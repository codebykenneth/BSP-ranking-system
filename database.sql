-- =========================================================
-- BSP Ranking System - Database Schema + Sample Data
-- Import this file in phpMyAdmin (XAMPP) to set everything up.
-- =========================================================

CREATE DATABASE IF NOT EXISTS bsp_ranking_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE bsp_ranking_system;

-- ---------------------------------------------------------
-- Table: users  (admin accounts)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,   -- stored as a password_hash()
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin account -> username: admin | password: admin123
-- (hash generated with PHP password_hash('admin123', PASSWORD_DEFAULT))
INSERT INTO users (username, password) VALUES
('admin', '$2y$10$YkFCcElroOnf.MMEhdkvgOPOug2xKCPohgEvlvT9afFjcDfWNjiPS');

-- ---------------------------------------------------------
-- Table: scouts
-- rank_level is a numeric value (1-7) used directly in the
-- scoring formula. rank holds the human-readable BSP rank name.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS scouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    troop VARCHAR(100) NOT NULL,
    rank_name VARCHAR(50) NOT NULL DEFAULT 'Scout',
    rank_level INT NOT NULL DEFAULT 1,     -- 1=Scout ... 7=Eagle Scout
    attendance DECIMAL(5,2) NOT NULL DEFAULT 0, -- percentage 0-100
    photo VARCHAR(255) DEFAULT NULL,
    username VARCHAR(50) NULL UNIQUE,      -- set only if the scout self-registers a portal account
    password VARCHAR(255) NULL,            -- password_hash(), NULL until they register
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO scouts (name, troop, rank_name, rank_level, attendance, photo) VALUES
('Juan Dela Cruz',      'Troop 101 - San Miguel', 'Star Scout',        5, 95.00, NULL),
('Mark Anthony Reyes',  'Troop 101 - San Miguel', 'First Class Scout', 4, 88.50, NULL),
('Paolo Santos',        'Troop 102 - Sto. Nino',  'Eagle Scout',       7, 99.00, NULL),
('Kevin Ramos',         'Troop 102 - Sto. Nino',  'Tenderfoot',        2, 70.00, NULL),
('Miguel Torres',       'Troop 103 - Immaculada', 'Second Class Scout',3, 82.00, NULL),
('Angelo Bautista',     'Troop 101 - San Miguel', 'Senior Scout',      6, 91.25, NULL),
('Rafael Garcia',       'Troop 103 - Immaculada', 'Scout',             1, 60.00, NULL),
('Enzo Villanueva',     'Troop 102 - Sto. Nino',  'First Class Scout', 4, 78.00, NULL);

-- ---------------------------------------------------------
-- Table: events  (troop meetings / activities that attendance is taken at)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    checkin_code VARCHAR(10) NOT NULL,   -- short code used by the QR self check-in link
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO events (title, event_date, checkin_code) VALUES
('Weekly Troop Meeting - Week 1', '2026-06-01', 'WK1A2C'),
('Weekly Troop Meeting - Week 2', '2026-06-08', 'WK2B7D'),
('Weekly Troop Meeting - Week 3', '2026-06-15', 'WK3E9F'),
('Camporee 2026',                 '2026-02-14', 'CMP014'),
('Community Tree Planting',       '2026-03-02', 'TREE02');

-- ---------------------------------------------------------
-- Table: attendance  (one row per scout per event)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    scout_id INT NOT NULL,
    status ENUM('Present', 'Late', 'Absent', 'Excused') NOT NULL DEFAULT 'Absent',
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_event_scout (event_id, scout_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (scout_id) REFERENCES scouts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO attendance (event_id, scout_id, status) VALUES
(1, 1, 'Present'), (1, 2, 'Present'), (1, 3, 'Present'), (1, 4, 'Absent'),
(1, 5, 'Late'),    (1, 6, 'Present'), (1, 7, 'Absent'),  (1, 8, 'Present'),
(2, 1, 'Present'), (2, 2, 'Absent'),  (2, 3, 'Present'), (2, 4, 'Present'),
(2, 5, 'Present'), (2, 6, 'Present'), (2, 7, 'Excused'), (2, 8, 'Late'),
(3, 1, 'Present'), (3, 2, 'Present'), (3, 3, 'Present'), (3, 4, 'Absent'),
(3, 5, 'Present'), (3, 6, 'Absent'),  (3, 7, 'Absent'),  (3, 8, 'Present');

-- ---------------------------------------------------------
-- Table: announcements  (posted by admin, visible to scouts on their portal)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    posted_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO announcements (title, message, posted_by) VALUES
('Welcome to the BSP Ranking System', 'Scouts can now log in to their own portal to check their points, attendance, and rank. See your Troop Leader if you need your login set up.', 'admin'),
('Camporee 2026 Reminder', 'Don''t forget to bring your uniform and camping gear for this weekend''s Camporee. Attendance will be recorded on-site.', 'admin');

-- ---------------------------------------------------------
-- Table: activities
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scout_id INT NOT NULL,
    activity_name VARCHAR(150) NOT NULL,
    points INT NOT NULL DEFAULT 0,
    activity_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scout_id) REFERENCES scouts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO activities (scout_id, activity_name, points, activity_date) VALUES
(1, 'Camporee 2026',              50, '2026-02-14'),
(1, 'Community Tree Planting',    20, '2026-03-02'),
(1, 'First Aid Training',         30, '2026-04-10'),
(2, 'Camporee 2026',              50, '2026-02-14'),
(2, 'Flag Ceremony Duty',         10, '2026-03-15'),
(3, 'National Jamboree',          80, '2026-01-20'),
(3, 'Community Tree Planting',    20, '2026-03-02'),
(3, 'Leadership Training Course', 40, '2026-05-05'),
(4, 'Flag Ceremony Duty',         10, '2026-03-15'),
(5, 'First Aid Training',         30, '2026-04-10'),
(5, 'Camporee 2026',              50, '2026-02-14'),
(6, 'Leadership Training Course', 40, '2026-05-05'),
(6, 'National Jamboree',          80, '2026-01-20'),
(7, 'Flag Ceremony Duty',         10, '2026-03-15'),
(8, 'Community Tree Planting',    20, '2026-03-02'),
(8, 'First Aid Training',         30, '2026-04-10');
