<?php
// Start session

session_start();

// Database connection
$conn = new mysqli("localhost", "root", "22701", "pharmacy");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


?>