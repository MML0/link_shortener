<?php
require "db.php";

// Check if URL has ?fresh query string
if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] === 'fresh2') {
    $pdo->exec("DROP TABLE IF EXISTS links");
    echo "DROPED<br>";
}

/* ==========================
   CREATE LINKS TABLE
========================== */
$sql = "
CREATE TABLE IF NOT EXISTS links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    short_code VARCHAR(50)
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_bin
        NOT NULL,
    long_url TEXT NOT NULL,
    name VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    hits INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_short_code (short_code)
);
";
$pdo->exec($sql);

/* ==========================
   CREATE DAILY HITS TABLE
========================== */
$sql = "
CREATE TABLE IF NOT EXISTS daily_hits (
    hit_date DATE PRIMARY KEY,
    hits INT DEFAULT 0
);
";
$pdo->exec($sql);

/* ==========================
   INSERT BASE ROUTE (SAFE)
========================== */
$stmt = $pdo->prepare("
    INSERT INTO links (short_code, long_url, name, phone)
    SELECT :code, :url, :name, :phone
    FROM DUAL
    WHERE NOT EXISTS (
        SELECT 1 FROM links WHERE short_code = :code
    )
");

$stmt->execute([
    ":code"  => "__home__",
    ":url"   => "https://banoo.khas.shop",
    ":name"  => "Default Name",
    ":phone" => "0000000000"
]);

echo "Migration completed ✅";
