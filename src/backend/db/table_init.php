<?php

// Connect to the database initialized by db_init and run the upcart_builder_seeder.sql file.
require_once __DIR__ . '/../config/connector.php';

$pdo = $conn

$seederPath = __DIR__ . '/upcart_builder_seeder.sql';
if (!file_exists($seederPath)) {
    die('Seeder file not found: ' . $seederPath . "\n");
}

$sql = file_get_contents($seederPath);
if ($sql === false) {
    die('Failed to read seeder file.\n');
}

$statements = preg_split('/;\s*\r?\n/', $sql);
foreach ($statements as $statement) {
    $statement = trim($statement);
    if ($statement === '' || strpos($statement, '--') === 0 || strpos($statement, '#') === 0) {
        continue;
    }

    try {
        $pdo->exec($statement);
    } catch (PDOException $e) {
        die('Seeder execution failed: ' . $e->getMessage() . "\nStatement: " . $statement . "\n");
    }
}

echo "Seeder executed successfully.\n";
