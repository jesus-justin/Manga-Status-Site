<?php
include 'db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
  header("Location: home.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $status = $conn->real_escape_string($_POST['status']);
  $category = trim($_POST['category']);
  $read_link = $conn->real_escape_string(trim($_POST['read_link']));
  $last_chapter = $conn->real_escape_string(trim($_POST['last_chapter']));

  if ($category === '') {
    $category = 'Uncategorized';
  }

  $sql = "UPDATE manga 
          SET status='$status', 
              category='$category',
              read_link=" . ($read_link !== '' ? "'$read_link'" : "NULL") . ",
              last_chapter=" . ($status === 'currently reading' && $last_chapter !== '' ? "'$last_chapter'" : "NULL") . "
          WHERE id=$id";

  if ($conn->query($sql) === TRUE) {
    header("Location: home.php");
    exit();
  } else {
    echo "Error: " . $conn->error;
  }
}

$sql = "SELECT * FROM manga WHERE id=$id";
$result = $conn->query($sql);
$manga = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Manga</title>
  <link rel="stylesheet" href="edit.css">
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
  </script>
</head>
<body>

<nav>
  <div class="logo">MangaLibrary</div>
  <ul>
    <li><a href="home.php">Home</a></li>
    <li><a href="browse.php">Browse</a></li>
  </ul>
</nav>

<main>
  <div class="edit-container">
    <h1>Edit Manga</h1>
    <form method="POST">
      <div class="form-group">
        <label>Title:</label>
        <div class="readonly-title"><?php echo htmlspecialchars($manga['title']); ?></div>
      </div>

      <div class="form-group">
        <label>Status:</label>
        <select name="status" onchange="toggleChapterField()">
          <option value="will read" <?php if ($manga['status'] == 'will read') echo 'selected'; ?>>Will Read</option>
          <option value="currently reading" <?php if ($manga['status'] == 'currently reading') echo 'selected'; ?>>Currently Reading</option>
          <option value="stopped" <?php if ($manga['status'] == 'stopped') echo 'selected'; ?>>Stopped</option>
          <option value="finished" <?php if ($manga['status'] == 'finished') echo 'selected'; ?>>Finished</option>
        </select>
      </div>

      <div class="form-group">
        <label>Category:</label>
        <select name="category">
          <?php
          $categories = ['Uncategorized', 'Action', 'Romance', 'Horror'];
          foreach ($categories as $cat) {
            $selected = ($cat == $manga['category']) ? 'selected' : '';
            echo "<option value=\"$cat\" $selected>$cat</option>";
          }
          ?>
        </select>
      </div>

      <div class="form-group">
        <label>Read Link:</label>
        <input type="url" name="read_link" value="<?php echo htmlspecialchars($manga['read_link']); ?>" placeholder="Link to read manga">
      </div>

      <div class="form-group" id="chapterField" style="display: <?php echo $manga['status'] === 'currently reading' ? 'block' : 'none'; ?>;">
        <label>Last Chapter:</label>
        <input type="text" name="last_chapter" value="<?php echo htmlspecialchars($manga['last_chapter']); ?>" placeholder="Last Chapter">
      </div>

      <div class="form-actions">
        <button type="submit" class="primary-btn">Update</button>
        <a href="home.php" class="secondary-btn">Cancel</a>
      </div>
    </form>
  </div>
</main>

</body>
</html>
