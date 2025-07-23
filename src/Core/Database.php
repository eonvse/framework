<?php
declare(strict_types=1);

namespace Core;

use Core\Database\Connection;
use PDO;
use PDOStatement;

class Database
{
    public static function query(string $sql, array $params = []): PDOStatement
    {
        return Connection::getInstance()->query($sql, $params);
    }

    public static function getPdo(): PDO
    {
        return Connection::getInstance()->getPdo();
    }
}