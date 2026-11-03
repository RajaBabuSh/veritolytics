<?php
/**
 * Veritolytics - Database Connection Configuration Example
 * Pure Core PHP 8.2+ Architecture
 * 
 * Copy this file to config/database.php or configure environment variables.
 */

require_once __DIR__ . '/app.php';

class Database {
    private static ?PDO $instance = null;
    private static string $driver = 'mysql';

    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        // Database credentials from environment variables or standard defaults
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_NAME') ?: 'veritolytics_db';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

        // Attempt 1: Connect to MySQL
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 2,
            ];
            self::$instance = new PDO($dsn, $user, $pass, $options);
            self::$driver = 'mysql';
            return self::$instance;
        } catch (PDOException $e) {
            // Attempt 2: SQLite fallback database for zero-friction local testing
            $sqlitePath = dirname(__DIR__) . '/database/veritolytics.sqlite';

            try {
                $dsn = "sqlite:" . $sqlitePath;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                self::$instance = new PDO($dsn, null, null, $options);
                self::$driver = 'sqlite';
                self::$instance->exec("PRAGMA foreign_keys = ON;");
                return self::$instance;
            } catch (PDOException $sqliteEx) {
                die("Database Connection Error: Failed to connect to MySQL and SQLite.");
            }
        }
    }

    public static function getDriver(): string {
        return self::$driver;
    }
}

function get_db(): PDO {
    return Database::getConnection();
}
