<?php

require_once __DIR__ . '/constants.php';

$dbHost = getenv('SPMS_DB_HOST') ?: 'localhost';
$dbUser = getenv('SPMS_DB_USER') ?: 'root';
$dbPass = getenv('SPMS_DB_PASS') ?: '';
$dbName = getenv('SPMS_DB_NAME') ?: 'spms_db';
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
