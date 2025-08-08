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

function getExternalMangaLinks($title) {
  $encoded = urlencode($title);
  $sites = [
    ["name" => "WeebCentral", "url" => "https://weebcentral.com/search?query=$encoded"],
    ["name" => "MangaDex", "url" => "https://mangadex.org/search?title=$encoded"],
    ["name" => "MangaReader.to", "url" => "https://mangareader.to/search?keyword=$encoded"],
    ["name" => "Manga Plus", "url" => "https://mangaplus.shueisha.co.jp/updates"],
    ["name" => "Viz", "url" => "https://www.viz.com/search/$encoded/all"]
  ];
  return json_encode($sites);
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

  $external_links = $conn->real_escape_string(getExternalMangaLinks($title));

  $sql = "INSERT INTO manga (title, status, category, read_link, last_chapter, external_links)
          VALUES ('$title', '$status', '$category', 
          " . ($read_link !== '' ? "'$read_link'" : "NULL") . ",
          " . ($status === 'currently reading' && $last_chapter !== '' ? "'$last_chapter'" : "NULL") . ",
          '$external_links'
          )";

  if ($conn->query($sql) === TRUE) {
    // Try to fetch and save the manga cover
    fetchMangaCover($title);
    header("Location: home.php?success=1");
    exit();
  } else {
    echo "Error: " . $conn->error;
  }
}
?>
