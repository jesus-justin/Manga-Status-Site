 -m <?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "manga_library";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error;
}

// Set charset to handle special characters
$conn->set_charset("utf8mb4");
?>
