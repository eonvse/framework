<?php
declare(strict_types=1);

namespace Core\Database;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

class Connection
{
    private static ?PDO $pdo = null;

    public static function getInstance(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

    public function getPdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = $this->createConnection();
        }
        return self::$pdo;
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    private static function createConnection(): PDO
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $_ENV['DB_DRIVER'] ?? 'mysql',
            $_ENV['DB_HOST'] ?? 'localhost',
            $_ENV['DB_PORT'] ?? '3306',
            $_ENV['DB_DATABASE'],
            $_ENV['DB_CHARSET'] ?? 'utf8mb4'
        );

        try {
            return new PDO(
                $dsn,
                $_ENV['DB_USERNAME'],
                $_ENV['DB_PASSWORD'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }
}