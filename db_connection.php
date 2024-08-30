<?php
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Log function to write messages to the log file
function logMessage($message) {
    global $logFile;
    date_default_timezone_set('Africa/Accra');
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

// Database connection details
$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASSWORD'];

function connectToDatabase($dbname) {
    global $servername, $username, $password;

    logMessage("Connecting to database: $dbname");

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        logMessage("Database connection failed for $dbname: " . $conn->connect_error);
        return false;
    } else {
        logMessage("Database connection successful for $dbname");
        return $conn;
    }
}
?>
