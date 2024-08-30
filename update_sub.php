<?php
try {
    require 'vendor/autoload.php'; // Include Composer's autoloader
    require 'db_connection.php'; // Include the database connection script
} catch (\Throwable $th) {
    echo "ERROR OCCURRED @ REQUIRED FILES: " . $th->getMessage() . "\nLINE NUMBER: " . $th->getLine();
    //throw $th;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// $dbHost = getenv('DB_HOST');
$dbHost = $_ENV['DB_HOST'];

// echo $dbHost;


// Log directory and file setup
try {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true); // Create the logs directory if it doesn't exist
    }
    $logFile = $logDir . '/subscribers_update_' . date('Y_m_d') . '.log';

// Log script start
    logMessage("Script started");

    $databases = ["mtninfobox", "mtnJobsDotGo"];

    foreach ($databases as $dbname) {
        $conn = connectToDatabase($dbname);

        if (!$conn) {
            continue; // Skip to the next database if connection fails
        }

        // Get the count of subscribers
        $countQuery = "SELECT COUNT(*) AS count 
                   FROM Subscribers 
                   WHERE status = 10 
                     AND MONTH(lastCharged) = 8 
                     AND YEAR(lastCharged) = 2024";
        $result = $conn->query($countQuery);

        if ($result) {
            $row = $result->fetch_assoc();
            $subscribersCount = $row['count'];
            logMessage("Subscribers Count Query successful for $dbname: $subscribersCount subscribers found");
        } else {
            logMessage("Subscribers Count Query failed for $dbname: " . $conn->error);
            $conn->close();
            continue; // Skip to the next database if query fails
        }

        // Update subscribers
        $updateQuery = "UPDATE Subscribers 
                    SET status = 2 
                    WHERE status = 10 
                      AND MONTH(lastCharged) = 8 
                      AND YEAR(lastCharged) = 2024 
                    LIMIT 2000";
        try {
            if ($conn->query($updateQuery) === TRUE) {
                logMessage("Subscribers Update Query successful for $dbname: " . $conn->affected_rows . " subscribers updated");
            } else {
                logMessage("Subscribers Update Query failed for $dbname: " . $conn->error);
            }
        } catch (\Exception $th) {
            echo "OPERATION FAILED: " . $th->getMessage() . "\nLINE NUMBER: " . $th->getLine();
        }

        // Close the database connection
        $conn->close();
        logMessage("Database connection closed for $dbname");
    }
} catch (\Throwable $th) {
    echo "ERROR OCCURRED: " . $th->getMessage() . "\nLINE NUMBER: " . $th->getLine();
    //throw $th;
}


try {
    // Send email report
    $mail = new PHPMailer(true); // Passing `true` enables exceptions

    // Server settings
    $mail->isSMTP();
    $mail->Host = '62.129.149.147';
    $mail->Port = 2525;
    $mail->SMTPAuth = false; // Set to true if SMTP requires authentication

    // Recipients
    $mail->setFrom('no-reply@gwosevo.com', 'InfoServices');
    $mail->addAddress('foster.asante@gwosevo.com'); // Add a recipient

    // Content
    $mail->isHTML(false); // Set email format to HTML or false
    $mail->Subject = 'Daily Subscribers Update Report';
    $mail->Body    = "Log file: " . $logFile . "\n";

    // Attach log file
    $mail->addAttachment($logFile);

    $mail->send();
    logMessage('Email report sent successfully');
} catch (Exception $e) {
    logMessage("Email report failed to send. Mailer Error: {$mail->ErrorInfo}");
}

// Log script end
logMessage("Script ended");
