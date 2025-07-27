<?php include 'db.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manga Library</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="home.css">
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
    
    // Scroll progress bar
    function updateScrollProgress() {
      const scrollTop = window.pageYOffset;
      const docHeight = document.body.offsetHeight - window.innerHeight;
      const scrollPercent = (scrollTop / docHeight) * 100;
      document.querySelector('.scroll-progress').style.width = scrollPercent + '%';
    }
    
    // Show toast notification
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
    
    // Initialize all interactive features
    document.addEventListener('DOMContentLoaded', function() {
      // Create fewer particles for performance
      function createParticles() {
        const container = document.createElement('div');
        container.className = 'particles-container';
        document.body.appendChild(container);
        for (let i = 0; i < 12; i++) { // Reduced from 50
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
      
      // Add scroll progress bar
      const progressBar = document.createElement('div');
      progressBar.className = 'scroll-progress';
      document.body.appendChild(progressBar);
      window.addEventListener('scroll', updateScrollProgress);
      
      // Scroll animations for manga cards (optional, but lightweight)
      if ('IntersectionObserver' in window) {
        const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
        const observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              entry.target.classList.add('visible');
            }
          });
        }, observerOptions);
        document.querySelectorAll('.manga-card').forEach(card => observer.observe(card));
      } else {
        // Fallback: show all
        document.querySelectorAll('.manga-card').forEach(card => card.classList.add('visible'));
      }
      
      // Search
      const searchInput = document.getElementById('searchInput');
      if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
          clearTimeout(searchTimeout);
          searchTimeout = setTimeout(() => {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.manga-card').forEach(card => {
              const title = card.getAttribute('data-title');
              const category = card.getAttribute('data-category');
              const status = card.getAttribute('data-status');
              if ((title && title.includes(query)) || (category && category.includes(query)) || (status && status.includes(query))) {
                card.style.display = '';
                card.style.animation = 'fadeIn 0.3s ease';
              } else {
                card.style.display = 'none';
              }
            });
          }, 200);
        });
      }
      
      // Random manga (no confetti, just highlight and scroll)
      window.enhancedRandomManga = function() {
        const randomBtn = document.getElementById('randomMangaBtn');
        if (randomBtn) {
          randomBtn.innerHTML = '<span class="loading-spinner"></span> Finding...';
          randomBtn.disabled = true;
          setTimeout(() => {
            const mangaCards = Array.from(document.querySelectorAll('.manga-card'));
            if (mangaCards.length === 0) {
              randomBtn.innerHTML = '🎲 Random Manga';
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
              randomBtn.innerHTML = '🎲 Random Manga';
              randomBtn.disabled = false;
              resultDiv.style.opacity = '1';
              resultDiv.style.transform = 'scale(1)';
            }, 400);
          }, 700);
        }
      };
      const randomBtn = document.getElementById('randomMangaBtn');
      if (randomBtn) {
        randomBtn.addEventListener('click', window.enhancedRandomManga);
      }
      
      // Theme mode toggle
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
        darkModeToggle.addEventListener('click', function() {
          let idx = themes.indexOf(document.body.classList.value.split(' ').find(c => themes.includes(c)));
          idx = (idx + 1) % themes.length;
          applyTheme(themes[idx]);
        });
      }
      
      // Floating action buttons (keep, but only 2)
      const floatingActions = document.createElement('div');
      floatingActions.className = 'floating-actions';
      floatingActions.innerHTML = `
        <button class="floating-btn" onclick="window.location='create.php'" title="Add New Manga">➕</button>
        <button class="floating-btn" onclick="window.location='browse.php'" title="Browse All">📚</button>
      `;
      document.body.appendChild(floatingActions);
      
      // Category filters (keep, but no animation)
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
          <div class="filter-pill active" onclick="filterByCategory('all')">All</div>
          ${Array.from(categories).map(cat => 
            `<div class="filter-pill" onclick="filterByCategory('${cat}')">${cat}</div>`
          ).join('')}
        `;
        document.querySelector('.latest-heading').after(filterContainer);
      }
      window.filterByCategory = function(category) {
        const cards = document.querySelectorAll('.manga-card');
        const pills = document.querySelectorAll('.filter-pill');
        pills.forEach(pill => pill.classList.remove('active'));
        event.target.classList.add('active');
        cards.forEach(card => {
          const cardCategory = card.getAttribute('data-category');
          if (category === 'all' || cardCategory === category) {
            card.style.display = '';
          } else {
            card.style.display = 'none';
          }
        });
      };
      // Stats (optional, but lightweight)
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
    <input type="text" id="searchInput" placeholder="Search manga...">
  </div>
  <div class="nav-actions">
    <button id="darkModeToggle" title="Toggle dark mode">🌙</button>
    <button id="settingsBtn" title="Settings" type="button">⚙️</button>
  </div>
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

<div class="banner">
  <div class="banner-content">
    <h1>FEATURED: MANGA LIBRARY</h1>
    <p>Manage your collection easily with cover images, categories, and reading links.</p>
    <button id="randomMangaBtn">🎲 Random Manga</button>
    <div id="randomMangaResult" style="margin-top:10px;"></div>
  </div>
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
