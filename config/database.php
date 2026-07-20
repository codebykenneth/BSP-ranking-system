<?php
/**
 * Database connection (PDO) - Neon PostgreSQL
 */
define('DB_HOST', 'ep-patient-base-avx701x3-pooler.c-11.us-east-1.aws.neon.tech');
define('DB_NAME', 'neondb');
define('DB_USER', 'neondb_owner');
define('DB_PASS', getenv('DB_PASS') ?: 'npg_ZdQlI5o8MqAC');

try {
    $pdo = new PDO(
        "pgsql:host=" . DB_HOST . ";port=5432;dbname=" . DB_NAME . ";sslmode=require",");$pdo->exec("SET TIME ZONE 'Asia/Manila'
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
