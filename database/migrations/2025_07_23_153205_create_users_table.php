<?php
declare(strict_types=1);

use Core\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        // Write your migration code here
        $this->execute("
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function down(): void
    {
        // Write your rollback code here
        $this->execute("
            DROP TABLE IF EXISTS users
        ");
    }
}