<?php
    #api endpoint for transaction table in MariaDB database

    header("Content-Type: application/json");

    global $conn;

    $sql = "SELECT * FROM transaction";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($transactions);

?>