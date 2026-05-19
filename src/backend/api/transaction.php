<?php
    #api endpoint for transaction table in MariaDB database

    header("Content-Type: application/json");

    require_once("../config/config.php");

    $conn = new mysqli($host, $user, $password, $dbname);

    $sql = "SELECT * FROM transaction";
    $result = $conn->query($sql);

    $transactions = [];

    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
}

echo json_encode($transactions);

?>