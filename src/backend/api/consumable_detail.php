<?php
    #api endpoint for consumable_detail table in MariaDB database

    header("Content-Type: application/json");

    global $conn;

    $sql = "SELECT * FROM consumable_detail";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $consumable_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($consumable_details);

?>