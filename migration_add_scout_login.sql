    -- =========================================================
    -- Migration: adds scout self-registration / login support
    -- Run this ONCE in phpMyAdmin (SQL tab, on the bsp_ranking_system
    -- database) if your database already exists — it will NOT
    -- delete any of your existing scouts, activities, or admin data.
    -- =========================================================

    USE bsp_ranking_system;

    ALTER TABLE scouts
        ADD COLUMN username VARCHAR(50) NULL UNIQUE AFTER photo,
        ADD COLUMN password VARCHAR(255) NULL AFTER username;
