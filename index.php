<?php include 'db.php'; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Manga Library</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Manga Library</h1>

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

    <h2>My Manga List</h2>
    <table>
        <tr>
            <th>Title</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>

        <?php
        $sql = "SELECT * FROM manga";
        $result = $conn->query($sql);

        if ($result->num_rows > 0):
            while($row = $result->fetch_assoc()):
        ?>
            <tr>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td>
                    <a href="edit.php?id=<?php echo $row['id']; ?>">Edit</a>
                    <a href="delete.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this manga?');">Delete</a>
                </td>
            </tr>
        <?php
            endwhile;
        else:
        ?>
            <tr><td colspan="3">No manga added yet.</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>
