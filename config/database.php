<?php
/**
 * Veritolytics - Universal Database Connection Manager (PDO)
 * Pure Core PHP 8.2+ Architecture
 * 
 * Supports:
 * - Direct MySQL / Cloud MySQL (Railway, TiDB Cloud, Aiven, PlanetScale, AWS RDS, Supabase)
 * - Single Connection String (DATABASE_URL, MYSQL_URL, CLEARDB_DATABASE_URL, JAWSDB_URL)
 * - Standard Split Credentials (DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT)
 * - Railway Platform Variables (MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE, MYSQLPORT)
 * - SQLite Fallback for zero-configuration local standalone testing
 */

require_once __DIR__ . '/app.php';

class Database {
    private static ?PDO $instance = null;
    private static string $driver = 'mysql';
    private static string $activeHost = '127.0.0.1';
    private static string $activeDatabase = 'veritolytics_db';
    private static ?string $connectionError = null;

    /**
     * Helper to read environment variables from getenv, $_ENV, and $_SERVER
     */
    private static function getEnvVal(string $key, ?string $default = null): ?string {
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return (string)$val;
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return (string)$_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return (string)$_SERVER[$key];
        }
        return $default;
    }

    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        // 1. Check for single URL connection string (standard across modern PaaS like Railway / Heroku / TiDB)
        $dbUrl = self::getEnvVal('DATABASE_URL') 
              ?: self::getEnvVal('MYSQL_URL') 
              ?: self::getEnvVal('CLEARDB_DATABASE_URL') 
              ?: self::getEnvVal('JAWSDB_URL');

        $host = '127.0.0.1';
        $port = 3306;
        $dbname = 'veritolytics_db';
        $user = 'root';
        $pass = '';

        if (!empty($dbUrl)) {
            $parsed = parse_url($dbUrl);
            if ($parsed && isset($parsed['host'])) {
                $host = $parsed['host'];
                $port = (int)($parsed['port'] ?? 3306);
                $user = isset($parsed['user']) ? urldecode($parsed['user']) : 'root';
                $pass = isset($parsed['pass']) ? urldecode($parsed['pass']) : '';
                $dbname = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'veritolytics_db';
            }
        } else {
            // 2. Check split variables (Railway format, standard DB_* format, or MYSQL_* format)
            $host = self::getEnvVal('DB_HOST')
                 ?: self::getEnvVal('MYSQLHOST')
                 ?: self::getEnvVal('MYSQL_HOST')
                 ?: '127.0.0.1';

            $port = (int)(self::getEnvVal('DB_PORT')
                       ?: self::getEnvVal('MYSQLPORT')
                       ?: self::getEnvVal('MYSQL_PORT')
                       ?: 3306);

            $dbname = self::getEnvVal('DB_NAME')
                   ?: self::getEnvVal('MYSQLDATABASE')
                   ?: self::getEnvVal('MYSQL_DATABASE')
                   ?: 'veritolytics_db';

            $user = self::getEnvVal('DB_USER')
                 ?: self::getEnvVal('MYSQLUSER')
                 ?: self::getEnvVal('MYSQL_USER')
                 ?: 'root';

            $pass = self::getEnvVal('DB_PASS')
                 ?? self::getEnvVal('DB_PASSWORD')
                 ?? self::getEnvVal('MYSQLPASSWORD')
                 ?? self::getEnvVal('MYSQL_PASSWORD')
                 ?? '';
        }

        self::$activeHost = $host;
        self::$activeDatabase = $dbname;

        // Attempt 1: Connect to MySQL
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $isLocal = ($host === '127.0.0.1' || $host === 'localhost');
            $timeout = (int)(self::getEnvVal('DB_TIMEOUT') ?: ($isLocal ? 2 : 7));

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => $timeout,
            ];

            // Automatic SSL/TLS detection for Cloud MySQL (TiDB Cloud, Railway, Aiven, PlanetScale, AWS RDS)
            $dbSsl = self::getEnvVal('DB_SSL');
            $sslCa = self::getEnvVal('DB_SSL_CA');
            $isCloudHost = (!$isLocal && (
                str_contains($host, 'tidbcloud.com') ||
                str_contains($host, 'railway.app') ||
                str_contains($host, 'rlwy.net') ||
                str_contains($host, 'aivencloud.com') ||
                str_contains($host, 'planetscale.net') ||
                str_contains($host, 'amazonaws.com')
            ));

            if ($dbSsl === 'true' || $sslCa || $isCloudHost) {
                if ($sslCa && file_exists($sslCa)) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
                }
                // Cloud MySQL proxies frequently use server certs that need verification bypass if no CA bundle is provided
                if (self::getEnvVal('DB_SSL_VERIFY') === 'false' || empty($sslCa)) {
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                }
            }

            self::$instance = new PDO($dsn, $user, $pass, $options);
            self::$driver = 'mysql';
            self::$connectionError = null;

            // Auto-migrate schema parity columns on MySQL (including soft-delete column deleted_at)
            try {
                $cols = self::$instance->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN, 0);
                $requiredCols = [
                    'pending_email' => 'VARCHAR(191) DEFAULT NULL',
                    'email_verification_token' => 'VARCHAR(255) DEFAULT NULL',
                    'email_verification_expires_at' => 'DATETIME DEFAULT NULL',
                    'state' => 'VARCHAR(100) DEFAULT NULL',
                    'city' => 'VARCHAR(100) DEFAULT NULL',
                    'avatar' => 'VARCHAR(255) DEFAULT NULL',
                    'notification_preferences' => 'TEXT DEFAULT NULL',
                    'deleted_at' => 'DATETIME DEFAULT NULL',
                ];
                foreach ($requiredCols as $colName => $colDef) {
                    if (!in_array($colName, $cols)) {
                        self::$instance->exec("ALTER TABLE `users` ADD COLUMN `{$colName}` {$colDef}");
                    }
                }
            } catch (Throwable $migrateEx) {
                // Table might be initializing
            }

            // Ensure support_tickets table exists on MySQL
            try {
                self::$instance->query("SELECT 1 FROM `support_tickets` LIMIT 1");
            } catch (Throwable $tblEx) {
                try {
                    self::$instance->exec("
                        CREATE TABLE IF NOT EXISTS `support_tickets` (
                          `id` INT AUTO_INCREMENT PRIMARY KEY,
                          `ticket_number` VARCHAR(50) NOT NULL UNIQUE,
                          `user_id` INT DEFAULT NULL,
                          `name` VARCHAR(100) NOT NULL,
                          `email` VARCHAR(191) NOT NULL,
                          `subject` VARCHAR(255) NOT NULL,
                          `message` TEXT NOT NULL,
                          `status` ENUM('open', 'in_review', 'resolved') NOT NULL DEFAULT 'open',
                          `priority` ENUM('normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
                          `admin_reply` TEXT DEFAULT NULL,
                          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                          INDEX `idx_tickets_user` (`user_id`),
                          INDEX `idx_tickets_status` (`status`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                } catch (Throwable $createEx) {}
            }

            return self::$instance;
        } catch (PDOException $e) {
            self::$connectionError = $e->getMessage();
            if ($host !== '127.0.0.1' && $host !== 'localhost') {
                error_log("Veritolytics MySQL Connection Error: " . $e->getMessage() . " (Host: {$host}, Port: {$port}, Database: {$dbname})");
            }

            // Attempt 2: Use SQLite fallback database for local zero-config testing
            $sqlitePath = dirname(__DIR__) . '/database/veritolytics.sqlite';

            try {
                $dsn = "sqlite:" . $sqlitePath;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                self::$instance = new PDO($dsn, null, null, $options);
                self::$instance->exec('PRAGMA foreign_keys = ON;');
                self::$instance->exec('PRAGMA journal_mode = WAL;');
                self::$instance->exec('PRAGMA busy_timeout = 5000;');
                self::$driver = 'sqlite';
                self::$activeHost = 'local:sqlite';
                self::$activeDatabase = 'veritolytics.sqlite';

                // Initialize schema ONLY if users table does not exist
                $hasUsersTable = false;
                try {
                    $hasUsersTable = (bool)self::$instance->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='users'")->fetch();
                } catch (Throwable $t) {}

                if (!$hasUsersTable) {
                    $schemaSql = file_get_contents(dirname(__DIR__) . '/database/schema_sqlite.sql');
                    if ($schemaSql) {
                        self::$instance->exec($schemaSql);
                    }
                }

                // Auto-migrate missing columns for SQLite
                try {
                    $cols = self::$instance->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1);
                    $requiredCols = [
                        'pending_email' => 'TEXT DEFAULT NULL',
                        'email_verification_token' => 'TEXT DEFAULT NULL',
                        'email_verification_expires_at' => 'TEXT DEFAULT NULL',
                        'state' => 'TEXT DEFAULT NULL',
                        'city' => 'TEXT DEFAULT NULL',
                        'avatar' => 'TEXT DEFAULT NULL',
                        'notification_preferences' => 'TEXT DEFAULT NULL',
                        'deleted_at' => 'TEXT DEFAULT NULL',
                    ];
                    foreach ($requiredCols as $colName => $colDef) {
                        if (!in_array($colName, $cols)) {
                            self::$instance->exec("ALTER TABLE users ADD COLUMN {$colName} {$colDef}");
                        }
                    }
                } catch (Throwable $migrateEx) {}

                return self::$instance;
            } catch (PDOException $sqliteEx) {
                die("Critical Database Error: Unable to establish database connection. Details: " . htmlspecialchars($e->getMessage()));
            }
        }
    }

    public static function getDriver(): string {
        if (self::$instance === null) {
            self::getConnection();
        }
        return self::$driver;
    }

    public static function getActiveHost(): string {
        return self::$activeHost;
    }

    public static function getActiveDatabase(): string {
        return self::$activeDatabase;
    }

    public static function getConnectionError(): ?string {
        return self::$connectionError;
    }

    public static function getStatusInfo(): array {
        return [
            'driver' => self::getDriver(),
            'host' => self::$activeHost,
            'database' => self::$activeDatabase,
            'is_mysql' => self::$driver === 'mysql',
            'error' => self::$connectionError
        ];
    }

    public static function closeConnection(): void {
        self::$instance = null;
    }
}

/**
 * Global helper to retrieve active PDO instance
 */
function get_db(): PDO {
    return Database::getConnection();
}
