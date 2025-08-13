<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'user_collection.php';

$auth = new Auth($conn);
$auth->requireLogin();

$userCollection = new UserCollection($conn);
$user = $auth->getCurrentUser();
$stats = $userCollection->getCollectionStats($user['id']);

// Get user's collection
$collection = $userCollection->getUserCollection($user['id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Manga Library</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="home.css">
</head>
<body>
    <nav>
        <div class="logo">MangaLibrary</div>
        <ul>
            <li><a href="home.php">Home</a></li>
            <li><a href="browse.php">Browse</a></li>
            <li><a href="dashboard.php">My Dashboard</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    
    <div class="dashboard-container">
        <h1>Welcome to Your Dashboard</h1>
        
        <div class="stats">
            <h2>Your Collection Stats</h2>
            <ul>
                <li>Total Manga: <?= $stats['total'] ?></li>
                <li>Currently Reading: <?= $stats['currently reading'] ?></li>
                <li>Finished: <?= $stats['finished'] ?></li>
                <li>Want to Read: <?= $stats['want to read'] ?></li>
            </ul>
        </div>
        
        <h2>Your Collection</h2>
        <ul>
            <?php foreach ($collection as $item): ?>
                <li>
                    <h3><?= htmlspecialchars($item['title']) ?></h3>
                    <p>Status: <?= htmlspecialchars($item['status']) ?></p>
                    <p>Progress: <?= $item['progress'] ?>/<?= $item['last_chapter'] ?: '?' ?></p>
                    <?php if ($item['rating']): ?>
                        <p>Rating: <?= $item['rating'] ?>/10</p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>
