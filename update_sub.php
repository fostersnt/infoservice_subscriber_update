<?php
require 'vendor/autoload.php';
require 'db_connection.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Log directory and file setup
try {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true); // Create the logs directory if it doesn't exist
    }
    $logFile = $logDir . '/subscribers_update_' . date('Y_m_d') . '.log';

    // Log script start
    logMessage("Script started");

    $databases = [$_ENV['DATABASE_ONE'], $_ENV['DATABASE_TWO']];

    $database_table = $_ENV['DB_TABLE'];
    $database_table_column = $_ENV['DB_TABLE_COLUMN'];

    $db_result = [];

    if (count($databases) > 0) {

        foreach ($databases as $dbname) {
            $conn = connectToDatabase($dbname);

            if (!$conn) {
                continue; // Skip to the next database if connection fails
            } else {
                // Get the count of subscribers
                $countQuery = "SELECT COUNT(*) AS count 
                   FROM $database_table 
                   WHERE status = 10 
                     AND MONTH($database_table_column) = 8 
                     AND YEAR($database_table_column) = 2024";
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
                $updateQuery = "UPDATE $database_table 
                    SET status = 2 
                    WHERE status = 10 
                      AND MONTH($database_table_column) = 8 
                      AND YEAR($database_table_column) = 2024 
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
        }
    }else {
        echo "NO DATABASE AVAILABLE";
    }
} catch (\Throwable $th) {
    logMessage("ERROR OCCURRED: " . $th->getMessage() . "\nLINE NUMBER: " . $th->getLine());
    echo "ERROR OCCURRED: " . $th->getMessage() . "\nLINE NUMBER: " . $th->getLine();
    //throw $th;
}


try {
    // Send email report
    $mail = new PHPMailer(true); // Passing `true` enables exceptions

    // Server settings
    $mail->isSMTP();
    $mail->Host = $_ENV['MAIL_HOST'];
    $mail->Port = $_ENV['MAIL_PORT'];
    $mail->SMTPAuth = false; // Set to true if SMTP requires authentication

    // Recipients
    $mail->setFrom($_ENV['NO_REPLY_MAIL'], 'InfoServices');
    $mail->addAddress($_ENV['RECIPIENT_ONE']); // Add a recipient
    $mail->addAddress($_ENV['RECIPIENT_TWO']); // Add a recipient

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
