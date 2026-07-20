-- =========================================================
-- Migration: adds the event-based attendance system
-- Run this ONCE in phpMyAdmin (SQL tab, on the bsp_ranking_system
-- database). It will NOT delete any existing scouts, activities,
-- or admin data — it only adds new tables.
-- =========================================================

USE bsp_ranking_system;

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    checkin_code VARCHAR(10) NOT NULL,   -- short code used by the QR self check-in link
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

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
