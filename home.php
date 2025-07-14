<?php include 'db.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manga Library</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&family=Noto+Serif+JP:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
  <div class="logo">MangaLibrary</div>
  <ul>
    <li><a href="home.php">Home</a></li>
    <li><a href="#">New Releases</a></li>
    <li><a href="#">Popular</a></li>
    <li><a href="#">Browse</a></li>
  </ul>
  <div class="search-box">
    <input type="text" placeholder="Search manga...">
  </div>
</nav>

<div class="banner">
  <div class="banner-content">
    <h1>FEATURED: MANGA LIBRARY</h1>
    <p>Manage your collection easily with cover images.</p>
    <button>Read now</button>
  </div>
</div>

<div class="add-form">
  <form action="add.php" method="POST">
    <input type="text" name="title" placeholder="Enter Manga Title" required>
    <select name="status">
      <option value="will read">Will Read</option>
      <option value="currently reading">Currently Reading</option>
      <option value="stopped">Stopped</option>
      <option value="finished">Finished</option>
    </select>
    <button type="submit">Add Manga</button>
  </form>
</div>

<h2 class="latest-heading">Latest Manga Updates</h2>

<div class="manga-grid">
<?php
$sql = "SELECT * FROM manga";
$result = $conn->query($sql);

if ($result->num_rows > 0):
  while ($row = $result->fetch_assoc()):
    // ✅ Make filename: lowercase → spaces to underscores → .jpeg
    $title = strtolower(trim($row['title']));
    $filename = str_replace(' ', '_', $title) . '.jpeg';
?>
  <div class="manga-card">
    <img src="images/<?php echo htmlspecialchars($filename); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
    <p>Status: <?php echo htmlspecialchars($row['status']); ?></p>
    <a href="edit.php?id=<?php echo $row['id']; ?>" class="button">Edit</a>
    <a href="delete.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this manga?');" class="button">Delete</a>
  </div>
<?php endwhile; else: ?>
  <p style="color:#eee; text-align:center;">No manga added yet.</p>
<?php endif; ?>
</div>

</body>
</html>
