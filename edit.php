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
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&display=swap');

    body {
      margin: 0;
      font-family: 'Noto Sans JP', sans-serif;
      background: #111;
      color: #f0f0f0;
    }

    nav {
      background: #1a1a1a;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.2rem 2rem;
      border-bottom: 1px solid #333;
    }

    nav .logo {
      font-size: 1.7rem;
      font-weight: bold;
      color: #fff;
    }

    nav ul {
      list-style: none;
      display: flex;
      gap: 2rem;
      margin: 0;
      padding: 0;
    }

    nav ul li a {
      text-decoration: none;
      color: #ccc;
      font-weight: 500;
      transition: color 0.3s;
    }

    nav ul li a:hover {
      color: #fff;
    }

    main {
      display: flex;
      justify-content: center;
      padding: 4rem 1rem;
    }

    .edit-container {
      background: linear-gradient(145deg, #1f1f1f, #292929);
      max-width: 550px;
      width: 100%;
      padding: 2.5rem;
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(0,0,0,0.5);
      margin: 4rem auto;
    }

    .edit-container h1 {
      text-align: center;
      margin-bottom: 2rem;
      font-size: 1.8rem;
      color: #ff4d4d;
    }

    .edit-container form {
      display: flex;
      flex-direction: column;
    }

    .form-group {
      margin-bottom: 1.8rem;
    }

    .readonly-title {
      background: #2b2b2b;
      padding: 0.8rem 1rem;
      border-radius: 6px;
      font-size: 1rem;
    }

    .edit-container label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: bold;
      color: #ddd;
    }

    .edit-container input[type="text"],
    .edit-container input[type="url"],
    .edit-container select {
      width: 100%;
      padding: 0.8rem 1rem;
      border: none;
      border-radius: 6px;
      background: #2c2c2c;
      color: #f0f0f0;
      transition: box-shadow 0.2s;
    }

    .edit-container input:focus,
    .edit-container select:focus {
      outline: none;
      box-shadow: 0 0 5px #ff4d4d;
    }

    .form-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .primary-btn {
      background: linear-gradient(135deg, #ff4d4d, #e13c3c);
      border: none;
      padding: 0.9rem 2rem;
      border-radius: 6px;
      color: #fff;
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
      text-decoration: none;
      text-align: center;
      font-weight: bold;
    }

    .primary-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(255,77,77,0.4);
    }

    .secondary-btn {
      background: #333;
      color: #ccc;
      padding: 0.9rem 2rem;
      border-radius: 6px;
      text-decoration: none;
      text-align: center;
      transition: background 0.3s, color 0.3s, transform 0.2s;
      font-weight: bold;
    }

    .secondary-btn:hover {
      background: #444;
      color: #fff;
      transform: translateY(-2px);
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
