<?php
include 'db.php';

// Get unique categories
$sql = "SELECT DISTINCT category FROM manga ORDER BY category ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Browse Manga</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Browse Categories</h1>

<ul>
<?php while ($row = $result->fetch_assoc()): ?>
  <li><a href="browse.php?category=<?php echo urlencode($row['category']); ?>">
    <?php echo htmlspecialchars($row['category']); ?>
  </a></li>
<?php endwhile; ?>
</ul>

<?php
if (isset($_GET['category'])) {
  $cat = $conn->real_escape_string($_GET['category']);
  echo "<h2>Category: " . htmlspecialchars($cat) . "</h2>";

  $sql2 = "SELECT * FROM manga WHERE category='$cat'";
  $result2 = $conn->query($sql2);

  if ($result2->num_rows > 0):
    while ($manga = $result2->fetch_assoc()):
      $title = strtolower(trim($manga['title']));
      $filename = str_replace(' ', '_', $title) . '.jpeg';
?>
  <div class="manga-card">
    <img src="images/<?php echo htmlspecialchars($filename); ?>" alt="<?php echo htmlspecialchars($manga['title']); ?>">
    <h3><?php echo htmlspecialchars($manga['title']); ?></h3>
    <p>Status: <?php echo htmlspecialchars($manga['status']); ?></p>
  </div>
<?php endwhile; else: ?>
  <p>No manga in this category.</p>
<?php endif;
}
?>

<p><a href="home.php" class="button">← Back Home</a></p>

</body>
</html>
