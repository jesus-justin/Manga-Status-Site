<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = $conn->real_escape_string($_POST['title']);
  $status = $conn->real_escape_string($_POST['status']);
  $category = $conn->real_escape_string($_POST['category']);

  $sql = "INSERT INTO manga (title, status, category) VALUES ('$title', '$status', '$category')";

  if ($conn->query($sql) === TRUE) {
    header("Location: home.php");
    exit();
  } else {
    echo "Error: " . $conn->error;
  }
}
?>
