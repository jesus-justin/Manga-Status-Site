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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manga Library</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="home.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="animations.js"></script>
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
      // Check for success parameter and show SweetAlert
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('success') === '1') {
        Swal.fire({
          title: 'Manga Added!',
          text: 'Your manga has been successfully added to the library.',
          icon: 'success',
          confirmButtonText: 'Great!',
          confirmButtonColor: '#007bff',
          timer: 3000,
          timerProgressBar: true
        });
        
        // Clean the URL
        window.history.replaceState({}, document.title, window.location.pathname);
      }

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
            let hasResults = false;
            
            document.querySelectorAll('.manga-card').forEach(card => {
              const title = card.getAttribute('data-title');
              const category = card.getAttribute('data-category');
              const status = card.getAttribute('data-status');
              const shouldShow = (title.includes(query) || category.includes(query) || status.includes(query));
              card.style.display = shouldShow ? '' : 'none';
              if (shouldShow) hasResults = true;
            });

            // Show SweetAlert if no results found
            if (query && !hasResults) {
              Swal.fire({
                title: 'No Results',
                text: `No manga found for "${query}"`,
                icon: 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#007bff',
                timer: 3000,
                timerProgressBar: true
              });
            }
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

      // Extract unique individual genres from manga cards
      const genresSet = new Set();
      mangaCards.forEach(card => {
        const categoryStr = card.getAttribute('data-category');
        if (categoryStr) {
          const genres = categoryStr.split(',').map(g => g.trim().toLowerCase());
          genres.forEach(g => {
            if (g) genresSet.add(g);
          });
        }
      });

      if (genresSet.size > 0) {
        const filterContainer = document.createElement('div');
        filterContainer.className = 'category-filters';
        filterContainer.innerHTML = `
          <div class="filter-pill active" onclick="filterByGenre('all', this)">All</div>
          ${Array.from(genresSet).map(genre =>
            `<div class="filter-pill" onclick="filterByGenre('${genre}', this)">${genre.charAt(0).toUpperCase() + genre.slice(1)}</div>`
          ).join('')}
        `;
        document.querySelector('.latest-heading').after(filterContainer);
      }

      window.filterByGenre = function (genre, el) {
        const cards = document.querySelectorAll('.manga-card');
        document.querySelectorAll('.filter-pill').forEach(pill => pill.classList.remove('active'));
        if (el) el.classList.add('active');
        cards.forEach(card => {
          const categoryStr = card.getAttribute('data-category').toLowerCase();
          card.style.display = (genre === 'all' || categoryStr.includes(genre)) ? '' : 'none';
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

    function confirmLogout() {
      Swal.fire({
        title: 'Logout?',
        text: 'Are you sure you want to logout?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, logout!',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'logout.php';
        }
      });
    }
  </script>
</head>
<body>

<div class="scroll-progress"></div>

<nav>
  <div class="logo">
    <a href="home.php" style="color: inherit; text-decoration: none; cursor: pointer;">MangaLibrary</a>
  </div>
  <ul>
    <li><a href="home.php">Home</a></li>
    <li><a href="browse.php">Browse</a></li>
    <li><a href="user_progress.php">My Progress</a></li>
    <li><a href="nsfw.php">NSFW Mode</a></li>
  </ul>
  <div class="search-box">
    <input type="text" id="searchInput" placeholder="Search manga..." aria-label="Search manga">
  </div>
  <div class="nav-actions">
    <button id="darkModeToggle" title="Toggle dark mode">🌙</button>
    <div class="auth-buttons-container">
      <a href="login_fixed.php" class="auth-btn" title="Login" style="display: block; margin-bottom: 8px; padding: 10px; font-size: 20px;">🔐</a>
      <a href="register.php" class="auth-btn" title="Register" style="display: block; margin-bottom: 8px; padding: 10px; font-size: 20px;">👤</a>
      <a href="#" onclick="confirmLogout()" class="auth-btn" title="Logout" style="display: block; padding: 10px; font-size: 20px;">🚪</a>
    </div>
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

<div class="manga-container">
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

// Fetch manga for current page using prepared statement
$sql = "SELECT * FROM manga ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $manga_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0):
  while ($row = $result->fetch_assoc()):
    $title = strtolower(trim($row['title']));
    $filename = str_replace(' ', '_', $title) . '.jpeg';
    $category = htmlspecialchars($row['category']);
    $status = htmlspecialchars($row['status']);
    $statusClass = strtolower(str_replace(' ', '-', $status));
    // Split categories by comma and create individual badges
    $categories = explode(',', $category);
    $categoryBadges = '';
    foreach ($categories as $cat) {
        $cat = trim($cat);
        if (!empty($cat)) {
            $categoryBadges .= '<span class="category-badge">' . htmlspecialchars($cat) . '</span>';
        }
    }
?>
  <div class="manga-card" data-id="<?= $row['id'] ?>" data-title="<?= strtolower($row['title']) ?>" data-category="<?= strtolower($row['category']) ?>" data-status="<?= strtolower($row['status']) ?>">
    <img src="images/<?= htmlspecialchars($filename) ?>" alt="<?= htmlspecialchars($row['title']) ?>" onerror="this.src='images/default.jpg'" loading="lazy">
    <h3><?= htmlspecialchars($row['title']) ?></h3>
    <div class="status-label <?= $statusClass ?>">Status: <?= $status ?></div>
    <div class="category-badges"><?= $categoryBadges ?></div>
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
</div>

<?php
$conn->close();
?>

<script>
(function () {
  const settingsBtn = document.getElementById('settingsBtn');
  const tabContainer = document.getElementById('settingsTabContainer');
  let tabOpen = false;
  if (settingsBtn && tabContainer) {
  }
})();
</script>

<footer class="site-footer">
  <div class="footer-content">
    <div class="footer-section">
      <h4>MangaLibrary</h4>
      <p>Your personal manga collection manager</p>
    </div>
    <div class="footer-section">
      <h4>Legal</h4>
      <p>All manga content and images are property of their respective owners. This site is for personal collection management only.</p>
    </div>
    <div class="footer-section">
      <h4>Contact</h4>
      <p>For issues or suggestions, contact the site administrator</p>
      <p>Contact: mjesusjustin@gmail.com</p>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; 2025 MangaLibrary. All Rights Reserved. | Copyrights @2025 Manga Status Site</p>
  </div>
</footer>

<style>
.site-footer {
  background: #1a1a1a;
  color: #ccc;
  padding: 40px 20px 20px;
  margin-top: 60px;
  border-top: 1px solid #333;
}

.footer-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 30px;
  max-width: 1200px;
  margin: 0 auto;
}

.footer-section h4 {
  color: #fff;
  margin-bottom: 15px;
  font-size: 18px;
}

.footer-section p {
  font-size: 14px;
  line-height: 1.6;
}

.footer-bottom {
  text-align: center;
  margin-top: 30px;
  padding-top: 20px;
  border-top: 1px solid #333;
  font-size: 14px;
}

@media (max-width: 768px) {
  .footer-content {
  }
}
</style>
</body>
</html>
