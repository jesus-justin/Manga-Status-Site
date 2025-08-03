<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manga Library</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="home.css">
  <style>
    .scroll-progress {
      position: fixed;
      top: 0;
      left: 0;
      height: 5px;
      background: #f00;
      width: 0%;
      z-index: 9999;
    }

    .btn {
      padding: 5px 10px;
      background-color: #444;
      color: white;
      border: none;
      border-radius: 4px;
      text-decoration: none;
      margin-right: 5px;
    }

    .btn:hover {
      background-color: #666;
    }

    .card-actions {
      margin-top: 10px;
    }

    #randomMangaResult {
      margin-top: 10px;
    }

    /* Pagination styles */
    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      margin: 30px 0;
      flex-wrap: wrap;
    }

    .pagination-btn {
      display: inline-block;
      padding: 8px 16px;
      margin: 0 4px;
      background-color: #444;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      transition: background-color 0.3s;
    }

    .pagination-btn:hover {
      background-color: #666;
    }

    .pagination-btn.current {
      background-color: #007bff;
      cursor: default;
    }

    .pagination-ellipsis {
      padding: 8px 4px;
      color: #ccc;
    }
  </style>
  <script>
    function toggleChapterField() {
      const status = document.querySelector('select[name="status"]').value;
      const chapterField = document.getElementById('chapterField');
      chapterField.style.display = (status === 'currently reading') ? 'block' : 'none';
    }

    function updateScrollProgress() {
      const scrollTop = window.pageYOffset;
      const docHeight = document.body.offsetHeight - window.innerHeight;
      const scrollPercent = (scrollTop / docHeight) * 100;
      document.querySelector('.scroll-progress').style.width = scrollPercent + '%';
    }

    function showToast(message, duration = 3000) {
      const toast = document.createElement('div');
      toast.className = 'toast';
      toast.textContent = message;
      document.body.appendChild(toast);
      setTimeout(() => toast.classList.add('show'), 100);
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
      }, duration);
    }

    document.addEventListener('DOMContentLoaded', function () {
      function createParticles() {
        const container = document.createElement('div');
        container.className = 'particles-container';
        document.body.appendChild(container);
        for (let i = 0; i < 12; i++) {
          const particle = document.createElement('div');
          particle.className = 'particle';
          particle.style.left = Math.random() * 100 + '%';
          particle.style.top = Math.random() * 100 + '%';
          particle.style.width = Math.random() * 8 + 4 + 'px';
          particle.style.height = particle.style.width;
          particle.style.animationDelay = Math.random() * 6 + 's';
          particle.style.animationDuration = (Math.random() * 2 + 3) + 's';
          container.appendChild(particle);
        }
      }
      createParticles();

      const progressBar = document.createElement('div');
      progressBar.className = 'scroll-progress';
      document.body.appendChild(progressBar);
      window.addEventListener('scroll', updateScrollProgress);

      if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              entry.target.classList.add('visible');
            }
          });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
        document.querySelectorAll('.manga-card').forEach(card => observer.observe(card));
      } else {
        document.querySelectorAll('.manga-card').forEach(card => card.classList.add('visible'));
      }

      const searchInput = document.getElementById('searchInput');
      if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function () {
          clearTimeout(searchTimeout);
          searchTimeout = setTimeout(() => {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.manga-card').forEach(card => {
              const title = card.getAttribute('data-title');
              const category = card.getAttribute('data-category');
              const status = card.getAttribute('data-status');
              card.style.display = (title.includes(query) || category.includes(query) || status.includes(query)) ? '' : 'none';
            });
          }, 200);
        });
      }

      window.enhancedRandomManga = function () {
        const randomBtn = document.getElementById('randomMangaBtn');
        if (!randomBtn) return;
        randomBtn.innerHTML = '<span class="loading-spinner"></span> Finding...';
        randomBtn.disabled = true;
        setTimeout(() => {
          const mangaCards = Array.from(document.querySelectorAll('.manga-card'));
          if (!mangaCards.length) {
            randomBtn.textContent = '🎲 Random Manga';
            randomBtn.disabled = false;
            return;
          }
          mangaCards.forEach(card => card.classList.remove('highlight-manga'));
          const random = mangaCards[Math.floor(Math.random() * mangaCards.length)];
          random.classList.add('highlight-manga');
          random.scrollIntoView({ behavior: 'smooth', block: 'center' });
          const resultDiv = document.getElementById('randomMangaResult');
          resultDiv.style.opacity = '0';
          resultDiv.style.transform = 'scale(0.8)';
          setTimeout(() => {
            randomBtn.textContent = '🎲 Random Manga';
            randomBtn.disabled = false;
            resultDiv.style.opacity = '1';
            resultDiv.style.transform = 'scale(1)';
          }, 400);
        }, 700);
      };

      const randomBtn = document.getElementById('randomMangaBtn');
      if (randomBtn) {
        randomBtn.addEventListener('click', window.enhancedRandomManga);
      }

      const themes = ['theme-red', 'theme-white', 'theme-green', 'theme-blue', 'theme-purple'];
      const themeIcons = ['🌑', '☀️', '🌿', '🌊', '🟣'];
      const darkModeToggle = document.getElementById('darkModeToggle');

      function applyTheme(theme) {
        document.body.style.transition = 'all 0.5s ease';
        document.body.classList.remove(...themes);
        document.body.classList.add(theme);
        localStorage.setItem('themeMode', theme);
        if (darkModeToggle) {
          const idx = themes.indexOf(theme);
          darkModeToggle.innerText = themeIcons[idx] || '🎨';
        }
      }

      const savedTheme = localStorage.getItem('themeMode') || 'theme-red';
      applyTheme(savedTheme);
      if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function () {
          let idx = themes.indexOf(document.body.classList.value.split(' ').find(c => themes.includes(c)));
          idx = (idx + 1) % themes.length;
          applyTheme(themes[idx]);
        });
      }

      const floatingActions = document.createElement('div');
      floatingActions.className = 'floating-actions';
      floatingActions.innerHTML = `
        <button class="floating-btn" onclick="window.location='create.php'" title="Add New Manga">➕</button>
        <button class="floating-btn" onclick="window.location='browse.php'" title="Browse All">📚</button>
      `;
      document.body.appendChild(floatingActions);

      const mangaCards = document.querySelectorAll('.manga-card');
      const categories = new Set();
      mangaCards.forEach(card => {
        const category = card.getAttribute('data-category');
        if (category) categories.add(category);
      });

      if (categories.size > 0) {
        const filterContainer = document.createElement('div');
        filterContainer.className = 'category-filters';
        filterContainer.innerHTML = `
          <div class="filter-pill active" onclick="filterByCategory('all', this)">All</div>
          ${Array.from(categories).map(cat =>
            `<div class="filter-pill" onclick="filterByCategory('${cat}', this)">${cat}</div>`
          ).join('')}
        `;
        document.querySelector('.latest-heading').after(filterContainer);
      }

      window.filterByCategory = function (category, el) {
        const cards = document.querySelectorAll('.manga-card');
        document.querySelectorAll('.filter-pill').forEach(pill => pill.classList.remove('active'));
        if (el) el.classList.add('active');
        cards.forEach(card => {
          const cardCategory = card.getAttribute('data-category');
          card.style.display = (category === 'all' || cardCategory === category) ? '' : 'none';
        });
      };

      const statsContainer = document.createElement('div');
      statsContainer.className = 'stats-container';
      statsContainer.innerHTML = `
        <div class="stat-item">
          <span class="stat-number">${mangaCards.length}</span>
          <div>Total Manga</div>
        </div>
        <div class="stat-item">
          <span class="stat-number">${new Set(Array.from(mangaCards).map(card => card.getAttribute('data-category'))).size}</span>
          <div>Categories</div>
        </div>
        <div class="stat-item">
          <span class="stat-number">${Array.from(mangaCards).filter(card => card.getAttribute('data-status') === 'currently reading').length}</span>
          <div>Reading Now</div>
        </div>
      `;
      document.querySelector('.banner').after(statsContainer);
    });
  </script>
</head>
<body>

<div class="scroll-progress"></div>

<nav>
  <div class="logo">MangaLibrary</div>
  <ul>
    <li><a href="home.php">Home</a></li>
    <li><a href="browse.php">Browse</a></li>
  </ul>
  <div class="search-box">
    <input type="text" id="searchInput" placeholder="Search manga..." aria-label="Search manga">
  </div>
  <div class="nav-actions">
    <button id="darkModeToggle" title="Toggle dark mode">🌙</button>
    
  </div>
  <div class="settings-tab-container" id="settingsTabContainer" style="display:none;">
    <div class="menu">
      <div class="menu__item" onclick="window.location='create.php'" title="Create">📝<span class="tab-label">Create</span></div>
      <div class="menu__item" onclick="window.location='change.php'" title="Change">✏️<span class="tab-label">Change</span></div>
    </div>
  </div>
</nav>

<div class="banner">
  <div class="banner-content">
    <h1>Welcome to your MANGA LIBRARY</h1>
    <p>Manage your collection easily with cover images, categories, and reading links.</p>
    <button id="randomMangaBtn" aria-label="Random Manga">🎲 Random Manga</button>
    <div id="randomMangaResult"></div>
  </div>
</div>

<h2 class="latest-heading">Latest Manga Updates</h2>

<div class="manga-grid">
<?php
// Pagination variables
$manga_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // Ensure page is at least 1

// Get total number of manga
$count_sql = "SELECT COUNT(*) as total FROM manga";
$count_result = $conn->query($count_sql);
$total_manga = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_manga / $manga_per_page);

// Calculate offset for SQL query
$offset = ($current_page - 1) * $manga_per_page;

// Fetch manga for current page
$sql = "SELECT * FROM manga ORDER BY id DESC LIMIT $manga_per_page OFFSET $offset";
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
  <div class="manga-card" data-id="<?= $row['id'] ?>" data-title="<?= strtolower($row['title']) ?>" data-category="<?= strtolower($row['category']) ?>" data-status="<?= strtolower($row['status']) ?>">
    <img src="images/<?= htmlspecialchars($filename) ?>" alt="<?= htmlspecialchars($row['title']) ?>" onerror="this.src='images/default.jpg'" loading="lazy">
    <h3><?= htmlspecialchars($row['title']) ?></h3>
    <div class="status-label <?= $statusClass ?>">Status: <?= $status ?></div>
    <?= $categoryBadge ?>
    <?php if (!empty($row['last_chapter'])): ?>
      <p>Last Chapter: <?= htmlspecialchars($row['last_chapter']) ?></p>
    <?php endif; ?>
    <?php if (!empty($row['read_link'])): ?>
      <p><a href="<?= htmlspecialchars($row['read_link']) ?>" target="_blank">Read Here</a></p>
    <?php endif; ?>
    <?php if (!empty($row['external_links'])):
      $links = json_decode($row['external_links'], true);
      if ($links && is_array($links)): ?>
      <div class="external-links"><strong>Read on:</strong><ul>
        <?php foreach ($links as $link): ?>
          <li><a href="<?= htmlspecialchars($link['url']) ?>" target="_blank"><?= htmlspecialchars($link['name']) ?></a></li>
        <?php endforeach; ?>
      </ul></div>
    <?php endif; endif; ?>
    <div class="card-actions">
      <a href="edit.php?id=<?= $row['id'] ?>" class="btn">✏️ Edit</a>
      <a href="delete.php?id=<?= $row['id'] ?>" class="btn" onclick="return confirm('Delete this manga?')">🗑️ Delete</a>
    </div>
  </div>
<?php endwhile; else: ?>
  <p style="color:#eee; text-align:center;">No manga added yet.</p>
<?php endif; ?>
</div>

<!-- Pagination Controls -->
<?php if ($total_pages > 1): ?>
<div class="pagination">
  <!-- Previous button -->
  <?php if ($current_page > 1): ?>
    <a href="?page=<?= $current_page - 1 ?>" class="pagination-btn">Previous</a>
  <?php endif; ?>

  <!-- Page numbers -->
  <?php
  $start_page = max(1, $current_page - 2);
  $end_page = min($total_pages, $current_page + 2);
  
  // Show first page if not in range
  if ($start_page > 1): ?>
    <a href="?page=1" class="pagination-btn">1</a>
    <?php if ($start_page > 2): ?>
      <span class="pagination-ellipsis">...</span>
    <?php endif; ?>
  <?php endif; ?>
  
  <!-- Page number buttons -->
  <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
    <?php if ($i == $current_page): ?>
      <span class="pagination-btn current"><?= $i ?></span>
    <?php else: ?>
      <a href="?page=<?= $i ?>" class="pagination-btn"><?= $i ?></a>
    <?php endif; ?>
  <?php endfor; ?>
  
  <!-- Show last page if not in range -->
  <?php if ($end_page < $total_pages): ?>
    <?php if ($end_page < $total_pages - 1): ?>
      <span class="pagination-ellipsis">...</span>
    <?php endif; ?>
    <a href="?page=<?= $total_pages ?>" class="pagination-btn"><?= $total_pages ?></a>
  <?php endif; ?>

  <!-- Next button -->
  <?php if ($current_page < $total_pages): ?>
    <a href="?page=<?= $current_page + 1 ?>" class="pagination-btn">Next</a>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php
$conn->close();
?>
</div>

<script>
(function () {
  const settingsBtn = document.getElementById('settingsBtn');
  const tabContainer = document.getElementById('settingsTabContainer');
  let tabOpen = false;
  if (settingsBtn && tabContainer) {
    settingsBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      tabOpen = !tabOpen;
      tabContainer.style.display = tabOpen ? 'block' : 'none';
    });
    document.addEventListener('click', function () {
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
