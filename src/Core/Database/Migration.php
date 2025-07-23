<?php
declare(strict_types=1);

namespace Core\Database;

use PDO;

abstract class Migration
{
    protected PDO $connection;

    public function __construct()
    {
        $this->connection = Connection::getInstance()->getPdo();
    }

    abstract public function up(): void;
    abstract public function down(): void;

    protected function execute(string $sql): void
    {
        $this->connection->exec($sql);
    }
}