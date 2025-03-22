<?php

namespace Ivy;

use Delight\Db\PdoDatabase;
use PDO;
use PDOException;

class DB
{
    private static ?PdoDatabase $connection = null;

    public static function init(): void
    {
        if (self::$connection !== null) {
            return;
        }

        try {
            $pdo = new PDO(
                "mysql:host=" . $_ENV['DB_HOST'] . ";port=" . $_ENV['DB_PORT'] . ";dbname=" . $_ENV['DB_DATABASE'] . ";charset=utf8",
                $_ENV['DB_USERNAME'],
                $_ENV['DB_PASSWORD']
            );
            self::$connection = PdoDatabase::fromPdo($pdo);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new RuntimeException("Database connection failed.");
        }
    }

    public static function getConnection(): PdoDatabase
    {
        if (self::$connection === null) {
            self::init();
        }

        if (self::$connection === null) {
            throw new RuntimeException("Database connection is not initialized.");
        }

        return self::$connection;
    }
}