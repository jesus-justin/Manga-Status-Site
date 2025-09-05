-- Manga Library Manga Table Schema
-- Updated to match PHP code expectations

USE manga_library;

-- Drop existing manga table if exists
DROP TABLE IF EXISTS manga;

-- Manga table
CREATE TABLE IF NOT EXISTS manga (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255),
    description TEXT,
    category VARCHAR(255),
    status ENUM('will read', 'currently reading', 'stopped', 'finished') DEFAULT 'will read',
    last_chapter VARCHAR(50),
    read_link VARCHAR(255),
    external_links TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_category (category),
    INDEX idx_status (status)
);

-- Insert sample manga data with new columns
INSERT INTO manga (title, author, description, category, status, last_chapter, read_link, external_links) VALUES
('Attack on Titan', 'Hajime Isayama', 'In a world where humanity lives inside cities surrounded by walls due to the Titans, gigantic humanoid creatures who devour humans seemingly without reason.', 'Action, Drama, Fantasy', 'finished', '139', 'https://example.com/aot', NULL),
('One Piece', 'Eiichiro Oda', 'Follows the adventures of Monkey D. Luffy and his pirate crew in order to find the greatest treasure ever left by the legendary Pirate, Gold Roger.', 'Action, Adventure, Comedy', 'currently reading', '1000', 'https://example.com/onepiece', NULL),
('Death Note', 'Tsugumi Ohba', 'A high school student discovers a supernatural notebook that allows him to kill anyone by writing the victim\'s name.', 'Mystery, Psychological, Supernatural', 'finished', '108', 'https://example.com/deathnote', NULL);
