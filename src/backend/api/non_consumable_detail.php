<?php
    #api endpoint for non_consumable_detail table in MariaDB database

    header("Content-Type: application/json");

    global $conn;

    $sql = "SELECT * FROM non_consumable_detail";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $non_consumable_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($non_consumable_details);

?>