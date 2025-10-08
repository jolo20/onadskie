<?php
// connections.php

// Database connection details for lgu2
$servername_lgu2 = "localhost";
$username_lgu2 = "root";
$password_lgu2 = "";
$dbname_lgu2 = "lgu2";

// Create connection to lgu2 database
$lgu2_conn = new mysqli($servername_lgu2, $username_lgu2, $password_lgu2, $dbname_lgu2);

// Check lgu2 connection
if ($lgu2_conn->connect_error) {
    die("Connection to lgu2 failed: " . $lgu2_conn->connect_error);
}

?>