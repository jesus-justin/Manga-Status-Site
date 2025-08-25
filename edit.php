<?php
include 'db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
  header("Location: home.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $status = $_POST['status'];
  
  // Handle multiple genres - convert array to comma-separated string
  $categories = isset($_POST['category']) ? $_POST['category'] : [];
  $category = !empty($categories) ? implode(', ', $categories) : 'Uncategorized';
  
  $read_link = trim($_POST['read_link']);
  $last_chapter = trim($_POST['last_chapter']);

  // Use prepared statement for UPDATE query
  $sql = "UPDATE manga 
          SET status = ?, 
              category = ?,
              read_link = ?,
              last_chapter = ?
          WHERE id = ?";
  
  $stmt = $conn->prepare($sql);
  
  // Handle NULL values for optional fields
  $read_link_value = ($read_link !== '') ? $read_link : NULL;
  $last_chapter_value = ($status === 'currently reading' && $last_chapter !== '') ? $last_chapter : NULL;
  
  $stmt->bind_param("ssssi", $status, $category, $read_link_value, $last_chapter_value, $id);

  if ($stmt->execute()) {
    header("Location: home.php");
    exit();
  } else {
    echo "Error: " . $stmt->error;
  }
}

// Use prepared statement for SELECT query
$sql = "SELECT * FROM manga WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$manga = $result->fetch_assoc();

// Split categories into array for checkbox handling
$selected_categories = explode(', ', $manga['category']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Manga</title>
  <link rel="stylesheet" href="edit.css">
  <style>
    .genre-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
      gap: 10px;
      margin: 10px 0;
      padding: 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      background-color: rgba(255, 255, 255, 0.05);
    }
    
    .genre-item {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .genre-item input[type="checkbox"] {
      margin: 0;
      cursor: pointer;
    }
    
    .genre-item label {
      font-size: 14px;
      cursor: pointer;
    }
  </style>
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
        <label>Genres:</label>
        <div class="genre-container">
          <?php
          $available_categories = ['Action', 'Adult', 'Adventure', 'Comedy', 'Drama', 'Ecchi', 'Fantasy', 'Gore', 'Horror', 'Isekai', 'Magic', 'Mecha', 'Mystery', 'Romance', 'School', 'Sci-Fi', 'Slice of Life', 'Supernatural', 'Tragedy'];
          foreach ($available_categories as $cat) {
            $checked = in_array($cat, $selected_categories) ? 'checked' : '';
            echo "<div class='genre-item'>
                    <input type='checkbox' id='cat_$cat' name='category[]' value='$cat' $checked>
                    <label for='cat_$cat'>$cat</label>
                  </div>";
          }
          ?>
        </div>
      </div>

      <div class="form-group">
        <label>Read Link:</label>
        <input type="url" name="read_link" value="<?php echo htmlspecialchars($manga['read_link']); ?>" placeholder="Link to read manga">
      </div>

      <div class="form-group" id="chapterField" style="display: <?php echo $manga['status'] === 'currently reading' ? 'block' : 'none'; ?>;">
        <label>Last Chapter:</label>
        <input type="text" name="last_chapter" value="<?php echo htmlspecialchars($manga['last_chapter']); ?>" placeholder="Last Chapter Read">
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
