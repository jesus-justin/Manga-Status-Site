<?php
require_once 'db.php';
require_once 'auth.php';

$auth = new Auth($conn);
$auth->requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Change Manga - Manga Library</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <div class="logo">
    <a href="home.php" style="color: inherit; text-decoration: none; cursor: pointer;">MangaLibrary</a>
  </div>
  <ul>
    <li><a href="home.php">Home</a></li>
    <li><a href="browse.php">Browse</a></li>
  </ul>
  <button id="darkModeToggle" title="Toggle dark mode">🌙</button>
  <button id="settingsBtn" title="Settings" type="button">⚙️</button>
  <div class="settings-tab-container" id="settingsTabContainer" style="display:none;">
    <div class="menu">
      <div class="menu__item" onclick="window.location='create.php'" title="Create">
        📝
        <span class="tab-label">Create</span>
      </div>
      <div class="menu__item" onclick="window.location='change.php'" title="Change">
        ✏️
        <span class="tab-label">Change</span>
      </div>
    </div>
  </div>
</nav>
<h2 class="latest-heading">Edit or Delete Manga</h2>
<div class="manga-grid">
<?php
$stmt = $conn->prepare("SELECT * FROM manga ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0):
  while ($row = $result->fetch_assoc()):
    $title = strtolower(trim($row['title']));
    $filename = str_replace(' ', '_', $title) . '.jpeg';
    $category = htmlspecialchars($row['category']);
    $status = htmlspecialchars($row['status']);
    $statusClass = strtolower(str_replace(' ', '-', $status));
    $categoryBadge = $category ? '<span class="category-badge">' . $category . '</span>' : '';
?>
  <div class="manga-card" data-id="<?php echo $row['id']; ?>" data-title="<?php echo strtolower($row['title']); ?>" data-category="<?php echo strtolower($row['category']); ?>" data-status="<?php echo strtolower($row['status']); ?>">
    <img src="images/<?php echo htmlspecialchars($filename); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
    <div class="status-label <?php echo $statusClass; ?>">Status: <?php echo $status; ?></div>
    <?php echo $categoryBadge; ?>
    <?php if (!empty($row['last_chapter'])): ?>
      <p>Last Chapter: <?php echo htmlspecialchars($row['last_chapter']); ?></p>
    <?php endif; ?>
    <?php if (!empty($row['read_link'])): ?>
      <p><a href="<?php echo htmlspecialchars($row['read_link']); ?>" target="_blank" rel="noopener noreferrer">Read Here</a></p>
    <?php endif; ?>
    <?php if (!empty($row['external_links'])): ?>
      <?php $links = json_decode($row['external_links'], true); if ($links && is_array($links)): ?>
        <div class="external-links">
          <strong>Read on:</strong>
          <ul>
            <?php foreach ($links as $link): ?>
              <li><a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($link['name']); ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    <?php endif; ?>
    <div class="card-actions">
      <a href="edit.php?id=<?php echo $row['id']; ?>" class="button">Edit</a>
      <form method="POST" action="delete.php" onsubmit="return confirm('Delete this manga?');" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($auth->getCsrfToken()); ?>">
        <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
        <button type="submit" class="button">Delete</button>
      </form>
    </div>
  </div>
<?php endwhile; else: ?>
  <p style="color:#eee; text-align:center;">No manga added yet.</p>
<?php endif; ?>
</div>
<script>
// Settings tab menu toggle for click only (no hover)
(function() {
  const settingsBtn = document.getElementById('settingsBtn');
  const tabContainer = document.getElementById('settingsTabContainer');
  let tabOpen = false;
  if (settingsBtn && tabContainer) {
    settingsBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      tabOpen = !tabOpen;
      tabContainer.style.display = tabOpen ? 'block' : 'none';
    });
    document.addEventListener('click', function() {
      if (tabOpen) {
        tabContainer.style.display = 'none';
        tabOpen = false;
      }
    });
  }
})();
</script>
</body>
</html> 