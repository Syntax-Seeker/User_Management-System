<?php

$db_host = 'localhost';
$db_user = 'username';
$db_password = 'password';
$db_name = 'database';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>