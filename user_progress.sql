-- User Reading Progress Table
CREATE TABLE IF NOT EXISTS user_reading_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    manga_id INT NOT NULL,
    current_chapter VARCHAR(50) DEFAULT NULL,
    total_chapters VARCHAR(50) DEFAULT NULL,
    status ENUM('plan_to_read', 'reading', 'completed', 'dropped', 'on_hold') DEFAULT 'plan_to_read',
    rating DECIMAL(2,1) DEFAULT NULL CHECK (rating >= 0 AND rating <= 10),
    start_date DATE DEFAULT NULL,
    finish_date DATE DEFAULT NULL,
    last_read_date DATE DEFAULT NULL,
    notes TEXT,
    progress_percentage INT DEFAULT 0 CHECK (progress_percentage >= 0 AND progress_percentage <= 100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (manga_id) REFERENCES manga(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_manga (user_id, manga_id)
);

-- Indexes for better performance
CREATE INDEX idx_user_progress ON user_reading_progress(user_id, status);
CREATE INDEX idx_manga_progress ON user_reading_progress(manga_id, status);
CREATE INDEX idx_last_read ON user_reading_progress(user_id, last_read_date);

-- User Reading Statistics View
CREATE VIEW user_reading_stats AS
SELECT 
    u.id as user_id,
    u.username,
    COUNT(CASE WHEN urp.status = 'reading' THEN 1 END) as currently_reading,
    COUNT(CASE WHEN urp.status = 'completed' THEN 1 END) as completed,
    COUNT(CASE WHEN urp.status = 'plan_to_read' THEN 1 END) as plan_to_read,
    COUNT(CASE WHEN urp.status = 'dropped' THEN 1 END) as dropped,
    COUNT(CASE WHEN urp.status = 'on_hold' THEN 1 END) as on_hold,
    AVG(urp.rating) as average_rating,
    SUM(urp.progress_percentage) as total_progress,
    MAX(urp.last_read_date) as last_activity
FROM users u
LEFT JOIN user_reading_progress urp ON u.id = urp.user_id
GROUP BY u.id, u.username;
