<?php
include 'db.php';

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

$sql = "SELECT id, title FROM manga";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $title = $row['title'];
    $external_links = getExternalMangaLinks($title);
    
    // Use prepared statement to prevent SQL injection
    $update = "UPDATE manga SET external_links=? WHERE id=?";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("si", $external_links, $id);
    $stmt->execute();
  }
  echo "All manga external links updated successfully.";
} else {
  echo "No manga found to update.";
}
$conn->close(); 