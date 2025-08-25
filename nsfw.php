<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'auth.php';
require_once 'db.php';

$auth = new Auth($conn);
if (!$auth->isLoggedIn()) {
    header('Location: login_fixed.php');
    exit();
}

// Pagination variables
$manga_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // Ensure page is at least 1

// Get total number of NSFW manga using prepared statement
$count_sql = "SELECT COUNT(*) as total FROM manga WHERE category LIKE '%ecchi%' OR category LIKE '%adult%'";
$count_result = $conn->query($count_sql);
$total_manga = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_manga / $manga_per_page);

// Calculate offset for SQL query
$offset = ($current_page - 1) * $manga_per_page;

// Fetch NSFW manga for current page using prepared statement
$sql = "SELECT * FROM manga WHERE category LIKE '%ecchi%' OR category LIKE '%adult%' ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $manga_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>NSFW Manga Library</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="home.css">
</head>
<body>
<nav>
  <div class="logo">MangaLibrary</div>
  <ul>
    <li><a href="home.php">Home</a></li>
    <li><a href="browse.php">Browse</a></li>
    <li><a href="user_progress.php">My Progress</a></li>
    <li><a href="nsfw.php">NSFW Mode</a></li>
  </ul>
</nav>

<h2>NSFW Manga Collection</h2>
<div class="manga-container">
  <div class="manga-grid">
<?php
if ($result->num_rows > 0):
  while ($row = $result->fetch_assoc()):
    $title = strtolower(trim($row['title']));
    $filename = str_replace(' ', '_', $title) . '.jpeg';
    $category = htmlspecialchars($row['category']);
    $status = htmlspecialchars($row['status']);
    $statusClass = strtolower(str_replace(' ', '-', $status));
?>
  <div class="manga-card" data-id="<?= $row['id'] ?>" data-title="<?= strtolower($row['title']) ?>" data-category="<?= strtolower($row['category']) ?>" data-status="<?= strtolower($row['status']) ?>">
    <img src="images/<?= htmlspecialchars($filename) ?>" alt="<?= htmlspecialchars($row['title']) ?>" onerror="this.src='images/default.jpg'" loading="lazy">
    <h3><?= htmlspecialchars($row['title']) ?></h3>
    <div class="status-label <?= $statusClass ?>">Status: <?= $status ?></div>
  </div>
<?php endwhile; else: ?>
  <p style="color:#eee; text-align:center;">No NSFW manga added yet.</p>
<?php endif; ?>
  </div>
</div>

<?php
if ($total_pages > 1): ?>
<div class="pagination">
  <?php if ($current_page > 1): ?>
    <a href="?page=<?= $current_page - 1 ?>" class="pagination-btn">Previous</a>
  <?php endif; ?>

  <?php
  $start_page = max(1, $current_page - 2);
  $end_page = min($total_pages, $current_page + 2);
  
  if ($start_page > 1): ?>
    <a href="?page=1" class="pagination-btn">1</a>
    <?php if ($start_page > 2): ?>
      <span class="pagination-ellipsis">...</span>
    <?php endif; ?>
  <?php endif; ?>
  
  <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
    <?php if ($i == $current_page): ?>
      <span class="pagination-btn current"><?= $i ?></span>
    <?php else: ?>
      <a href="?page=<?= $i ?>" class="pagination-btn"><?= $i ?></a>
    <?php endif; ?>
  <?php endfor; ?>
  
  <?php if ($end_page < $total_pages): ?>
    <?php if ($end_page < $total_pages - 1): ?>
      <span class="pagination-ellipsis">...</span>
    <?php endif; ?>
    <a href="?page=<?= $total_pages ?>" class="pagination-btn"><?= $total_pages ?></a>
  <?php endif; ?>

  <?php if ($current_page < $total_pages): ?>
    <a href="?page=<?= $current_page + 1 ?>" class="pagination-btn">Next</a>
  <?php endif; ?>
</div>
<?php endif; ?>

</body>
</html>
<?php
$conn->close();
?>
