<?php
declare(strict_types=1);

namespace Core\Database;

use Core\Application;
use Core\Database\Connection;
use PDO;

class Migrator
{
    private PDO $connection;
    private string $migrationsPath;
    private string $migrationsTable = 'migrations';

    public function __construct(Application $app)
    {
        $this->connection = Connection::getInstance()->getPdo();
        $this->migrationsPath = $app->getDatabasePath() . '/migrations/';
    }

    public function createMigrationsTable(): void
    {
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
/**
 * Получает список уже примененных миграций
 * 
 * @return array Массив имен файлов примененных миграций
 */
    protected function getAppliedMigrations(): array
    {
        $this->ensureMigrationsTableExists();

        $stmt = $this->connection->query(
            "SELECT migration FROM {$this->migrationsTable} ORDER BY id ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Проверяет существование таблицы миграций и создает при необходимости
     */
    protected function ensureMigrationsTableExists(): void
    {
        $stmt = $this->connection->query(
            "SHOW TABLES LIKE '{$this->migrationsTable}'"
        );

        if ($stmt->rowCount() === 0) {
            $this->createMigrationsTable();
        }
    }

    public function runMigrations(): void
    {
        $this->ensureMigrationsTableExists();
        $applied = $this->getAppliedMigrations();
        $files = $this->getMigrationFiles();

        $toApply = array_diff($files, $applied);
        if (empty($toApply)) {
            echo "No migrations to run.\n";
            return;
        }

        $batch = $this->getNextBatchNumber();
        
        foreach ($toApply as $migration) {
            $this->applyMigration($migration, $batch);
        }

        echo sprintf("Applied %d migration(s)\n", count($toApply));
    }

    /**
     * Получает список файлов миграций из директории
     * 
     * @return array Массив имен файлов миграций
     */
    protected function getMigrationFiles(): array
    {
        $files = scandir($this->migrationsPath);

        return array_filter($files, function($file) {
            return preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_.+\.php$/', $file);
        });
    }

    /**
     * Получает номер следующего batch для миграций
     * 
     * @return int Номер batch
     */
    protected function getNextBatchNumber(): int
    {
        $stmt = $this->connection->query(
            "SELECT MAX(batch) FROM {$this->migrationsTable}"
        );

        return (int)$stmt->fetchColumn() + 1;
    }


    /*protected function runMigration(string $migration, int $batch): void
    {
        require_once $this->migrationsPath . $migration;
        
        $className = $this->getMigrationClassName($migration);
        $instance = new $className();
        
        echo "Running migration: {$migration}...";
        $instance->up();
        $this->recordMigration($migration, $batch);
        echo "OK\n";
    }*/

    /**
     * Применяет указанную миграцию
     * 
     * @param string $migration Имя файла миграции
     * @param int $batch Номер batch
     */
    protected function applyMigration(string $migration, int $batch): void
    {
        require_once $this->migrationsPath . $migration;
        
        $className = $this->getMigrationClassName($migration);
        $instance = new $className();
        
        echo "Applying migration: {$migration}...";
        $instance->up();
        $this->recordMigration($migration, $batch);
        echo "DONE\n";
    }

     /**
     * Извлекает имя класса из имени файла миграции
     */
    protected function getMigrationClassName(string $migration): string
    {
        $name = preg_replace('/\d+_/', '', $migration);
        $name = str_replace('.php', '', $name);
        $name = str_replace('_', ' ', $name);
        $name = ucwords($name);
        return str_replace(' ', '', $name);
    }

    protected function recordMigration(string $migration, int $batch): void
    {
        $stmt = $this->connection->prepare("
            INSERT INTO {$this->migrationsTable} (migration, batch) 
            VALUES (:migration, :batch)
        ");
        $stmt->execute(['migration' => $migration, 'batch' => $batch]);
    }

    public function rollback(): void
    {
        $batch = $this->getLastBatch();
        $migrations = $this->getMigrationsByBatch($batch);

        if (empty($migrations)) {
            echo "Nothing to rollback.\n";
            return;
        }

        foreach ($migrations as $migration) {
            $this->rollbackMigration($migration);
        }

        echo "Rollback completed successfully.\n";
    }

    protected function rollbackMigration(string $migration): void
    {
        require_once $this->migrationsPath . $migration;
        
        $className = $this->getMigrationClassName($migration);
        $instance = new $className();
        
        echo "Rolling back: {$migration}...";
        $instance->down();
        $this->deleteMigration($migration);
        echo "OK\n";
    }

    public function createMigration(string $name): string
    {
        $this->ensureMigrationsDirectoryExists();

        $fileName = $this->generateMigrationFileName($name);
        $filePath = $this->migrationsPath . $fileName;

        $className = $this->generateClassName($name);

        $content = $this->generateMigrationContent($className);

        if (file_put_contents($filePath, $content) === false) {
            throw new \RuntimeException("Failed to create migration file: {$filePath}");
        }

        return $filePath;
    }

    protected function ensureMigrationsDirectoryExists(): void
    {
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }
    }

    protected function generateMigrationFileName(string $name): string
    {
        return date('Y_m_d_His') . '_' . $this->sanitizeName($name) . '.php';
    }

    protected function sanitizeName(string $name): string
    {
        return preg_replace('/[^a-z0-9_]+/i', '_', $name);
    }

    protected function generateClassName(string $name): string
    {
        return implode('', array_map('ucfirst', explode('_', $this->sanitizeName($name))));
    }

    protected function generateMigrationContent(string $className): string
    {
        return <<<PHP
    <?php
    declare(strict_types=1);

    use Core\Database\Migration;

    class {$className} extends Migration
    {
        public function up(): void
        {
            // Write your migration code here
            \$this->execute("
                // Your SQL statements
            ");
        }

        public function down(): void
        {
            // Write your rollback code here
            \$this->execute("
                // Your rollback SQL
            ");
        }
    }
    PHP;
    }
}