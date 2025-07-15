<?php
include 'db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
  header("Location: home.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $status = $conn->real_escape_string($_POST['status']);
  $category = $conn->real_escape_string($_POST['category']);
  $sql = "UPDATE manga SET status='$status', category='$category' WHERE id=$id";
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
  <meta charset="UTF-8">
  <title>Edit Manga</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="edit-container">
  <h1>Edit Manga</h1>
  <form method="POST">
    <p>Title: <strong><?php echo htmlspecialchars($manga['title']); ?></strong></p>
    <label>Status:</label>
    <select name="status">
      <option value="will read" <?php if ($manga['status'] == 'will read') echo 'selected'; ?>>Will Read</option>
      <option value="currently reading" <?php if ($manga['status'] == 'currently reading') echo 'selected'; ?>>Currently Reading</option>
      <option value="stopped" <?php if ($manga['status'] == 'stopped') echo 'selected'; ?>>Stopped</option>
      <option value="finished" <?php if ($manga['status'] == 'finished') echo 'selected'; ?>>Finished</option>
    </select>
    <label>Category:</label>
    <input type="text" name="category" value="<?php echo htmlspecialchars($manga['category']); ?>" required>
    <button type="submit">Update</button>
  </form>
  <p><a href="home.php" class="button">← Back</a></p>
</div>

</body>
</html>
