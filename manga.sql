-- Manga Library Unified Database Schema (Single Source of Truth)
-- Canonical schema file for the whole project.
-- Date: 2026-03-03

CREATE DATABASE IF NOT EXISTS manga_library;
USE manga_library;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- DROP VIEWS / TABLES (safe reset order)
-- =====================================================
DROP VIEW IF EXISTS user_reading_stats;

DROP TABLE IF EXISTS user_reading_progress;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS user_collections;
DROP TABLE IF EXISTS user_preferences;
DROP TABLE IF EXISTS manga;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- USERS & AUTH
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_token_expires DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_username (username),
    INDEX idx_users_email (email),
    INDEX idx_users_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    theme VARCHAR(20) DEFAULT 'light',
    notifications BOOLEAN DEFAULT TRUE,
    privacy VARCHAR(20) DEFAULT 'public',
    CONSTRAINT fk_user_preferences_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preference (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_sessions_token (session_token),
    INDEX idx_user_sessions_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_resets_token (token),
    INDEX idx_password_resets_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(50),
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempts_ip_time (ip_address, attempt_time),
    INDEX idx_login_attempts_username_time (username, attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MANGA LIBRARY CORE
-- =====================================================
CREATE TABLE IF NOT EXISTS manga (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255),
    description TEXT,
    category VARCHAR(255),
    status ENUM('will read', 'currently reading', 'stopped', 'finished', 'dropped') DEFAULT 'will read',
    last_chapter VARCHAR(50),
    read_link VARCHAR(255),
    external_links JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_manga_title (title),
    INDEX idx_manga_category (category),
    INDEX idx_manga_status (status),
    INDEX idx_manga_updated_at (updated_at),
    INDEX idx_manga_category_status (category, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    manga_id INT NOT NULL,
    status ENUM('want to read', 'reading', 'completed', 'dropped') DEFAULT 'want to read',
    rating DECIMAL(2,1) DEFAULT NULL,
    notes TEXT,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_collections_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_collections_manga FOREIGN KEY (manga_id) REFERENCES manga(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_manga_collection (user_id, manga_id),
    INDEX idx_user_collections_user_status (user_id, status),
    INDEX idx_user_collections_manga (manga_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_reading_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    manga_id INT NOT NULL,
    chapters INT DEFAULT 0,
    current_chapter VARCHAR(50) DEFAULT NULL,
    total_chapters VARCHAR(50) DEFAULT NULL,
    status ENUM('will read', 'currently reading', 'finished', 'dropped', 'on hold', 'stopped') DEFAULT 'will read',
    rating DECIMAL(3,1) DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    finish_date DATE DEFAULT NULL,
    last_read_date DATE DEFAULT NULL,
    notes TEXT,
    progress_percentage INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_progress_manga FOREIGN KEY (manga_id) REFERENCES manga(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_manga_progress (user_id, manga_id),
    INDEX idx_user_progress_user_status (user_id, status),
    INDEX idx_user_progress_manga_status (manga_id, status),
    INDEX idx_user_progress_last_read (user_id, last_read_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VIEWS
-- =====================================================
CREATE VIEW user_reading_stats AS
SELECT
    u.id AS user_id,
    u.username,
    COUNT(CASE WHEN urp.status = 'currently reading' THEN 1 END) AS currently_reading,
    COUNT(CASE WHEN urp.status = 'finished' THEN 1 END) AS completed,
    COUNT(CASE WHEN urp.status = 'will read' THEN 1 END) AS will_read,
    COUNT(CASE WHEN urp.status = 'dropped' THEN 1 END) AS dropped,
    COUNT(CASE WHEN urp.status = 'on hold' THEN 1 END) AS on_hold,
    AVG(urp.rating) AS average_rating,
    SUM(urp.progress_percentage) AS total_progress,
    MAX(urp.last_read_date) AS last_activity
FROM users u
LEFT JOIN user_reading_progress urp ON u.id = urp.user_id
GROUP BY u.id, u.username;

-- =====================================================
-- SEED DATA
-- =====================================================
INSERT INTO users (username, email, password_hash, email_verified)
VALUES ('admin', 'admin@mangalibrary.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE)
ON DUPLICATE KEY UPDATE username = username;

INSERT INTO manga (title, author, description, category, status, last_chapter, read_link, external_links)
VALUES
('Attack on Titan', 'Hajime Isayama', 'In a world where humanity lives inside cities surrounded by walls due to the Titans, gigantic humanoid creatures who devour humans seemingly without reason.', 'Action, Drama, Fantasy', 'finished', '139', 'https://example.com/aot', JSON_ARRAY()),
('One Piece', 'Eiichiro Oda', 'Follows the adventures of Monkey D. Luffy and his pirate crew in order to find the greatest treasure ever left by the legendary Pirate, Gold Roger.', 'Action, Adventure, Comedy', 'currently reading', '1000', 'https://example.com/onepiece', JSON_ARRAY()),
('Death Note', 'Tsugumi Ohba', 'A high school student discovers a supernatural notebook that allows him to kill anyone by writing the victim''s name.', 'Mystery, Psychological, Supernatural', 'finished', '108', 'https://example.com/deathnote', JSON_ARRAY())
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
