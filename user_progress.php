<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'auth.php';
require_once 'db.php';

$auth = new Auth($conn);
if (!$auth->isLoggedIn()) {
    header('Location: login_fixed.php');
    exit();
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reading Progress - MangaLibrary</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="home.css">
    <style>
        .progress-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .progress-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            color: #007bff;
            font-weight: bold;
        }
        
        .progress-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            background: #444;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .filter-btn.active {
            background: #007bff;
        }
        
        .progress-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .progress-card {
            background: #2a2a2a;
            border-radius: 8px;
            padding: 20px;
            position: relative;
        }
        
        .progress-cover {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .progress-info h3 {
            margin: 0 0 10px 0;
            color: #fff;
        }
        
        .progress-bar {
            background: #444;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            background: #007bff;
            height: 100%;
            transition: width 0.3s;
        }
        
        .progress-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .progress-btn {
            padding: 5px 10px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-bottom: 10px;
        }
        
        .status-reading { background: #28a745; }
        .status-completed { background: #17a2b8; }
        .status-plan { background: #ffc107; color: #000; }
        .status-dropped { background: #dc3545; }
        .status-hold { background: #6c757d; }
        
        .add-progress-form {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #ccc;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            background: #444;
            border: 1px solid #666;
            border-radius: 4px;
            color: #fff;
        }
    </style>
</head>
<body>
<nav>
    <div class="logo">My Reading Progress</div>
    <ul>
        <li><a href="home.php">Home</a></li>
        <li><a href="browse.php">Browse</a></li>
        <li><a href="user_progress.php">My Progress</a></li>
    </ul>
</nav>

<div class="progress-container">
    <div class="progress-header">
        <h1>My Reading Progress</h1>
        <button onclick="showAddForm()" class="filter-btn">+ Add New Progress</button>
    </div>

    <?php
    // Get user statistics
    $stats_sql = "SELECT 
        COUNT(CASE WHEN status = 'reading' THEN 1 END) as reading,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
        COUNT(CASE WHEN status = 'plan_to_read' THEN 1 END) as plan_to_read,
        COUNT(CASE WHEN status = 'dropped' THEN 1 END) as dropped,
        COUNT(CASE WHEN status = 'on_hold' THEN 1 END) as on_hold,
        AVG(rating) as avg_rating
    FROM user_reading_progress 
    WHERE user_id = ?";
    
    $stmt = $conn->prepare($stats_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    ?>

    <div class="progress-stats">
        <div class="stat-card">
            <div class="stat-number"><?= $stats['reading'] ?? 0 ?></div>
            <div>Currently Reading</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $stats['completed'] ?? 0 ?></div>
            <div>Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $stats['plan_to_read'] ?? 0 ?></div>
            <div>Plan to Read</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $stats['dropped'] ?? 0 ?></div>
            <div>Dropped</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $stats['on_hold'] ?? 0 ?></div>
            <div>On Hold</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($stats['avg_rating'] ?? 0, 1) ?></div>
            <div>Avg Rating</div>
        </div>
    </div>

    <!-- Add Progress Form -->
    <div id="addProgressForm" class="add-progress-form" style="display: none;">
        <h3>Add New Reading Progress</h3>
        <form action="save_progress.php" method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="manga_id">Select Manga</label>
                    <select name="manga_id" required>
                        <option value="">Choose a manga...</option>
                        <?php
                        $manga_sql = "SELECT id, title FROM manga ORDER BY title";
                        $manga_result = $conn->query($manga_sql);
                        while ($manga = $manga_result->fetch_assoc()) {
                            echo "<option value='{$manga['id']}'>{$manga['title']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" required>
                        <option value="plan_to_read">Plan to Read</option>
                        <option value="reading">Currently Reading</option>
                        <option value="completed">Completed</option>
                        <option value="dropped">Dropped</option>
                        <option value="on_hold">On Hold</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="current_chapter">Current Chapter</label>
                    <input type="text" name="current_chapter" placeholder="e.g., Chapter 15">
                </div>
                <div class="form-group">
                    <label for="total_chapters">Total Chapters</label>
                    <input type="text" name="total_chapters" placeholder="e.g., 50">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="rating">Rating (1-10)</label>
                    <input type="number" name="rating" min="1" max="10" step="0.1" placeholder="8.5">
                </div>
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" name="start_date">
                </div>
            </div>
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea name="notes" rows="3" placeholder="Personal notes about this manga..."></textarea>
            </div>
            <button type="submit" class="filter-btn">Save Progress</button>
            <button type="button" onclick="hideAddForm()" class="filter-btn" style="background: #6c757d;">Cancel</button>
        </form>
    </div>

    <?php
    // Get user's reading progress
    $progress_sql = "SELECT 
        urp.*,
        m.title,
        m.category,
        m.status as manga_status,
        m.read_link
    FROM user_reading_progress urp
    JOIN manga m ON urp.manga_id = m.id
    WHERE urp.user_id = ?
    ORDER BY urp.updated_at DESC";
    
    $stmt = $conn->prepare($progress_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $progress_result = $stmt->get_result();
    ?>

    <div class="progress-filters">
        <button class="filter-btn active" onclick="filterProgress('all')">All</button>
        <button class="filter-btn" onclick="filterProgress('reading')">Reading</button>
        <button class="filter-btn" onclick="filterProgress('completed')">Completed</button>
        <button class="filter-btn" onclick="filterProgress('plan_to_read')">Plan to Read</button>
        <button class="filter-btn" onclick="filterProgress('dropped')">Dropped</button>
        <button class="filter-btn" onclick="filterProgress('on_hold')">On Hold</button>
    </div>

    <div class="progress-grid" id="progressGrid">
        <?php while ($progress = $progress_result->fetch_assoc()): ?>
            <div class="progress-card" data-status="<?= $progress['status'] ?>">
                <img src="images/<?= str_replace(' ', '_', strtolower($progress['title'])) ?>.jpeg" 
                     alt="<?= htmlspecialchars($progress['title']) ?>" 
                     class="progress-cover"
                     onerror="this.src='images/default.jpg'">
                
                <div class="progress-info">
                    <h3><?= htmlspecialchars($progress['title']) ?></h3>
                    <span class="status-badge status-<?= $progress['status'] ?>">
                        <?= ucfirst(str_replace('_', ' ', $progress['status'])) ?>
                    </span>
                    
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $progress['progress_percentage'] ?>%"></div>
                    </div>
                    
                    <p>
                        Chapter <?= htmlspecialchars($progress['current_chapter'] ?? '0') ?> 
                        of <?= htmlspecialchars($progress['total_chapters'] ?? 'Unknown') ?>
                    </p>
                    
                    <?php if ($progress['rating']): ?>
                        <p>Rating: <?= $progress['rating'] ?>/10</p>
                    <?php endif; ?>
                    
                    <div class="progress-actions">
                        <button class="progress-btn" onclick="editProgress(<?= $progress['id'] ?>)">Edit</button>
                        <button class="progress-btn" onclick="deleteProgress(<?= $progress['id'] ?>)">Delete</button>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
function showAddForm() {
    document.getElementById('addProgressForm').style.display = 'block';
}

function hideAddForm() {
    document.getElementById('addProgressForm').style.display = 'none';
}

function filterProgress(status) {
    const cards = document.querySelectorAll('.progress-card');
    const buttons = document.querySelectorAll('.filter-btn');
    
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    cards.forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function editProgress(id) {
    // Implementation for editing progress
    alert('Edit functionality will be implemented');
}

function deleteProgress(id) {
    if (confirm('Are you sure you want to delete this progress?')) {
        window.location.href = 'delete_progress.php?id=' + id;
    }
}
</script>
</body>
</html>
<?php
$conn->close();
?>
