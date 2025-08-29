<?php
require_once 'db.php';

$sql = "SELECT username, email FROM users"; // Adjust the query based on the actual table structure
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Username: " . htmlspecialchars($row['username']) . " - Email: " . htmlspecialchars($row['email']) . "<br>";
    }
} else {
    echo "No users found.";
}

$conn->close();
?>
