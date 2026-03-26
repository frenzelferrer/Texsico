-- ============================================
-- Texsico - Minimal Social Messaging WebApp
-- Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS texsico_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE texsico_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    bio TEXT DEFAULT NULL,
    profile_image VARCHAR(255) DEFAULT 'default.png',
    cover_photo VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Posts table
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Comments table
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Likes table
CREATE TABLE likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Messages (Chat) table
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text','image','voice') NOT NULL DEFAULT 'text',
    media_file VARCHAR(255) DEFAULT NULL,
    media_duration INT DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sample Users (passwords: "password123" hashed)
INSERT INTO users (username, email, password, full_name, bio) VALUES
('alex_signal', 'alex@texsico.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alex Rivera', 'Coffee addict. Code enthusiast. Always one message away from a new idea.'),
('mia_shore', 'mia@texsico.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mia Shores', 'Designer, detail lover, and always sketching the next UI.'),
('jay_tide', 'jay@texsico.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jay Tidewell', 'Full stack dev. Night owl. Shipping features after midnight.');

-- Sample Posts
INSERT INTO posts (user_id, content, created_at) VALUES
(1, 'Just launched my first open-source project! Years of learning, compressed into one repo. Drop a star if you vibe with it ⭐', NOW() - INTERVAL 2 HOUR),
(2, 'Reminder: rest is productive. You cannot pour from an empty cup. Take care of yourself today 💙', NOW() - INTERVAL 5 HOUR),
(3, 'Hot take: documentation is more important than the code itself. Fight me.', NOW() - INTERVAL 1 DAY),
(1, 'Morning run done. Inbox zero achieved. Coffee in hand. Today is going to be different.', NOW() - INTERVAL 2 DAY);

-- Sample Comments
INSERT INTO comments (post_id, user_id, content, created_at) VALUES
(1, 2, 'This is so cool! Just starred it 🌟', NOW() - INTERVAL 1 HOUR),
(1, 3, 'Congrats! What stack did you use?', NOW() - INTERVAL 30 MINUTE),
(2, 1, 'Needed to hear this today. Thank you!', NOW() - INTERVAL 4 HOUR),
(3, 2, 'Completely agree. Underdocumented code is a nightmare.', NOW() - INTERVAL 20 HOUR);

-- Sample Likes
INSERT INTO likes (post_id, user_id) VALUES
(1, 2), (1, 3), (2, 1), (2, 3), (3, 1), (3, 2), (4, 2), (4, 3);

-- Sample Messages
INSERT INTO messages (sender_id, receiver_id, message, is_read, created_at) VALUES
(1, 2, 'Hey Mia! Loved your latest post 💙', 1, NOW() - INTERVAL 3 HOUR),
(2, 1, 'Thanks Alex! Means a lot 😊', 1, NOW() - INTERVAL 2 HOUR),
(2, 1, 'By the way, saw your project - amazing work!', 0, NOW() - INTERVAL 1 HOUR);
