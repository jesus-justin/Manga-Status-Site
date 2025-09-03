-- Manga Library Manga Table Schema
-- Run this SQL to create the manga table for the application

USE manga_library;

-- Manga table
CREATE TABLE IF NOT EXISTS manga (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255),
    description TEXT,
    category VARCHAR(100),
    status ENUM('ongoing', 'completed', 'hiatus', 'cancelled') DEFAULT 'ongoing',
    chapters INT DEFAULT 0,
    volumes INT DEFAULT 0,
    image VARCHAR(255),
    rating DECIMAL(3,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_rating (rating)
);

-- Insert sample manga data
INSERT INTO manga (title, author, description, category, status, chapters, volumes, rating) VALUES
('Attack on Titan', 'Hajime Isayama', 'In a world where humanity lives inside cities surrounded by walls due to the Titans, gigantic humanoid creatures who devour humans seemingly without reason.', 'Action, Drama, Fantasy', 'completed', 139, 34, 9.0),
('One Piece', 'Eiichiro Oda', 'Follows the adventures of Monkey D. Luffy and his pirate crew in order to find the greatest treasure ever left by the legendary Pirate, Gold Roger.', 'Action, Adventure, Comedy', 'ongoing', 1000, 100, 9.2),
('Death Note', 'Tsugumi Ohba', 'A high school student discovers a supernatural notebook that allows him to kill anyone by writing the victim\'s name.', 'Mystery, Psychological, Supernatural', 'completed', 108, 12, 8.6),
('My Hero Academia', 'Kohei Horikoshi', 'A superhero-loving boy without any powers is determined to enroll in a prestigious hero academy and learn what it really means to be a hero.', 'Action, Comedy, School', 'ongoing', 350, 35, 8.4),
('Demon Slayer', 'Koyoharu Gotouge', 'A young boy becomes a demon slayer to avenge his family and cure his sister, who was turned into a demon.', 'Action, Historical, Shounen', 'completed', 205, 23, 8.7),
('Jujutsu Kaisen', 'Gege Akutami', 'A high school student joins a secret organization of sorcerers to kill a powerful Curse that was awakened in his town.', 'Action, Drama, Supernatural', 'ongoing', 150, 17, 8.8),
('Chainsaw Man', 'Tatsuki Fujimoto', 'A young man who has been reduced to a poverty-stricken young man by a yakuza loan shark is transformed into a devil hunter after merging with his pet devil Pochita.', 'Action, Horror, Shounen', 'ongoing', 100, 11, 8.9),
('Tokyo Ghoul', 'Sui Ishida', 'A college student is attacked by a ghoul, a superhuman being that feeds on human flesh, and becomes a half-ghoul himself.', 'Action, Horror, Seinen', 'completed', 144, 14, 7.8),
('Fullmetal Alchemist', 'Hiromu Arakawa', 'Two brothers search for a Philosopher\'s Stone after an attempt to revive their deceased mother goes wrong.', 'Action, Adventure, Drama', 'completed', 108, 27, 9.1),
('Naruto', 'Masashi Kishimoto', 'A young ninja who seeks to gain the respect and acknowledgment of his fellow villagers by becoming the greatest ninja in the village.', 'Action, Adventure, Martial Arts', 'completed', 700, 72, 8.3);
