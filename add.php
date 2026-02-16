<?php
require_once 'db.php';
require_once 'auth.php';

$auth = new Auth($conn);
$auth->requireLogin();

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
  // Validate CSRF token
  if (!isset($_POST['csrf_token']) || !$auth->validateCsrfToken($_POST['csrf_token'])) {
    die("CSRF token validation failed");
  }

  $title = trim($_POST['title']);
  $status = $_POST['status'];
  
  // Check if manga already exists using prepared statement
  $check_sql = "SELECT id FROM manga WHERE title = ?";
  $stmt = $conn->prepare($check_sql);
  $stmt->bind_param("s", $title);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows > 0) {
    // Manga already exists
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Manga Already Added</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'warning',
                title: 'Manga already added!',
                text: 'This manga has already been added to your list.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'home.php';
                }
            });
        </script>
    </body>
    </html>";
    exit();
  }
  
  // Handle multiple genres - convert array to comma-separated string
  $categories = isset($_POST['category']) ? $_POST['category'] : [];
  $category = !empty($categories) ? implode(', ', $categories) : 'Uncategorized';
  
  $read_link = trim($_POST['read_link']);
  $last_chapter = trim($_POST['last_chapter']);

  $external_links = getExternalMangaLinks($title);

  // Use prepared statement for INSERT query
  $sql = "INSERT INTO manga (title, status, category, read_link, last_chapter, external_links)
          VALUES (?, ?, ?, ?, ?, ?)";
  
  $stmt = $conn->prepare($sql);
  
  // Handle NULL values for optional fields
  $read_link_value = ($read_link !== '') ? $read_link : NULL;
  $last_chapter_value = ($status === 'currently reading' && $last_chapter !== '') ? $last_chapter : NULL;
  
  $stmt->bind_param("ssssss", $title, $status, $category, $read_link_value, $last_chapter_value, $external_links);

  if ($stmt->execute()) {
    // Try to fetch and save the manga cover
    fetchMangaCover($title);
    header("Location: home.php?success=1");
    exit();
  } else {
    echo "Error: " . $stmt->error;
  }
}
?>
