<?php
require_once 'db.php';

$stmt = $conn->prepare("SELECT username, email FROM users");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Username: " . htmlspecialchars($row['username']) . " - Email: " . htmlspecialchars($row['email']) . "<br>";
    }
} else {
    echo "No users found.";
}

$conn->close();
?>
