<?php
/**
 * Veritolytics - Production MySQL & Database Session Handler
 * Pure Core PHP 8.2+ Architecture
 * 
 * Implements SessionHandlerInterface for persistent, centralized session storage
 * across distributed serverless container instances on Vercel (FrankenPHP / Docker).
 */

class DatabaseSessionHandler implements SessionHandlerInterface {
    private PDO $db;
    private int $maxLifetime;
    private bool $tableChecked = false;
    private string $driver = 'mysql';

    public function __construct(PDO $db, int $maxLifetime = 604800) {
        $this->db = $db;
        $this->maxLifetime = $maxLifetime;
        try {
            $this->driver = (string)$this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        } catch (Throwable $e) {
            $this->driver = 'mysql';
        }
    }

    /**
     * Initialize session resources and verify database table
     */
    public function open(string $path, string $name): bool {
        if (!$this->tableChecked) {
            $this->ensureTableExists();
            $this->tableChecked = true;
        }
        return true;
    }

    /**
     * Close session handler
     */
    public function close(): bool {
        return true;
    }

    /**
     * Read session data from centralized database
     */
    public function read(string $id): string|false {
        return $this->retry(function() use ($id) {
            $minTime = time() - $this->maxLifetime;
            $stmt = $this->db->prepare("
                SELECT data FROM sessions 
                WHERE id = :id AND last_activity > :min_time 
                LIMIT 1
            ");
            $stmt->execute([
                ':id' => $id,
                ':min_time' => $minTime
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['data'])) {
                return (string)$row['data'];
            }
            return '';
        }, '');
    }

    /**
     * Persist session data atomically to database
     */
    public function write(string $id, string $data): bool {
        return $this->retry(function() use ($id, $data) {
            $userId = null;
            if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
                $userId = (int)$_SESSION['user_id'];
            }

            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
            if ($ip && str_contains($ip, ',')) {
                $ip = trim(explode(',', $ip)[0]);
            }
            $ip = $ip ? substr($ip, 0, 45) : null;
            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
            $now = time();

            // REPLACE INTO provides atomic upsert across both MySQL and SQLite
            $stmt = $this->db->prepare("
                REPLACE INTO sessions (id, user_id, ip_address, user_agent, data, last_activity)
                VALUES (:id, :user_id, :ip_address, :user_agent, :data, :last_activity)
            ");
            return $stmt->execute([
                ':id' => $id,
                ':user_id' => $userId,
                ':ip_address' => $ip,
                ':user_agent' => $ua,
                ':data' => $data,
                ':last_activity' => $now
            ]);
        }, false);
    }

    /**
     * Destroy session record upon logout or expiration
     */
    public function destroy(string $id): bool {
        return $this->retry(function() use ($id) {
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        }, false);
    }

    /**
     * Garbage collection for expired session records
     */
    public function gc(int $max_lifetime): int|false {
        return $this->retry(function() use ($max_lifetime) {
            $cutoff = time() - $max_lifetime;
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE last_activity < :cutoff");
            $stmt->execute([':cutoff' => $cutoff]);
            return $stmt->rowCount();
        }, 0);
    }

    /**
     * Execute operation with automatic retry on temporary database lock
     */
    private function retry(callable $fn, mixed $default): mixed {
        $attempts = 0;
        while ($attempts < 3) {
            try {
                return $fn();
            } catch (Throwable $e) {
                $attempts++;
                if ($attempts >= 3) {
                    error_log("DatabaseSessionHandler Error after 3 attempts: " . $e->getMessage());
                    return $default;
                }
                usleep(50000); // 50ms pause before retry
            }
        }
        return $default;
    }

    /**
     * Ensure sessions table exists in active database
     */
    public function ensureTableExists(): void {
        try {
            if ($this->driver === 'sqlite') {
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS sessions (
                      id TEXT PRIMARY KEY,
                      user_id INTEGER DEFAULT NULL,
                      ip_address TEXT DEFAULT NULL,
                      user_agent TEXT DEFAULT NULL,
                      data TEXT NOT NULL,
                      last_activity INTEGER NOT NULL
                    );
                    CREATE INDEX IF NOT EXISTS idx_sessions_last_activity ON sessions (last_activity);
                    CREATE INDEX IF NOT EXISTS idx_sessions_user_id ON sessions (user_id);
                ");
            } else {
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS `sessions` (
                      `id` VARCHAR(128) NOT NULL PRIMARY KEY,
                      `user_id` INT DEFAULT NULL,
                      `ip_address` VARCHAR(45) DEFAULT NULL,
                      `user_agent` VARCHAR(255) DEFAULT NULL,
                      `data` MEDIUMTEXT NOT NULL,
                      `last_activity` INT UNSIGNED NOT NULL,
                      INDEX `idx_sessions_last_activity` (`last_activity`),
                      INDEX `idx_sessions_user_id` (`user_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            }
        } catch (Throwable $e) {
            error_log("DatabaseSessionHandler::ensureTableExists Notice: " . $e->getMessage());
        }
    }
}
