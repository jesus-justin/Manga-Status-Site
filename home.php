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

try {
    // Pagination variables
    $manga_per_page = 10;
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $current_page = max(1, $current_page); // Ensure page is at least 1

    // Get total number of manga
    $count_sql = "SELECT COUNT(*) as total FROM manga";
    $count_result = $conn->query($count_sql);
    if (!$count_result) {
        throw new Exception("Failed to count manga: " . $conn->error);
    }
    $total_manga = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_manga / $manga_per_page);

    // Calculate offset for SQL query
    $offset = ($current_page - 1) * $manga_per_page;

    // Fetch manga for current page using prepared statement
    $sql = "SELECT * FROM manga ORDER BY id DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param("ii", $manga_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
} catch (Exception $e) {
    error_log("Error fetching manga for home page: " . $e->getMessage());
    $result = null;
    $total_pages = 0;
    $current_page = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manga Library</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="home.css">
  <link rel="stylesheet" href="accessibility.css">
  <link rel="stylesheet" href="skeleton-loader.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="animations.js"></script>
  <style>
    .side-scroll-track {
      position: fixed;
      right: 12px;
      top: 0;
      width: 6px;
      height: 100vh;
      background: rgba(200, 200, 200, 0.15);
      border-radius: 3px;
      z-index: 9999;
      overflow: hidden;
      pointer-events: none;
    }

    .side-scroll-progress {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 0%;
      background: linear-gradient(180deg, #ff4081, #ff1744);
      border-radius: 3px;
      transition: height 0.05s ease-out;
      box-shadow: 0 0 8px rgba(255, 23, 68, 0.6);
    }

    .btn {
      padding: 5px 10px;
      background-color: var(--accent);
      color: #fff7ea;
      border: none;
      border-radius: 4px;
      text-decoration: none;
      margin-right: 5px;
    }

    .btn:hover {
      background-color: #b91e32;
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
      background-color: #2a2420;
      color: #fff7ea;
      text-decoration: none;
      border-radius: 4px;
      transition: background-color 0.3s;
    }

    .pagination-btn:hover {
      background-color: #3b312c;
    }

    .pagination-btn.current {
      background-color: var(--accent);
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
      const scrollTop = window.scrollY || window.pageYOffset || 0;
      const docHeight = document.documentElement.scrollHeight - window.innerHeight;
      const progressElement = document.getElementById('sideScrollProgress');
      if (!progressElement) return;
      const scrollPercent = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
      progressElement.style.height = Math.min(100, Math.max(0, scrollPercent)) + '%';
      console.log('Scroll:', {scrollTop, docHeight, scrollPercent}); // Debug
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

      window.addEventListener('scroll', updateScrollProgress);
      updateScrollProgress();

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
      const sortSelect = document.getElementById('sortSelect');
      const statusFilter = document.getElementById('statusFilter');
      const clearFiltersBtn = document.getElementById('clearFiltersBtn');
      const viewModeBtn = document.getElementById('viewModeBtn');
      const resultCount = document.getElementById('resultCount');

      function applyViewMode(mode) {
        const compact = mode === 'compact';
        document.body.classList.toggle('compact-view', compact);
        if (viewModeBtn) {
          viewModeBtn.textContent = compact ? 'Comfortable View' : 'Compact View';
          viewModeBtn.setAttribute('aria-pressed', compact ? 'true' : 'false');
        }
      }

      function refreshResultCount() {
        if (!resultCount) return;
        const visible = document.querySelectorAll('.manga-card:not([style*="display: none"])').length;
        resultCount.textContent = `${visible} shown`;
      }

      function sortCards(mode) {
        const grid = document.querySelector('.manga-grid');
        if (!grid) return;
        const cards = Array.from(grid.querySelectorAll('.manga-card'));

        cards.sort((a, b) => {
          const titleA = a.getAttribute('data-title') || '';
          const titleB = b.getAttribute('data-title') || '';
          const statusA = a.getAttribute('data-status') || '';
          const statusB = b.getAttribute('data-status') || '';
          const idA = parseInt(a.getAttribute('data-id') || '0', 10);
          const idB = parseInt(b.getAttribute('data-id') || '0', 10);

          if (mode === 'title-asc') return titleA.localeCompare(titleB);
          if (mode === 'title-desc') return titleB.localeCompare(titleA);
          if (mode === 'status') return statusA.localeCompare(statusB) || titleA.localeCompare(titleB);
          return idB - idA;
        });

        cards.forEach(card => grid.appendChild(card));
      }

      function applyFilters(showNoResultsPopup = true) {
        const query = (searchInput?.value || '').toLowerCase().trim();
        const selectedStatus = (statusFilter?.value || 'all').toLowerCase();
        let hasResults = false;

        document.querySelectorAll('.manga-card').forEach(card => {
          const title = card.getAttribute('data-title') || '';
          const category = card.getAttribute('data-category') || '';
          const status = card.getAttribute('data-status') || '';

          const matchesQuery = !query || title.includes(query) || category.includes(query) || status.includes(query);
          const matchesStatus = selectedStatus === 'all' || status === selectedStatus;
          const shouldShow = matchesQuery && matchesStatus;

          card.style.display = shouldShow ? '' : 'none';
          if (shouldShow) hasResults = true;
        });

        refreshResultCount();

        if (showNoResultsPopup && query && !hasResults) {
          Swal.fire({
            title: 'No Results',
            text: `No manga found for "${query}"`,
            icon: 'info',
            confirmButtonText: 'OK',
            confirmButtonColor: '#007bff',
            timer: 2200,
            timerProgressBar: true
          });
        }
      }

      if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function () {
          clearTimeout(searchTimeout);
          searchTimeout = setTimeout(() => applyFilters(true), 180);
        });
      }

      if (statusFilter) {
        statusFilter.addEventListener('change', () => applyFilters(false));
      }

      if (sortSelect) {
        sortSelect.addEventListener('change', function () {
          sortCards(this.value);
          applyFilters(false);
        });
      }

      if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function () {
          if (searchInput) searchInput.value = '';
          if (statusFilter) statusFilter.value = 'all';
          if (sortSelect) sortSelect.value = 'newest';
          sortCards('newest');
          applyFilters(false);
        });
      }

      if (viewModeBtn) {
        const savedViewMode = localStorage.getItem('homeViewMode') || 'comfortable';
        applyViewMode(savedViewMode);

        viewModeBtn.addEventListener('click', function () {
          const nextMode = document.body.classList.contains('compact-view') ? 'comfortable' : 'compact';
          applyViewMode(nextMode);
          localStorage.setItem('homeViewMode', nextMode);
        });
      }

      sortCards('newest');
      applyFilters(false);

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

      document.addEventListener('click', async (event) => {
        const copyButton = event.target.closest('.copy-title-btn');
        if (!copyButton) return;

        const title = copyButton.getAttribute('data-title') || '';
        try {
          await navigator.clipboard.writeText(title);
          showToast(`Copied: ${title}`);
        } catch (error) {
          showToast('Copy failed. Please try again.');
        }
      });

      document.addEventListener('keydown', (event) => {
        const tag = document.activeElement?.tagName?.toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select') {
          return;
        }

        if (event.key === '/') {
          event.preventDefault();
          searchInput?.focus();
          searchInput?.select();
        }

        if (event.key.toLowerCase() === 'r') {
          event.preventDefault();
          window.enhancedRandomManga?.();
        }

        if (event.key.toLowerCase() === 'g') {
          window.location.href = 'browse.php';
        }

        if (event.key.toLowerCase() === 'a') {
          window.location.href = 'add.php';
        }
      });
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

<div class="side-scroll-track" aria-hidden="true">
  <div class="side-scroll-progress" id="sideScrollProgress"></div>
</div>

<!-- Hero Section -->
<section class="hero-section">
  <div class="hero-bg-elements">
    <div class="hero-bg-element"></div>
    <div class="hero-bg-element"></div>
    <div class="hero-bg-element"></div>
  </div>
  <div class="hero-content">
    <h1 class="hero-title">Manga Library</h1>
    <p class="hero-subtitle">Discover, track, and manage your favorite manga collection</p>
    <div class="hero-buttons">
      <a href="add.php" class="hero-btn hero-btn-primary">Add New Manga</a>
      <a href="browse.php" class="hero-btn hero-btn-secondary">Browse Collection</a>
      <a href="#librarySection" class="hero-btn hero-btn-secondary">Jump To Library</a>
    </div>
  </div>
</section>

<!-- Navigation -->
<nav>
  <div class="nav-container">
    <a href="home.php" class="nav-logo">MangaLibrary</a>
    <ul class="nav-menu">
      <li><a href="home.php">Home</a></li>
      <li><a href="browse.php">Browse</a></li>
      <li><a href="user_progress.php">My Progress</a></li>
    </ul>
    <div class="nav-actions">
      <button id="darkModeToggle" class="theme-toggle" title="Toggle theme">🎨</button>
    </div>
  </div>
</nav>

<main id="librarySection">
  <h1 class="latest-heading fade-in-up">Latest Manga</h1>
  <section class="library-controls" aria-label="Library controls">
    <input type="text" id="searchInput" class="library-input" placeholder="Search title, genre, or status..." aria-label="Search manga">
    <select id="sortSelect" class="library-select" aria-label="Sort manga">
      <option value="newest">Sort: Newest</option>
      <option value="title-asc">Title A-Z</option>
      <option value="title-desc">Title Z-A</option>
      <option value="status">Status</option>
    </select>
    <select id="statusFilter" class="library-select" aria-label="Filter by status">
      <option value="all">All statuses</option>
      <option value="currently reading">Currently reading</option>
      <option value="finished">Finished</option>
      <option value="will read">Will read</option>
      <option value="dropped">Dropped</option>
    </select>
    <button id="clearFiltersBtn" class="btn" type="button">Clear</button>
    <button id="viewModeBtn" class="btn" type="button" aria-pressed="false">Compact View</button>
    <span id="resultCount" class="result-count"></span>
  </section>
  <div class="manga-container">
    <div class="manga-grid" style="background: rgba(0,0,0,0.2); min-height: 400px; border-radius: 8px;">
      <?php if ($result && $result->num_rows > 0): ?>
        <!-- Found <?php echo $result->num_rows; ?> manga -->
        <?php while ($row = $result->fetch_assoc()): ?>
          <?php
            $title = strtolower(trim($row['title']));
            $filename = str_replace(' ', '_', $title) . '.jpeg';
            $category = htmlspecialchars($row['category']);
            $status = htmlspecialchars($row['status']);
            $statusClass = strtolower(str_replace(' ', '-', $status));
            $genres = array_map('trim', explode(',', $row['category']));
            $genre_badges = '';
            foreach ($genres as $g) {
                if (!empty($g)) {
                    $genre_badges .= '<span class="category-badge">' . htmlspecialchars($g) . '</span> ';
                }
            }
          ?>
          <div class="manga-card" data-id="<?= $row['id'] ?>" data-title="<?= strtolower($row['title']) ?>" data-category="<?= strtolower($row['category']) ?>" data-status="<?= strtolower($row['status']) ?>">
            <img src="images/<?= htmlspecialchars($filename) ?>" alt="<?= htmlspecialchars($row['title']) ?>" onerror="this.src='images/default.jpg'" loading="lazy">
            <h3><?= htmlspecialchars($row['title']) ?></h3>
            <div class="status-label <?= $statusClass ?>">Status: <?= $status ?></div>
            <div class="genre-badges"><?= $genre_badges ?></div>
            <?php if (!empty($row['last_chapter'])): ?>
              <p>Last Chapter: <?= htmlspecialchars($row['last_chapter']) ?></p>
            <?php endif; ?>
            <?php if (!empty($row['read_link'])): ?>
              <p><a href="<?= htmlspecialchars($row['read_link']) ?>" target="_blank" rel="noopener noreferrer">Read Here</a></p>
            <?php endif; ?>
            <?php if (!empty($row['external_links'])):
              $links = json_decode($row['external_links'], true);
              if ($links && is_array($links)): ?>
              <div class="external-links"><strong>Read on:</strong><ul>
                <?php foreach ($links as $link): ?>
                  <li><a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($link['name']) ?></a></li>
                <?php endforeach; ?>
              </ul></div>
            <?php endif; endif; ?>
            <div class="card-actions">
              <a href="edit.php?id=<?= $row['id'] ?>" class="btn">✏️ Edit</a>
              <button type="button" class="btn copy-title-btn" data-title="<?= htmlspecialchars($row['title']) ?>">📋 Copy</button>
              <form method="POST" action="delete.php" onsubmit="return confirm('Delete this manga?');" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->getCsrfToken()) ?>">
                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                <button type="submit" class="btn">🗑️ Delete</button>
              </form>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="color:#eee; text-align:center; margin-top: 2rem;">No manga found.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php if ($current_page > 1): ?>
        <a href="?page=<?= $current_page - 1 ?>" class="pagination-btn">Previous</a>
      <?php endif; ?>

      <?php
      $start_page = max(1, $current_page - 2);
      $end_page = min($total_pages, $current_page + 2);

      if ($start_page > 1): ?>
        <a href="?page=1" class="pagination-btn">1</a>
        <?php if ($start_page > 2): ?>
          <span class="pagination-ellipsis">...</span>
        <?php endif; ?>
      <?php endif; ?>

      <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
        <?php if ($i == $current_page): ?>
          <span class="pagination-btn current"><?= $i ?></span>
        <?php else: ?>
          <a href="?page=<?= $i ?>" class="pagination-btn"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>

      <?php if ($end_page < $total_pages): ?>
        <?php if ($end_page < $total_pages - 1): ?>
          <span class="pagination-ellipsis">...</span>
        <?php endif; ?>
        <a href="?page=<?= $total_pages ?>" class="pagination-btn"><?= $total_pages ?></a>
      <?php endif; ?>

      <?php if ($current_page < $total_pages): ?>
        <a href="?page=<?= $current_page + 1 ?>" class="pagination-btn">Next</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</main>

<!-- Footer -->
<footer class="footer">
  <div class="footer-content">
    <div class="footer-section">
      <h3>About MangaLibrary</h3>
      <p>Your personal manga collection manager. Track your reading progress, discover new series, and organize your favorite manga all in one place.</p>
    </div>
    <div class="footer-section">
      <h3>Quick Links</h3>
      <div class="footer-links">
        <a href="home.php" class="footer-link">Home</a>
        <a href="browse.php" class="footer-link">Browse</a>
        <a href="add.php" class="footer-link">Add Manga</a>
        <a href="user_progress.php" class="footer-link">My Progress</a>
      </div>
    </div>
    <div class="footer-section">
      <h3>Connect</h3>
      <p>Follow us for the latest manga updates and recommendations.</p>
      <div class="footer-links">
        <span class="footer-link">📱 Social Media</span>
        <span class="footer-link">📧 Contact</span>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; 2024 MangaLibrary. Built with ❤️ for manga enthusiasts.</p>
  </div>
</footer>

</body>
</html>
