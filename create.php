<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add Manga - Manga Library</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="home.css">
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // --- Function to toggle chapter field based on status ---
      function toggleChapterField() {
        const status = document.querySelector('select[name="status"]').value;
        const chapterField = document.getElementById('chapterField');
        chapterField.style.display = (status === 'currently reading') ? 'block' : 'none';
      }

      // Initial call and event listener for chapter field
      toggleChapterField();
      document.querySelector('select[name="status"]').addEventListener('change', toggleChapterField);

      // --- Theme switcher logic ---
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
          const currentTheme = Array.from(document.body.classList).find(c => themes.includes(c)) || themes[0];
          const idx = (themes.indexOf(currentTheme) + 1) % themes.length;
          applyTheme(themes[idx]);
        });
      }

      // --- Settings tab menu toggle ---
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
<div class="add-form">
  <form action="add.php" method="POST">
    <input type="text" name="title" placeholder="Enter Manga Title" required>
    <select name="status" required>
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
</body>
</html> 