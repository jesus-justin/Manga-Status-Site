-- Database Optimization Script for Manga Library
-- This file contains recommended indexes and optimizations for better performance
-- Run these commands on your MySQL database to improve query performance

-- =====================================================
-- TABLE CREATIONS
-- =====================================================

-- Create login_attempts table for rate limiting
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    username VARCHAR(255) NOT NULL,
    attempt_time INT NOT NULL,
    INDEX idx_login_attempts_ip_username (ip, username),
    INDEX idx_login_attempts_time (attempt_time)
);

-- =====================================================
-- INDEX OPTIMIZATIONS
-- =====================================================

-- Primary index on manga.id (already exists as PRIMARY KEY, but ensuring it's optimized)
-- This helps with pagination queries that use ORDER BY id DESC
ALTER TABLE manga ADD INDEX idx_manga_id (id);

-- Index on category column for faster filtering and LIKE queries
-- Used in browse.php for genre filtering
ALTER TABLE manga ADD INDEX idx_manga_category (category(255));

-- Index on title column for sorting operations
-- Used in browse.php for ORDER BY title ASC
ALTER TABLE manga ADD INDEX idx_manga_title (title(255));

-- Index on status column for status-based filtering
-- Useful for dashboard statistics and filtering
ALTER TABLE manga ADD INDEX idx_manga_status (status);

-- Composite index for category and status filtering
-- Useful when filtering by both genre and reading status
ALTER TABLE manga ADD INDEX idx_manga_category_status (category(100), status);

-- Index on last_chapter for queries that filter by chapter progress
ALTER TABLE manga ADD INDEX idx_manga_last_chapter (last_chapter);

-- =====================================================
-- USER TABLE OPTIMIZATIONS
-- =====================================================

-- Index on users.username for faster login lookups
ALTER TABLE users ADD INDEX idx_users_username (username);

-- Index on users.email for faster login lookups
ALTER TABLE users ADD INDEX idx_users_email (email);

-- Composite index for username and email lookups during authentication
ALTER TABLE users ADD INDEX idx_users_username_email (username, email);

-- Index on created_at for user analytics and sorting
ALTER TABLE users ADD INDEX idx_users_created_at (created_at);

-- =====================================================
-- QUERY OPTIMIZATION EXAMPLES
-- =====================================================

-- Example: Optimized pagination query (from home.php)
-- EXPLAIN SELECT * FROM manga ORDER BY id DESC LIMIT 10 OFFSET 0;
-- Should use idx_manga_id index

-- Example: Optimized genre filtering query (from browse.php)
-- EXPLAIN SELECT * FROM manga WHERE category LIKE '%action%' ORDER BY title ASC;
-- Should use idx_manga_category and idx_manga_title indexes

-- Example: Optimized login query (from auth.php)
-- EXPLAIN SELECT id, username, password_hash FROM users WHERE username = 'testuser' OR email = 'test@example.com';
-- Should use idx_users_username_email composite index

-- =====================================================
-- PERFORMANCE MONITORING QUERIES
-- =====================================================

-- Check index usage
SHOW INDEX FROM manga;
SHOW INDEX FROM users;

-- Analyze slow queries (run these periodically)
-- EXPLAIN SELECT * FROM manga WHERE category LIKE '%action%' AND status = 'currently reading';
-- EXPLAIN SELECT COUNT(*) as total FROM manga;

-- =====================================================
-- MAINTENANCE QUERIES
-- =====================================================

-- Analyze table for optimization
ANALYZE TABLE manga;
ANALYZE TABLE users;

-- Check table structure and indexes
DESCRIBE manga;
DESCRIBE users;

-- =====================================================
-- ADDITIONAL OPTIMIZATION TIPS
-- =====================================================

/*
1. Consider using FULLTEXT indexes for title and category if you need complex text searches:
   ALTER TABLE manga ADD FULLTEXT idx_manga_title_fulltext (title);
   ALTER TABLE manga ADD FULLTEXT idx_manga_category_fulltext (category);

2. For very large datasets, consider partitioning the manga table by date or status:
   PARTITION BY RANGE (YEAR(created_at)) (...)

3. Monitor query performance with:
   SHOW PROCESSLIST;
   SHOW ENGINE INNODB STATUS;

4. Consider using query caching for frequently accessed data:
   SET GLOBAL query_cache_size = 134217728; -- 128MB
   SET GLOBAL query_cache_type = ON;

5. For read-heavy applications, consider using read replicas to distribute load.
*/

-- =====================================================
-- INDEX REMOVAL (if needed for cleanup)
-- =====================================================

/*
-- Uncomment these if you need to remove indexes (not recommended for production)

-- ALTER TABLE manga DROP INDEX idx_manga_id;
-- ALTER TABLE manga DROP INDEX idx_manga_category;
-- ALTER TABLE manga DROP INDEX idx_manga_title;
-- ALTER TABLE manga DROP INDEX idx_manga_status;
-- ALTER TABLE manga DROP INDEX idx_manga_category_status;
-- ALTER TABLE manga DROP INDEX idx_manga_last_chapter;

-- ALTER TABLE users DROP INDEX idx_users_username;
-- ALTER TABLE users DROP INDEX idx_users_email;
-- ALTER TABLE users DROP INDEX idx_users_username_email;
-- ALTER TABLE users DROP INDEX idx_users_created_at;
*/
