<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = $conn->real_escape_string($_POST['title']);
  $status = $conn->real_escape_string($_POST['status']);
  $category = trim($_POST['category']);
  $read_link = $conn->real_escape_string(trim($_POST['read_link']));
  $last_chapter = $conn->real_escape_string(trim($_POST['last_chapter']));

  if ($category === '') {
    $category = 'Uncategorized';
  }

  $sql = "INSERT INTO manga (title, status, category, read_link, last_chapter)
          VALUES ('$title', '$status', '$category', 
          " . ($read_link !== '' ? "'$read_link'" : "NULL") . ",
          " . ($status === 'currently reading' && $last_chapter !== '' ? "'$last_chapter'" : "NULL") . "
          )";

  if ($conn->query($sql) === TRUE) {
    header("Location: home.php");
    exit();
  } else {
    echo "Error: " . $conn->error;
  }
}
?>
