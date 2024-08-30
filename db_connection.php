<?php
// Log function to write messages to the log file
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

// Database connection details
$servername = "62.129.149.141";
$username = "metabase";
$password = "metabase#221";

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
