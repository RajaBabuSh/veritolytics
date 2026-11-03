<?php
/**
 * Veritolytics - Database Connection Manager (PDO)
 * Pure Core PHP 8.2+ Architecture
 * 
 * Supports MySQL 8.0+ / MariaDB / XAMPP with fallback to SQLite for zero-config testing.
 */

require_once __DIR__ . '/app.php';

class Database {
    private static ?PDO $instance = null;
    private static string $driver = 'mysql';

    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        // Database credentials from environment variables or standard XAMPP defaults
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_NAME') ?: 'veritolytics_db';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

        // Attempt 1: Connect to MySQL
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $timeout = (int)(getenv('DB_TIMEOUT') ?: ($host === '127.0.0.1' || $host === 'localhost' ? 2 : 5));
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => $timeout,
            ];

            // Optional SSL/TLS configuration for Cloud MySQL (TiDB Cloud, Aiven, PlanetScale, Railway, AWS RDS)
            if (getenv('DB_SSL') === 'true' || getenv('DB_SSL_CA')) {
                if (getenv('DB_SSL_CA') && file_exists(getenv('DB_SSL_CA'))) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = getenv('DB_SSL_CA');
                }
                if (getenv('DB_SSL_VERIFY') === 'false') {
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                }
            }

            self::$instance = new PDO($dsn, $user, $pass, $options);
            self::$driver = 'mysql';
            return self::$instance;
        } catch (PDOException $e) {
            // MySQL could not be reached (e.g. XAMPP MySQL not started yet).
            // Attempt 2: Use SQLite fallback database for zero-friction local testing
            $sqlitePath = dirname(__DIR__) . '/database/veritolytics.sqlite';
            $isNewDb = !file_exists($sqlitePath);

            try {
                $dsn = "sqlite:" . $sqlitePath;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                self::$instance = new PDO($dsn, null, null, $options);
                self::$instance->exec('PRAGMA foreign_keys = ON;');
                self::$driver = 'sqlite';

                // Initialize schema if new
                if ($isNewDb) {
                    $schemaSql = file_get_contents(dirname(__DIR__) . '/database/schema_sqlite.sql');
                    if ($schemaSql) {
                        self::$instance->exec($schemaSql);
                    }
                }

                return self::$instance;
            } catch (PDOException $sqliteEx) {
                // If both fail, terminate with safe error description
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
}

/**
 * Global helper to retrieve active PDO instance
 */
function get_db(): PDO {
    return Database::getConnection();
}
