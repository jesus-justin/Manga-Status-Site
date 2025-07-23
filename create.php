<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add Manga - Manga Library</title>
  <link rel="stylesheet" href="style.css">
  <script>
    function toggleChapterField() {
      const status = document.querySelector('select[name="status"]').value;
      const chapterField = document.getElementById('chapterField');
      if (status === 'currently reading') {
        chapterField.style.display = 'block';
      } else {
        chapterField.style.display = 'none';
      }
    }
    document.addEventListener('DOMContentLoaded', function() {
      toggleChapterField();
    });
  </script>
</head>
<body>
<nav>
  <div class="logo">MangaLibrary</div>
  <ul>
    <li><a href="home.php">Home</a></li>
    <li><a href="browse.php">Browse</a></li>
  </ul>
  <button id="darkModeToggle" title="Toggle dark mode">🌙</button>
</nav>
<div class="add-form">
  <form action="add.php" method="POST">
    <input type="text" name="title" placeholder="Enter Manga Title" required>
    <select name="status" onchange="toggleChapterField()" required>
      <option value="will read">Will Read</option>
      <option value="currently reading">Currently Reading</option>
      <option value="stopped">Stopped</option>
      <option value="finished">Finished</option>
    </select>
    <select name="category">
      <option value="">Uncategorized</option>
      <option value="Action">Action</option>
      <option value="Romance">Romance</option>
      <option value="Horror">Horror</option>
    </select>
    <input type="url" name="read_link" placeholder="Link to read manga (optional)">
    <div id="chapterField" style="display:none;">
      <input type="text" name="last_chapter" placeholder="Last Chapter Read">
    </div>
    <button type="submit">Add Manga</button>
  </form>
</div>
</body>
</html> 