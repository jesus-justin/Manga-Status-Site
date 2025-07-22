<?php include 'db.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manga Library</title>
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
    // --- Real-time Search Functionality ---
    document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.getElementById('searchInput');
      if (searchInput) {
        searchInput.addEventListener('input', function() {
          const query = this.value.toLowerCase();
          document.querySelectorAll('.manga-card').forEach(card => {
            const title = card.getAttribute('data-title');
            const category = card.getAttribute('data-category');
            const status = card.getAttribute('data-status');
            if ((title && title.includes(query)) || (category && category.includes(query)) || (status && status.includes(query))) {
              card.style.display = '';
            } else {
              card.style.display = 'none';
            }
          });
        });
      }
      // --- Random Manga Button ---
      const randomBtn = document.getElementById('randomMangaBtn');
      if (randomBtn) {
        const mangaCards = Array.from(document.querySelectorAll('.manga-card'));
        const mangaList = mangaCards.map(card => ({
          id: card.getAttribute('data-id'),
          title: card.getAttribute('data-title'),
          category: card.getAttribute('data-category'),
          readLink: card.querySelector('a[target="_blank"]') ? card.querySelector('a[target="_blank"]').href : ''
        }));
        randomBtn.addEventListener('click', function() {
          if (mangaList.length === 0) return;
          // Remove previous highlight
          mangaCards.forEach(card => card.classList.remove('highlight-manga'));
          const random = mangaList[Math.floor(Math.random() * mangaList.length)];
          if (random.readLink) {
            document.getElementById('randomMangaResult').innerHTML =
              `<strong>Read:</strong> <a href='${random.readLink}' target='_blank' style='color:#fff;text-decoration:underline;'>${random.title}</a> [${random.category}]`;
          } else {
            document.getElementById('randomMangaResult').innerHTML =
              `<strong>Highlighted:</strong> ${random.title} [${random.category}]`;
            // Highlight the card and scroll to it
            const card = document.querySelector(`.manga-card[data-id='${random.id}']`);
            if (card) {
              card.classList.add('highlight-manga');
              card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
          }
        });
      }
      // --- Theme Mode Toggle (Multi-color) ---
      const themes = ['theme-red', 'theme-white', 'theme-green', 'theme-blue', 'theme-purple'];
      const themeIcons = ['🌑', '☀️', '🌿', '🌊', '🟣'];
      const darkModeToggle = document.getElementById('darkModeToggle');
      function applyTheme(theme) {
        document.body.classList.remove(...themes);
        document.body.classList.add(theme);
        localStorage.setItem('themeMode', theme);
        // Update button icon
        if (darkModeToggle) {
          const idx = themes.indexOf(theme);
          darkModeToggle.innerText = themeIcons[idx] || '🎨';
        }
      }
      // On load, set theme
      const savedTheme = localStorage.getItem('themeMode') || 'theme-red';
      applyTheme(savedTheme);
      if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
          let idx = themes.indexOf(document.body.classList.value.split(' ').find(c => themes.includes(c)));
          idx = (idx + 1) % themes.length;
          applyTheme(themes[idx]);
        });
      }
    });
  </script>
</head>
<body>

<nav>
  <div class="logo">MangaLibrary</div>
  <ul>
    <li><a href="home.php">Home</a></li>
    <li><a href="browse.php">Browse</a></li>
  </ul>
  <div class="search-box">
    <input type="text" id="searchInput" placeholder="Search manga...">
  </div>
  <button id="darkModeToggle" title="Toggle dark mode">🌙</button>
</nav>

<div class="banner">
  <div class="banner-content">
    <h1>FEATURED: MANGA LIBRARY</h1>
    <p>Manage your collection easily with cover images, categories, and reading links.</p>
    <button id="randomMangaBtn">🎲 Random Manga</button>
    <div id="randomMangaResult" style="margin-top:10px;"></div>
  </div>
</div>

<div class="add-form">
  <form action="add.php" method="POST">
    <input type="text" name="title" placeholder="Enter Manga Title" required>
    <select name="status" onchange="toggleChapterField()" required>
      <option value="will read">Will Read</option>
      <option value="currently reading">Currently Reading</option>
      <option value="stopped">Stopped</option>
      <option value="finished">Finished</option>
    </select>
    <select name="category">
      <option value="">Uncategorized</option>
      <option value="Action">Action</option>
      <option value="Romance">Romance</option>
      <option value="Horror">Horror</option>
    </select>
    <input type="url" name="read_link" placeholder="Link to read manga (optional)">
    <div id="chapterField" style="display:none;">
      <input type="text" name="last_chapter" placeholder="Last Chapter Read">
    </div>
    <button type="submit">Add Manga</button>
  </form>
</div>

<h2 class="latest-heading">Latest Manga Updates</h2>

<div class="manga-grid">
<?php
$sql = "SELECT * FROM manga ORDER BY id DESC";
$result = $conn->query($sql);
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
      <p><a href="<?php echo htmlspecialchars($row['read_link']); ?>" target="_blank">Read Here</a></p>
    <?php endif; ?>
    <?php if (!empty($row['external_links'])): ?>
      <?php $links = json_decode($row['external_links'], true); if ($links && is_array($links)): ?>
        <div class="external-links">
          <strong>Read on:</strong>
          <ul>
            <?php foreach ($links as $link): ?>
              <li><a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank"><?php echo htmlspecialchars($link['name']); ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    <?php endif; ?>
    <div class="card-actions">
      <a href="edit.php?id=<?php echo $row['id']; ?>" class="button">Edit</a>
      <a href="delete.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this manga?');" class="button">Delete</a>
    </div>
  </div>
<?php endwhile; else: ?>
  <p style="color:#eee; text-align:center;">No manga added yet.</p>
<?php endif; ?>
</div>

</body>
</html>
