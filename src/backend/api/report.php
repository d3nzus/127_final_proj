<?php
    #api endpoint for report table in MariaDB database

    header("Content-Type: application/json");

    global $conn;

    $sql = "SELECT * FROM report";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($reports);

?>