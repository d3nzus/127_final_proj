<?php
    #api endpoint for user_warning table in MariaDB database

    header("Content-Type: application/json");

    require_once("../config/config.php");

    $conn = new mysqli($host, $user, $password, $dbname);

    $sql = "SELECT * FROM user_warning";
    $result = $conn->query($sql);

    $user_warnings = [];

    while ($row = $result->fetch_assoc()) {
        $user_warnings[] = $row;
}

echo json_encode($user_warnings);

?>