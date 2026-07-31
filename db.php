<?php
// ThriftHub Database Connection File

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "thrift_db";

// Connect to MySQL server
$conn = @new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("<div style='font-family:sans-serif; padding:20px; background:#fff5f5; border:1px solid #feb2b2; color:#c53030; margin:40px auto; max-width:600px; border-radius:8px;'>
        <h2>⚠️ Cannot Connect to MySQL</h2>
        <p>Please make sure <strong>MySQL</strong> is started in your <strong>XAMPP Control Panel</strong>.</p>
        <p><small>Error: " . htmlspecialchars($conn->connect_error) . "</small></p>
    </div>");
}


if (!$conn->select_db($dbname)) {
    // Try to auto-create database if possible
    $conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    if (!$conn->select_db($dbname)) {
        die("<div style='font-family:sans-serif; padding:20px; background:#fff5f5; border:1px solid #feb2b2; color:#c53030; margin:40px auto; max-width:600px; border-radius:8px;'>
            <h2>⚠️ Database '$dbname' Not Found</h2>
            <p>Please import the <code>database.sql</code> file into phpMyAdmin or MySQL to set up the ThriftHub database.</p>
        </div>");
    }
}

// Ensure UTF-8 encoding
$conn->set_charset("utf8mb4");
?>
