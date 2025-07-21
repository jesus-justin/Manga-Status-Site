<?php
include 'db.php';

function fetchMangaCover($title) {
  // Use Jikan API to search for manga
  $searchUrl = 'https://api.jikan.moe/v4/manga?q=' . urlencode($title) . '&limit=1';
  $response = @file_get_contents($searchUrl);
  
  if ($response === false) {
    return false;
  }
  
  $data = json_decode($response, true);
  
  if (empty($data['data'])) {
    return false;
  }
  
  // Get the image URL from the first result
  $imageUrl = $data['data'][0]['images']['jpg']['large_image_url'] ?? false;
  
  if (!$imageUrl) {
    return false;
  }
  
  // Download and save the image
  $imageContent = @file_get_contents($imageUrl);
  if ($imageContent === false) {
    return false;
  }
  
  $filename = strtolower(str_replace(' ', '_', trim($title))) . '.jpeg';
  $savePath = __DIR__ . '/images/' . $filename;
  
  if (file_put_contents($savePath, $imageContent) === false) {
    return false;
  }
  
  return true;
}

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
    // Try to fetch and save the manga cover
    fetchMangaCover($title);
    header("Location: home.php");
    exit();
  } else {
    echo "Error: " . $conn->error;
  }
}
?>
