<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected static string $primaryKey = 'id';

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::query(
            "SELECT * FROM users WHERE email = :email",
            ['email' => $email]
        );
        return $stmt->fetch() ?: null;
    }
}