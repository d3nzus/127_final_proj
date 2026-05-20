<?php
    #api endpoint for user_warning table in MariaDB database

    header("Content-Type: application/json");

    global $conn;

    $sql = "SELECT * FROM user_warning";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $user_warnings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($user_warnings);

?>