<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Database configuration
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "barangay_db";

$conn = mysqli_connect($host, $user, $pass, $dbname); 

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// 1. SET SYSTEM TIMEZONE
date_default_timezone_set('Asia/Manila');

// 2. THE HEARTBEAT LOGIC
// Every time any page is loaded, update the user's "Last Seen" timestamp
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $conn->query("INSERT INTO portal_activity (user_id, last_seen) 
                  VALUES ('$uid', NOW()) 
                  ON DUPLICATE KEY UPDATE last_seen = NOW()");
}
?>