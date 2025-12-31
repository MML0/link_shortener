<?php
require "db.php";

/* ==========================
   CREATE TABLE
========================== */
$sql = "
CREATE TABLE IF NOT EXISTS links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    short_code VARCHAR(50) UNIQUE NOT NULL,
    long_url TEXT NOT NULL,
    name VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    hits INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
