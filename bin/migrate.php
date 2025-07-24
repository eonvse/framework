#!/usr/bin/env php
<?php
require __DIR__ . '/../vendor/autoload.php';

use Core\Application;
use Core\Database\Migrator;

$app = new Application(dirname(__DIR__));
$migrator = new Migrator($app);

$command = $argv[1] ?? '';

switch ($command) {
    case 'run':
        $migrator->runMigrations();
        break;
    case 'rollback':
        $migrator->rollback();
        break;
    case 'create':
        if (!isset($argv[2])) {
            die("Usage: php bin/migrate.php create <migration_name>\n");
        }
        $name = $argv[2];
        try {
            $path = $migrator->createMigration($name);
            echo "Created migration: " . basename($path) . "\n";
        } catch (\Exception $e) {
            die("Error creating migration: " . $e->getMessage() . "\n");
        }
    break;
    default:
        echo "Available commands:\n";
        echo "  run - Run all pending migrations\n";
        echo "  rollback - Rollback the last batch of migrations\n";
        echo "  create <name> - Create new migration\n";
}