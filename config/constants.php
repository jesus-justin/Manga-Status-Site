<?php
/**
 * Application Constants
 * 
 * Central definition of application-wide constants.
 */

// Application Info
define('APP_NAME', 'MangaLibrary');
define('APP_VERSION', '1.0.0');
define('APP_DEBUG', getenv('APP_DEBUG') ?: false);

// URLs and Paths
define('APP_URL', 'http://localhost/Manga-Status-Site');
define('BASE_PATH', __DIR__ . '/..');
define('CONFIG_PATH', __DIR__);
define('LOGS_PATH', BASE_PATH . '/logs');
define('IMAGES_PATH', BASE_PATH . '/images');

// Database
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'manga_library');

// Security
define('SESSION_TIMEOUT', 3600); // 1 hour
define('REMEMBER_ME_DURATION', 86400 * 30); // 30 days
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_WINDOW', 900); // 15 minutes
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_COOKIE_SECURE', getenv('SESSION_COOKIE_SECURE') ?: false);
define('SESSION_COOKIE_HTTPONLY', true);

// File Upload
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Pagination
define('DEFAULT_PER_PAGE', 10);
define('MAX_PER_PAGE', 100);

// API
define('API_RATE_LIMIT', 100); // requests per window
define('API_RATE_WINDOW', 3600); // 1 hour

// Manga Statuses
define('MANGA_STATUSES', [
    'will read' => 'Planning to Read',
    'currently reading' => 'Currently Reading',
    'stopped' => 'Stopped Reading',
    'finished' => 'Finished Reading'
]);

// User Reading Progress Statuses
define('PROGRESS_STATUSES', [
    'plan_to_read' => 'Plan to Read',
    'reading' => 'Reading',
    'completed' => 'Completed',
    'dropped' => 'Dropped',
    'on_hold' => 'On Hold'
]);

// Rating Range
define('MIN_RATING', 1);
define('MAX_RATING', 10);

// Default Genres
define('DEFAULT_GENRES', [
    'Action', 'Adventure', 'Comedy', 'Drama', 'Ecchi', 'Fantasy',
    'Gore', 'Horror', 'Isekai', 'Magic', 'Mecha', 'Mystery',
    'Romance', 'School', 'Sci-Fi', 'Slice of Life', 'Supernatural', 'Tragedy'
]);

// Email Configuration
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@mangalibrary.local');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: APP_NAME);
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');

// Cache
define('CACHE_DRIVER', getenv('CACHE_DRIVER') ?: 'file');
define('CACHE_DURATION', 3600); // 1 hour
define('CACHE_PATH', BASE_PATH . '/cache');

// Logging
define('LOG_LEVEL', getenv('LOG_LEVEL') ?: 'info');
define('LOG_FILE', LOGS_PATH . '/app.log');
define('LOG_MAX_SIZE', 10485760); // 10MB

// External APIs
define('JIKAN_API_BASE', 'https://api.jikan.moe/v4');
define('JIKAN_API_TIMEOUT', 10);

?>
