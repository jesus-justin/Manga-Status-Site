<?php
include 'db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $conn->real_escape_string($_POST['status']);
    $sql = "UPDATE manga SET status='$status' WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        header("Location: index.php");
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
<html>
<head>
    <title>Edit Manga</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&family=Noto+Serif+JP:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="edit-container">
    <h1>Edit Manga Status</h1>
    <p class="subtitle">マンガライブラリ</p>

    <form method="POST">
        <p>Title: <strong><?php echo htmlspecialchars($manga['title']); ?></strong></p>
        <label for="status">Status:</label>
        <select name="status">
            <option value="will read" <?php if($manga['status']=='will read') echo 'selected'; ?>>Will Read</option>
            <option value="currently reading" <?php if($manga['status']=='currently reading') echo 'selected'; ?>>Currently Reading</option>
            <option value="stopped" <?php if($manga['status']=='stopped') echo 'selected'; ?>>Stopped</option>
            <option value="finished" <?php if($manga['status']=='finished') echo 'selected'; ?>>Finished</option>
        </select>
        <button type="submit">Update</button>
    </form>

    <p><a href="index.php" class="button">← Back to Library</a></p>
</div>

</body>
</html>
