<?php
// ============================================================
// db.php — MySQL Database Connection (mysqli)
// Course: NCSM301 Web Development Laboratory
// ============================================================

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';                       // set your MySQL password here
$db_name = 'hostel_complaints';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    http_response_code(500);
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
