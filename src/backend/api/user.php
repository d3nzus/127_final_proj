<?php
    #api endpoint for user table in MariaDB database

    header("Content-Type: application/json");

    require_once("../config/config.php");

    $conn = new mysqli($host, $user, $password, $dbname);

    $sql = "SELECT * FROM user";
    $result = $conn->query($sql);

    $users = [];

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
}

echo json_encode($users);

?>