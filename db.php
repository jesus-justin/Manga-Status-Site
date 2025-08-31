<?php
/**
 * Database Configuration File
 *
 * This file establishes a secure database connection for the Manga Library application.
 * It uses environment variables for sensitive configuration to improve security.
 *
 * @author Manga Library Development Team
 * @version 1.0
 * @since 2024
 */

// Database configuration using environment variables for security
// Environment variables should be set in production for better security
$servername = getenv('DB_HOST') ?: "localhost";  // Database server hostname
$username = getenv('DB_USER') ?: "root";        // Database username
$password = getenv('DB_PASS') ?: "";            // Database password (empty for local development)
$dbname = getenv('DB_NAME') ?: "manga_library"; // Database name

/**
 * Establish database connection with comprehensive error handling
 *
 * Uses MySQLi extension for improved security and prepared statements support.
 * Connection is configured with timeout and charset settings for reliability.
 */
try {
    // Create MySQLi connection object
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Verify connection was successful
    if ($conn->connect_error) {
        // Log detailed error for debugging (not exposed to user)
        error_log("Database connection failed: " . $conn->connect_error);
        // Show user-friendly error message
        die("Database connection failed. Please try again later.");
    }

    // Set character set to UTF-8 with full Unicode support
    // This prevents encoding issues with special characters
    if (!$conn->set_charset("utf8mb4")) {
        error_log("Error setting charset: " . $conn->error);
        die("Database configuration error.");
    }

    // Set connection timeout to prevent hanging connections
    // 10 seconds is reasonable for most applications
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

} catch (Exception $e) {
    // Catch any unexpected errors during connection setup
    error_log("Database setup error: " . $e->getMessage());
    die("Database configuration error. Please contact support.");
}

/**
 * Global database connection object
 *
 * This $conn variable is used throughout the application for database operations.
 * Always use prepared statements with this connection for security.
 *
 * @global mysqli $conn Database connection object
 */
?>
