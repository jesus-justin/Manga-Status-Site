<?php
include 'db.php';

function getExternalMangaLinks($title) {
  $encoded = urlencode($title);
  $sites = [
    ["name" => "WeebCentral", "url" => "https://weebcentral.com/search?query=$encoded"],
    ["name" => "MangaDex", "url" => "https://mangadex.org/search?title=$encoded"],
    ["name" => "ToonClash", "url" => "https://toonclash.com/search?query=$encoded"],
    ["name" => "MangaSee", "url" => "https://mangasee123.com/search/?keyword=$encoded"],
    ["name" => "MangaReader", "url" => "https://www.mangareader.net/search/?w=$encoded"]
  ];
  return json_encode($sites);
}

$sql = "SELECT id, title FROM manga";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $title = $row['title'];
    $external_links = $conn->real_escape_string(getExternalMangaLinks($title));
    $update = "UPDATE manga SET external_links='$external_links' WHERE id=$id";
    $conn->query($update);
  }
  echo "All manga external links updated successfully.";
} else {
  echo "No manga found to update.";
}
$conn->close(); 