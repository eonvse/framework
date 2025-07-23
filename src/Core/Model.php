<?php
declare(strict_types=1);

namespace Core;

abstract class Model
{
    protected static string $table;
    protected static string $primaryKey = 'id';

    public static function all(): array
    {
        $stmt = Database::query("SELECT * FROM " . static::$table);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::query(
            "SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = :id",
            ['id' => $id]
        );
        return $stmt->fetch() ?: null;
    }

    // Другие базовые методы...
}