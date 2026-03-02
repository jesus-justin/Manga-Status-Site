<?php
require_once 'db.php';
require_once 'auth.php';

$auth = new Auth($conn);
$auth->requireLogin();

try {
    // Get all non-NSFW genres by splitting the category field using prepared statement
    $sql_all = "SELECT category FROM manga WHERE category IS NOT NULL AND category != '' AND category NOT LIKE '%ecchi%' AND category NOT LIKE '%adult%'";
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
    $selected_status = $_GET['status'] ?? 'all';
    $selected_sort = $_GET['sort'] ?? 'title_asc';
} catch (Exception $e) {
    error_log("Error fetching genres: " . $e->getMessage());
    $unique_genres = [];
    $selected_genre = null;
    $selected_status = 'all';
    $selected_sort = 'title_asc';
}
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
      color: var(--ink);
    }
  </style>
</head>
<body>

<nav>
  <div class="logo">
    <a href="home.php" style="color: inherit; text-decoration: none; cursor: pointer;">MangaLibrary</a>
  </div>
  <ul>
    <li><a href="home.php">Home</a></li>
    <li><a href="browse.php">Browse</a></li>
  </ul>
  <div class="nav-actions">
    <button id="darkModeToggle" title="Toggle theme">🎨</button>
  </div>
</nav>

<h1 class="page-heading">Browse by Genre</h1>

<div class="library-controls" aria-label="Browse controls" style="max-width: 1100px; margin: 0 auto 1rem;">
  <input type="text" id="genreSearch" class="library-input" placeholder="Filter genres..." aria-label="Filter genres">
  <form method="GET" action="browse.php" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
    <?php if ($selected_genre): ?>
      <input type="hidden" name="genre" value="<?php echo htmlspecialchars($selected_genre); ?>">
    <?php endif; ?>
    <select name="status" class="library-select" aria-label="Filter status">
      <option value="all" <?php echo $selected_status === 'all' ? 'selected' : ''; ?>>All statuses</option>
      <option value="currently reading" <?php echo $selected_status === 'currently reading' ? 'selected' : ''; ?>>Currently reading</option>
      <option value="finished" <?php echo $selected_status === 'finished' ? 'selected' : ''; ?>>Finished</option>
      <option value="will read" <?php echo $selected_status === 'will read' ? 'selected' : ''; ?>>Will read</option>
      <option value="dropped" <?php echo $selected_status === 'dropped' ? 'selected' : ''; ?>>Dropped</option>
    </select>
    <select name="sort" class="library-select" aria-label="Sort manga">
      <option value="title_asc" <?php echo $selected_sort === 'title_asc' ? 'selected' : ''; ?>>Title A-Z</option>
      <option value="title_desc" <?php echo $selected_sort === 'title_desc' ? 'selected' : ''; ?>>Title Z-A</option>
      <option value="newest" <?php echo $selected_sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
      <option value="updated" <?php echo $selected_sort === 'updated' ? 'selected' : ''; ?>>Recently Updated</option>
    </select>
    <button type="submit" class="btn">Apply</button>
  </form>
  <a href="browse.php" class="btn" style="text-align:center; line-height: 42px;">Show All Genres</a>
</div>

<div class="category-filters">
  <a href="browse.php"><div class="filter-pill <?php echo empty($selected_genre) ? 'active' : ''; ?>">All</div></a>
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
    try {
        echo "<h2 class='latest-heading'>Genre: " . htmlspecialchars($selected_genre) . "</h2>";

        // Pagination variables for genre filtering
        $manga_per_page = 10;
        $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $current_page = max(1, $current_page);
        $offset = ($current_page - 1) * $manga_per_page;

        // Count total manga for selected genre
        $search_pattern = "%" . $selected_genre . "%";
        $count_sql = "SELECT COUNT(*) as total FROM manga WHERE category LIKE ? AND category NOT LIKE '%ecchi%' AND category NOT LIKE '%adult%'";
        $count_types = "s";
        $count_params = [$search_pattern];

        if ($selected_status !== 'all') {
          $count_sql .= " AND status = ?";
          $count_types .= "s";
          $count_params[] = $selected_status;
        }

        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param($count_types, ...$count_params);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_manga = $count_result->fetch_assoc()['total'];
        $total_pages = ceil($total_manga / $manga_per_page);

        // Fetch manga for current page with pagination
        $allowed_order = [
          'title_asc' => 'title ASC',
          'title_desc' => 'title DESC',
          'newest' => 'id DESC',
          'updated' => 'updated_at DESC'
        ];
        $order_by = $allowed_order[$selected_sort] ?? 'title ASC';

        $sql_manga = "SELECT * FROM manga WHERE category LIKE ? AND category NOT LIKE '%ecchi%' AND category NOT LIKE '%adult%'";
        $types = "s";
        $params = [$search_pattern];

        if ($selected_status !== 'all') {
          $sql_manga .= " AND status = ?";
          $types .= "s";
          $params[] = $selected_status;
        }

        $sql_manga .= " ORDER BY " . $order_by . " LIMIT ? OFFSET ?";
        $types .= "ii";
        $params[] = $manga_per_page;
        $params[] = $offset;

        $stmt = $conn->prepare($sql_manga);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $manga_result = $stmt->get_result();

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
        <p><a href="<?= htmlspecialchars($manga['read_link']) ?>" target="_blank" rel="noopener noreferrer">Read Here</a></p>
      <?php endif; ?>
      <?php if (!empty($manga['external_links'])):
        $links = json_decode($manga['external_links'], true);
        if ($links && is_array($links)): ?>
        <div class="external-links"><strong>Read on:</strong><ul>
          <?php foreach ($links as $link): ?>
            <li><a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($link['name']) ?></a></li>
          <?php endforeach; ?>
        </ul></div>
      <?php endif; endif; ?>
      <div class="card-actions">
        <a href="edit.php?id=<?= $manga['id'] ?>" class="btn">✏️ Edit</a>
        <form method="POST" action="delete.php" onsubmit="return confirm('Delete this manga?');" style="display:inline;">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->getCsrfToken()) ?>">
          <input type="hidden" name="id" value="<?= (int)$manga['id'] ?>">
          <button type="submit" class="btn">🗑️ Delete</button>
        </form>
      </div>
    </div>
  <?php endwhile; ?>
    </div>
  </div>
<?php else: ?>
  <div class="manga-container">
    <p style="color:#eee; text-align:center; margin-top: 2rem;">No manga found in this genre.</p>
  </div>
<?php endif; ?>

<!-- Pagination Controls -->
<?php if ($total_pages > 1): ?>
  <div class="pagination">
    <!-- Previous button -->
    <?php if ($current_page > 1): ?>
      <a href="?genre=<?= urlencode($selected_genre) ?>&status=<?= urlencode($selected_status) ?>&sort=<?= urlencode($selected_sort) ?>&page=<?= $current_page - 1 ?>" class="pagination-btn">Previous</a>
    <?php endif; ?>

    <!-- Page numbers -->
    <?php
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);

    // Show first page if not in range
    if ($start_page > 1): ?>
      <a href="?genre=<?= urlencode($selected_genre) ?>&status=<?= urlencode($selected_status) ?>&sort=<?= urlencode($selected_sort) ?>&page=1" class="pagination-btn">1</a>
      <?php if ($start_page > 2): ?>
        <span class="pagination-ellipsis">...</span>
      <?php endif; ?>
    <?php endif; ?>

    <!-- Page number buttons -->
    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
      <?php if ($i == $current_page): ?>
        <span class="pagination-btn current"><?= $i ?></span>
      <?php else: ?>
        <a href="?genre=<?= urlencode($selected_genre) ?>&status=<?= urlencode($selected_status) ?>&sort=<?= urlencode($selected_sort) ?>&page=<?= $i ?>" class="pagination-btn"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor; ?>

    <!-- Show last page if not in range -->
    <?php if ($end_page < $total_pages): ?>
      <?php if ($end_page < $total_pages - 1): ?>
        <span class="pagination-ellipsis">...</span>
      <?php endif; ?>
      <a href="?genre=<?= urlencode($selected_genre) ?>&status=<?= urlencode($selected_status) ?>&sort=<?= urlencode($selected_sort) ?>&page=<?= $total_pages ?>" class="pagination-btn"><?= $total_pages ?></a>
    <?php endif; ?>

    <!-- Next button -->
    <?php if ($current_page < $total_pages): ?>
      <a href="?genre=<?= urlencode($selected_genre) ?>&status=<?= urlencode($selected_status) ?>&sort=<?= urlencode($selected_sort) ?>&page=<?= $current_page + 1 ?>" class="pagination-btn">Next</a>
    <?php endif; ?>
  </div>
<?php endif; ?>
<?php
    } catch (Exception $e) {
        error_log("Error fetching manga for genre: " . $e->getMessage());
        echo '<div class="manga-container"><p style="color:#eee; text-align:center; margin-top: 2rem;">Error loading manga for this genre.</p></div>';
    }
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

  const genreSearch = document.getElementById('genreSearch');
  if (genreSearch) {
    genreSearch.addEventListener('input', function () {
      const query = this.value.toLowerCase().trim();
      document.querySelectorAll('.category-filters a').forEach((pillLink) => {
        const text = pillLink.textContent.toLowerCase();
        pillLink.style.display = !query || text.includes(query) ? '' : 'none';
      });
    });
  }
});
</script>
</body>
</html>
