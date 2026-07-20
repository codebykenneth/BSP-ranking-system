-- =========================================================
-- Migration: adds admin announcements (visible to scouts on their portal)
-- Run this ONCE in phpMyAdmin (SQL tab, on the bsp_ranking_system
-- database). It will NOT delete any existing data.
-- =========================================================

USE bsp_ranking_system;

CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    posted_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
