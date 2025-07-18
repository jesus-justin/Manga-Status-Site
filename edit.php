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
  </script>
</head>
<body>

<div class="edit-container">
  <h1>Edit Manga</h1>
  <form method="POST">
    <p>Title: <strong><?php echo htmlspecialchars($manga['title']); ?></strong></p>

    <label>Status:</label>
    <select name="status" onchange="toggleChapterField()">
      <option value="will read" <?php if ($manga['status'] == 'will read') echo 'selected'; ?>>Will Read</option>
      <option value="currently reading" <?php if ($manga['status'] == 'currently reading') echo 'selected'; ?>>Currently Reading</option>
      <option value="stopped" <?php if ($manga['status'] == 'stopped') echo 'selected'; ?>>Stopped</option>
      <option value="finished" <?php if ($manga['status'] == 'finished') echo 'selected'; ?>>Finished</option>
    </select>

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

    <label>Read Link:</label>
    <input type="url" name="read_link" value="<?php echo htmlspecialchars($manga['read_link']); ?>" placeholder="Link to read manga">

    <div id="chapterField" style="display: <?php echo $manga['status'] === 'currently reading' ? 'block' : 'none'; ?>;">
      <label>Last Chapter:</label>
      <input type="text" name="last_chapter" value="<?php echo htmlspecialchars($manga['last_chapter']); ?>" placeholder="Last Chapter">
    </div>

    <button type="submit">Update</button>
  </form>
  <p><a href="home.php" class="button">← Back</a></p>
</div>

</body>
</html>
