<?php
// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page with return URL
    header("Location: login.php?redirect=browse.php");
    exit();
}

include 'db.php';

// Get all genres by splitting the category field
$sql_all = "SELECT category FROM manga WHERE category IS NOT NULL AND category != ''";
$all_result = $conn->query($sql_all);

$all_genres = [];
while ($row = $all_result->fetch_assoc()) {
    $genres = array_map('trim', explode(',', $row['category']));
    foreach ($genres as $genre) {
        if (!empty($genre)) {
            $all_genres[] = $genre;
        }
    }
}

// Get unique genres
$unique_genres = array_unique($all_genres);
sort($unique_genres);

$selected_genre = $_GET['genre'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Browse Manga - Manga Library</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="home.css">
  <style>
    .page-heading {
      text-align: center;
      margin-top: 2rem;
      margin-bottom: 1rem;
      font-size: 2.5rem;
      color: #eee;
    }
  </style>
</head>
<body>

<nav>
  <div class="logo">MangaLibrary</div>
  <ul>
    <li><a href="home.php">Home</a></li>
    <li><a href="browse.php">Browse</a></li>
  </ul>
  <div class="nav-actions">
    <button id="darkModeToggle" title="Toggle theme">🎨</button>
  </div>
</nav>

<h1 class="page-heading">Browse by Genre</h1>

<div class="category-filters">
<?php foreach ($unique_genres as $genre):
  $genre_name = htmlspecialchars($genre);
  $is_active = ($selected_genre === $genre) ? 'active' : '';
?>
  <a href="browse.php?genre=<?php echo urlencode($genre); ?>">
    <div class="filter-pill <?php echo $is_active; ?>">
      <?php echo $genre_name; ?>
    </div>
  </a>
<?php endforeach; ?>
</div>

<?php
if ($selected_genre) {
  $genre_safe = $conn->real_escape_string($selected_genre);
  echo "<h2 class='latest-heading'>Genre: " . htmlspecialchars($selected_genre) . "</h2>";

  // Find manga that contain this genre
  $sql_manga = "SELECT * FROM manga WHERE category LIKE '%$genre_safe%' ORDER BY title ASC";
  $manga_result = $conn->query($sql_manga);

  if ($manga_result && $manga_result->num_rows > 0):
?>
  <div class="manga-container">
    <div class="manga-grid">
  <?php while ($manga = $manga_result->fetch_assoc()):
      $title = strtolower(trim($manga['title']));
      $filename = str_replace(' ', '_', $title) . '.jpeg';
      $category = htmlspecialchars($manga['category']);
      $status = htmlspecialchars($manga['status']);
      $statusClass = strtolower(str_replace(' ', '-', $status));
      
      // Split genres for display
      $genres = array_map('trim', explode(',', $manga['category']));
      $genre_badges = '';
      foreach ($genres as $g) {
          if (!empty($g)) {
              $genre_badges .= '<span class="category-badge">' . htmlspecialchars($g) . '</span> ';
          }
      }
  ?>
    <div class="manga-card" data-id="<?= $manga['id'] ?>" data-title="<?= strtolower($manga['title']) ?>" data-category="<?= strtolower($manga['category']) ?>" data-status="<?= strtolower($manga['status']) ?>">
      <img src="images/<?= htmlspecialchars($filename) ?>" alt="<?= htmlspecialchars($manga['title']) ?>" onerror="this.src='images/default.jpg'" loading="lazy">
      <h3><?php echo htmlspecialchars($manga['title']); ?></h3>
      <div class="status-label <?= $statusClass ?>">Status: <?= $status ?></div>
      <div class="genre-badges"><?= $genre_badges ?></div>
      <?php if (!empty($manga['last_chapter'])): ?>
        <p>Last Chapter: <?= htmlspecialchars($manga['last_chapter']) ?></p>
      <?php endif; ?>
      <?php if (!empty($manga['read_link'])): ?>
        <p><a href="<?= htmlspecialchars($manga['read_link']) ?>" target="_blank">Read Here</a></p>
      <?php endif; ?>
      <?php if (!empty($manga['external_links'])):
        $links = json_decode($manga['external_links'], true);
        if ($links && is_array($links)): ?>
        <div class="external-links"><strong>Read on:</strong><ul>
          <?php foreach ($links as $link): ?>
            <li><a href="<?= htmlspecialchars($link['url']) ?>" target="_blank"><?= htmlspecialchars($link['name']) ?></a></li>
          <?php endforeach; ?>
        </ul></div>
      <?php endif; endif; ?>
      <div class="card-actions">
        <a href="edit.php?id=<?= $manga['id'] ?>" class="btn">✏️ Edit</a>
        <a href="delete.php?id=<?= $manga['id'] ?>" class="btn" onclick="return confirm('Delete this manga?')">🗑️ Delete</a>
      </div>
    </div>
  <?php endwhile; ?>
    </div>
  </div>
<?php else: ?>
  <div class="manga-container">
    <p style="color:#eee; text-align:center; margin-top: 2rem;">No manga found in this genre.</p>
  </div>
<?php endif;
} else {
    echo '<div class="manga-container"><p style="color:#eee; text-align:center; margin-top: 2rem;">Please select a genre above to see the manga.</p></div>';
}

$conn->close();
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // --- Intersection Observer for revealing cards on scroll ---
  // This fixes the issue where cards would disappear after loading.
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
    document.querySelectorAll('.manga-card').forEach(card => observer.observe(card));
  } else {
    // Fallback for older browsers
    document.querySelectorAll('.manga-card').forEach(card => card.classList.add('visible'));
  }

  // --- Theme switcher logic for consistency ---
  const themes = ['theme-red', 'theme-white', 'theme-green', 'theme-blue', 'theme-purple'];
  const themeIcons = ['🌑', '☀️', '🌿', '🌊', '🟣'];
  const darkModeToggle = document.getElementById('darkModeToggle');

  function applyTheme(theme) {
    document.body.style.transition = 'all 0.5s ease';
    document.body.classList.remove(...themes);
    document.body.classList.add(theme);
    localStorage.setItem('themeMode', theme);
    if (darkModeToggle) {
      const idx = themes.indexOf(theme);
      darkModeToggle.innerText = themeIcons[idx] || '🎨';
    }
  }

  const savedTheme = localStorage.getItem('themeMode') || 'theme-red';
  applyTheme(savedTheme);

  if (darkModeToggle) {
    darkModeToggle.addEventListener('click', function () {
      const currentTheme = Array.from(document.body.classList).find(c => themes.includes(c)) || themes[0];
      const idx = (themes.indexOf(currentTheme) + 1) % themes.length;
      applyTheme(themes[idx]);
    });
  }
});
</script>
</body>
</html>
