<?php
    #api endpoint for user table in MariaDB database

    header("Content-Type: application/json");

    global $conn;

    $sql = "SELECT * FROM user";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($users);

?>