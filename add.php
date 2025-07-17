<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = $conn->real_escape_string($_POST['title']);
  $status = $conn->real_escape_string($_POST['status']);
  $category = trim($_POST['category']);

  // Use DB default if category is empty
  if ($category === '') {
    $sql = "INSERT INTO manga (title, status) VALUES ('$title', '$status')";
  } else {
    $category = $conn->real_escape_string($category);
    $sql = "INSERT INTO manga (title, status, category) VALUES ('$title', '$status', '$category')";
  }

  if ($conn->query($sql) === TRUE) {
    header("Location: home.php");
    exit();
  } else {
    echo "Error: " . $conn->error;
  }
}
?>
