<?php
require_once 'config.php';

echo "<h2>Database and Table Setup</h2>";

// Admin Users Table (No change mentioned, but good to keep)
$conn->query("CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username TEXT NOT NULL,
    password TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
// Avoid re-inserting admin on every run if it exists
$admin_check = $conn->query("SELECT id FROM admin_users WHERE username='admin'");
if ($admin_check->num_rows == 0) {
    $conn->query("INSERT INTO admin_users(username, password) VALUES ('admin', '".md5("P@ssw0rd")."')");
    echo "<p>Admin user created.</p>";
} else {
    echo "<p>Admin user already exists.</p>";
}


// Directions Table (No change)
$conn->query("CREATE TABLE IF NOT EXISTS directions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name TEXT NOT NULL,
    program_code_identifier TEXT NOT NULL UNIQUE,
    image_path TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "<p>Table 'directions' checked/created.</p>";

// Programs Table (No schema change, num_establishments might be re-thought or removed if not used)
$conn->query("CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    direction_id INT NOT NULL,
    name TEXT NOT NULL,
    program_code TEXT NOT NULL UNIQUE,
    keywords TEXT NULL,
    image_path TEXT NULL,
    attributes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (direction_id) REFERENCES directions(id) ON DELETE CASCADE
)");
echo "<p>Table 'programs' checked/created.</p>";

// Establishments Table (Modified as per requirements)
$conn->query("DROP TABLE IF EXISTS bundles"); // Drop bundles first due to FK
$conn->query("DROP TABLE IF EXISTS establishments"); // Drop and recreate for clean schema update
$conn->query("CREATE TABLE IF NOT EXISTS establishments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name TEXT NOT NULL,
    logo_path TEXT NULL,
    address TEXT NULL, -- Адрес приемной комиссии
    latitude TEXT NULL,
    longitude TEXT NULL,
    phone TEXT NULL,
    website TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "<p>Table 'establishments' modified/created.</p>";

// Attributes Table (No change)
$conn->query("CREATE TABLE IF NOT EXISTS atributes(
    id INT AUTO_INCREMENT PRIMARY KEY,
    title TEXT,
    location TEXT -- e.g., 'programs', 'establishments' (though establishment attributes are largely removed)
)");
echo "<p>Table 'atributes' checked/created.</p>";

// New Table: clusters
$conn->query("CREATE TABLE IF NOT EXISTS clusters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name TEXT NOT NULL,
    image_path TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "<p>Table 'clusters' created.</p>";

// New Table: bundles
$conn->query("CREATE TABLE IF NOT EXISTS bundles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT NOT NULL,
    program_id INT NOT NULL,
    education_type TEXT NULL, -- e.g., 'Бесплатно'
    education_base TEXT NULL, -- e.g., '9 классов'
    duration TEXT NULL, -- e.g., '3 года 10 месяцев'
    program_address TEXT NULL, -- Адрес проведения программы
    program_latitude TEXT NULL,
    program_longitude TEXT NULL,
    cluster_id INT NULL, -- Foreign key to clusters table
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
    FOREIGN KEY (cluster_id) REFERENCES clusters(id) ON DELETE SET NULL
)");
echo "<p>Table 'bundles' created.</p>";

echo "<h3>Database setup complete.</h3>";

$conn->close();
?>