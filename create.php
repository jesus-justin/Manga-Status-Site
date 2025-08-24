<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add Manga - Manga Library</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="home.css">
  <style>
    .genre-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
      gap: 10px;
      margin: 10px 0;
      padding: 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      background-color: rgba(255, 255, 255, 0.05);
    }
    
    .genre-item {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 5px;
    }
    
    .genre-item input[type="checkbox"] {
      margin: 0;
      width: auto;
      cursor: pointer;
    }
    
    .genre-item label {
      font-size: 14px;
      cursor: pointer;
      user-select: none;
    }
    
    .genre-section {
      margin: 15px 0;
    }
    
    .genre-section h4 {
      margin-bottom: 10px;
      color: #333;
    }
  </style>
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
    
    <div class="genre-section">
      <h4>Select Genres (multiple allowed):</h4>
      <div class="genre-container">
        <div class="genre-item">
          <input type="checkbox" id="action" name="category[]" value="Action">
          <label for="action">Action</label>
        </div>
        <div class="genre-item">
          <input type="checkbox" id="adult" name="category[]" value="Adult">
          <label for="adult">Adult</label>
        </div>
        <div class="genre-item">
          <input type="checkbox" id="adventure" name="category[]" value="Adventure">
          <label for="adventure">Adventure</label>
        </div>
        <div class="genre-item">
          <input type="checkbox" id="comedy" name="category[]" value="Comedy">
          <label for="comedy">Comedy</label>
        </div>
        <div class="genre-item">
          <input type="checkbox" id="drama" name="category[]" value="Drama">
          <label for="drama">Drama</label>
        </div>
        <div class="genre-item">
          <input type="checkbox" id="ecchi" name="category[]" value="ecchi">
          <label for="ecchi">Ecchi</label>
        </div>
        <div class="genre-item">
          <input type="checkbox" id="fantasy" name="category[]" value="Fantasy">
          <label for="fantasy">Fantasy</label>
        </div>
        <div class="genre-item">
          <input type="checkbox" id="gore" name="category[]" value="Gore">
          <label for="gore">Gore</label>
        </div>
        <div class="genre-item">
          <input type="checkbox" id="horror" name="category[]" value="Horror">
          <label for="horror">Horror</label>
        </div>
        <div class="genre-item">
          <input type="checkbox" id="isekai" name="category[]" value="Isekai">
          <label for="isekai">Isekai</label>
        </div>
        <div class="genre-item">
          <input type="checkbox" id="magic" name="category[]" value="Magic">
          <label for="magic">Magic</label>
        </div>
        <div class="genre-item">
          <input type="checkbox" id="mecha" name="category[]" value="Mecha">
          <label for="mecha">Mecha</label>
        </div>
        <div class="genre-item">
          <input type="checkbox" id="mystery" name="category[]" value="Mystery">
          <label for="mystery">Mystery</label>
        </div>
        <div class="genre-item">
          <input type="checkbox" id="romance" name="category[]" value="Romance">
          <label for="romance">Romance</label>
        </div>
        <div class="genre-item">
          <input type="checkbox" id="school" name="category[]" value="School">
          <label for="school">School</label>
        </div>
        <div class="genre-item">
          <input type="checkbox" id="sci-fi" name="category[]" value="Sci-Fi">
          <label for="sci-fi">Sci-Fi</label>
        </div>
        <div class="genre-item">
          <input type="checkbox" id="slice-of-life" name="category[]" value="Slice of Life">
          <label for="slice-of-life">Slice of Life</label>
        </div>
        <div class="genre-item">
          <input type="checkbox" id="supernatural" name="category[]" value="Supernatural">
          <label for="supernatural">Supernatural</label>
        </div>
        <div class="genre-item">
          <input type="checkbox" id="tragedy" name="category[]" value="Tragedy">
          <label for="tragedy">Tragedy</label>
        </div>
      </div>
    </div>
    
    <input type="url" name="read_link" placeholder="Link to read manga (optional)">
    <div id="chapterField" style="display:none;">
      <input type="text" name="last_chapter" placeholder="Last Chapter Read">
    </div>
    <button type="submit">Add Manga</button>
  </form>
</div>
</body>
</html>
