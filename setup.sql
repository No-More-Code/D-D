-- setup.sql
-- Create database
CREATE DATABASE IF NOT EXISTS social_platform;
USE social_platform;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    join_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_online BOOLEAN DEFAULT FALSE
);

-- Chat messages table
CREATE TABLE chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Direct messages table
CREATE TABLE direct_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    message TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Calendar events table
CREATE TABLE calendar_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Active sessions table (for real-time tracking)
CREATE TABLE active_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(100) NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Indexes for better performance
CREATE INDEX idx_chat_timestamp ON chat_messages(timestamp);
CREATE INDEX idx_dm_participants ON direct_messages(sender_id, recipient_id);
CREATE INDEX idx_events_date ON calendar_events(user_id, event_date);
CREATE INDEX idx_sessions_user ON active_sessions(user_id);

-- Insert some sample data
INSERT INTO users (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com'),
('demo_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'demo@example.com');

-- Sample chat messages
INSERT INTO chat_messages (user_id, message) VALUES 
(1, 'Welcome to the chat room!'),
(2, 'Hello everyone!');

-- Sample calendar events
INSERT INTO calendar_events (user_id, title, event_date) VALUES 
(1, 'Team Meeting', CURDATE()),
(1, 'Project Deadline', DATE_ADD(CURDATE(), INTERVAL 3 DAY)),
(2, 'Birthday Party', DATE_ADD(CURDATE(), INTERVAL 7 DAY));