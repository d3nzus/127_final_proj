<?php

// Connect to the database initialized by db_init and run the upcart_builder_seeder.sql file.
require_once __DIR__ . '/../config/config.php';

$conn = new mysqli($host, $user, $password);

$seederPath = __DIR__ . '/upcart_builder_seeder.sql';
if (!file_exists($seederPath)) {
    die('Seeder file not found: ' . $seederPath . "\n");
}

$sql = file_get_contents($seederPath);
if ($sql === false) {
    die('Failed to read seeder file.\n');
}

if ($conn->multi_query($sql)) {

    do {
        // store first result
        if ($result = $conn->store_result()) {
            $result->free();
        }

    } while ($conn->next_result());

    echo "Executed successfully";

} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>