<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'user_collection.php';

$auth = new Auth($conn);
$auth->requireLogin();

$userCollection = new UserCollection($conn);
$user = $auth->getCurrentUser();
$stats = $userCollection->getCollectionStats($user['id']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $userCollection->addToCollection(
                    $user['id'],
                    $_POST['manga_id'],
                    $_POST['status'],
                    $_POST['rating'] ?? null,
                    $_POST['progress'] ?? 0
                );
                break;
            case 'update_progress':
                $userCollection->updateProgress(
                    $user['id'],
                    $_POST['manga_id'],
                    $_POST['progress'],
                    $_POST['last_chapter'] ?? null
                );
                break;
            case 'remove':
                $userCollection->removeFromCollection($user['id'], $_POST['manga_id']);
                break;
        }
        header('Location: dashboard.php');
        exit();
    }
}

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
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
        
        .collection-filters {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid #444;
            border-radius: 20px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: #007bff;
            border-color: #007bff;
        }
        
        .manga-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .collection-item {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            position: relative;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: #007bff;
            transition: width 0.3s;
        }
        
        .rating-stars {
            color: #ffd700;
            margin: 10px 0;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-small {
            padding: 5px 10px;
            font-size: 0.9em;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">MangaLibrary</div>
        <ul>
            <li><a href="home.php">Home</a></li>
            <li><a href="browse.php">Browse</a></li>
            <li><a href="dashboard.php" class="active">My Dashboard</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
        <div class="user-info">
            Welcome, <?= htmlspecialchars($user['username']) ?>!
        </div>
    </nav>
    
    <div class="dashboard-container">
        <div class="welcome-banner">
            <h1>Welcome to Your Dashboard</h1>
            <p>Manage your personal manga collection and track your reading progress</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div>Total Manga</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['want_to_read'] ?></div>
                <div>Want to Read</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['currently_reading'] ?></div>
                <div>Currently Reading</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['finished'] ?></div>
                <div>Finished</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($stats['average_rating'], 1) ?></div>
                <div>Average Rating</div>
            </div>
        </div>
        
        <div class="collection-filters">
            <a href="?status=all" class="filter-btn <?= !isset($_GET['status']) || $_GET['status'] == 'all' ? 'active' : '' ?>">All</a>
            <a href="?status=want to read" class="filter-btn <?= isset($_GET['status']) && $_GET['status'] == 'want to read' ? 'active' : '' ?>">Want to Read</a>
            <a href="?status=currently reading" class="filter-btn <?= isset($_GET['status']) && $_GET['status'] == 'currently reading' ? 'active' : '' ?>">Currently Reading</a>
            <a href="?status=finished" class="filter-btn <?= isset($_GET['status']) && $_GET['status'] == 'finished' ? 'active' : '' ?>">Finished</a>
            <a href="?status=stopped" class="filter-btn <?= isset($_GET['status']) && $_GET['status'] == 'stopped' ? 'active' : '' ?>">Stopped</a>
        </div>
        
        <div class="manga-grid">
            <?php
            $status_filter = isset($_GET['status']) ? $_GET['status'] : null;
            $filtered_collection = $status_filter ? 
                array_filter($collection, fn($item) => $item['status'] == $status_filter) : 
                $collection;
            
            if (empty($filtered_collection)):
            ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <h3>No manga in this category yet</h3>
                    <p><a href="browse.php">Browse manga to add to your collection</a></p>
                </div>
            <?php else: ?>
                <?php foreach ($filtered_collection as $item): ?>
                    <div class="collection-item">
                        <h3><?= htmlspecialchars($item['title']) ?></h3>
                        <div class="status-label"><?= htmlspecialchars($item['status']) ?></div>
                        
                        <?php if ($item['status'] == 'currently reading'): ?>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= min(100, ($item['progress'] / max(1, $item['last_chapter'] ?: 1)) * 100 ?>%"></div>
                            </div>
                            <div>Progress: <?= $item['progress'] ?> / <?= $item['last_chapter'] ?: '?' ?> chapters</div>
                        <?php endif; ?>
                        
                        <?php if ($item['rating']): ?>
                            <div class="rating-stars">
                                Rating: <?= str_repeat('★', floor($item['rating'])) . str_repeat('☆', 10 - floor($item['rating'])) ?>
                                <?= $item['rating'] ?>/10
                            </div>
                        <?php endif; ?>
                        
                        <div class="action-buttons">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="manga_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="action" value="update_status">
                                <select name="status" onchange="this.form.submit()">
                                    <option value="want to read" <?= $item['status'] == 'want to read' ? 'selected' : '' ?>>Want to Read</option>
                                    <option value="currently reading" <?= $item['status'] == 'currently reading' ? 'selected' : '' ?>>Currently Reading</option>
                                    <option value="finished" <?= $item['status'] == 'finished' ? 'selected' : '' ?>>Finished</option>
                                    <option value="stopped" <?= $item['status'] == 'stopped' ? 'selected' : '' ?>>Stopped</option>
                                </select>
                            </form>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="manga_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" class="btn-small btn-danger" onclick="return confirm('Remove from collection?')">Remove</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
