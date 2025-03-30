<?php

namespace Ivy\Manager;

use Delight\Db\PdoDatabase;
use PDO;
use PDOException;
use RuntimeException;

class DatabaseManager
{
    private static ?PdoDatabase $db = null;

    public static function connection(): PdoDatabase
    {
        if (self::$db === null) {
            try {
                $pdo = new PDO(
                    "mysql:host=" . $_ENV['DB_HOST'] . ";port=" . $_ENV['DB_PORT'] . ";dbname=" . $_ENV['DB_DATABASE'] . ";charset=utf8",
                    $_ENV['DB_USERNAME'],
                    $_ENV['DB_PASSWORD']
                );
                self::$db = PdoDatabase::fromPdo($pdo);
            } catch (PDOException $e) {
                error_log("Database Connection Error: " . $e->getMessage());
                throw new RuntimeException("Database connection failed.");
            }
        }
        return self::$db;
    }
}

