<?php
error_reporting(0);
ini_set('display_errors', 1);

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', '8778214372'); 
define('DB_NAME', 'abiturent_v2');

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);

if ($conn->connect_error) {
    die("ERROR: Could not connect. " . $conn->connect_error);
}

$sql_create_db = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!$conn->query($sql_create_db)) {
    die("ERROR: Could not create database " . DB_NAME . ". " . $conn->error);
}

$conn->select_db(DB_NAME);
$conn->set_charset("utf8mb4");
if (session_status() == PHP_SESSION_NONE) {
    session_start();
} 


define('BASE_PATH', dirname(__DIR__)); 
define('UPLOAD_DIR_DIRECTIONS', BASE_PATH . '/uploads/directions/');
define('UPLOAD_DIR_PROGRAMS', BASE_PATH . '/uploads/programs/');
define('UPLOAD_DIR_ESTABLISHMENTS', BASE_PATH . '/uploads/establishments/');
define('UPLOAD_DIR_CLUSTERS', BASE_PATH . '/uploads/clusters/'); 

$upload_dirs = [UPLOAD_DIR_DIRECTIONS, UPLOAD_DIR_PROGRAMS, UPLOAD_DIR_ESTABLISHMENTS, UPLOAD_DIR_CLUSTERS];
foreach ($upload_dirs as $dir) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) {
            die("Failed to create directory: " . $dir);
        }
    }
}
?>
