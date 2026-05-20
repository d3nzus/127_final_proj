<?php
    #api endpoint for notification table in MariaDB database

    header("Content-Type: application/json");

    global $conn;

    $sql = "SELECT * FROM notification";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($notifications);

?>