<?php
$hostname="localhost";
$username="root";
$password="";
$dbname="sad";

try {
    $pdo = new PDO(
        "mysql:host=$hostname;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {
    die("Database connection failed. Please check your MySQL settings.");
}
