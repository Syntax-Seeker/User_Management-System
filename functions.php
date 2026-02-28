<?php

function dbConnect() {
    // Database connection parameters
    $host = 'localhost';
    $db = 'database_name';
    $user = 'username';
    $pass = 'password';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
    }
}

function validateInput($data) {
    return htmlspecialchars(strip_tags($data));
}

function authenticateUser($username, $password) {
    $pdo = dbConnect();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        return true;
    }
    return false;
}

?>