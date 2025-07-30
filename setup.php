<?php
// HivalVCF 1.0 - Setup Script
// WARNING: DELETE THIS FILE AFTER RUNNING IT!

// --- CONFIGURATION ---
// The name of your database file.
define('DB_FILE', 'hivalvcf.sqlite');

// --- DO NOT EDIT BELOW THIS LINE ---

header('Content-Type: text/plain');

if (file_exists(DB_FILE)) {
    die("Database file '" . DB_FILE . "' already exists. Please delete it if you want to run a fresh setup.");
}

try {
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Database file created successfully.\n";

    $sql = "
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        telegram_id BIGINT UNIQUE NOT NULL,
        username TEXT UNIQUE,
        phone_number TEXT UNIQUE,
        registration_timestamp INTEGER NOT NULL,
        status TEXT NOT NULL, -- 'collecting_username', 'collecting_phone', 'pending', 'active', 'disqualified'
        referred_by BIGINT,
        referral_count INTEGER DEFAULT 0,
        balance REAL DEFAULT 0.0
    );
    ";

    $pdo->exec($sql);
    echo "Table 'users' created successfully.\n";

    echo "\n--- SETUP COMPLETE ---\n";
    echo "You can now upload your main 'bot.php' file.\n";
    echo "IMPORTANT: DELETE THIS 'setup.php' FILE FROM YOUR SERVER NOW!\n";

} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
?>
