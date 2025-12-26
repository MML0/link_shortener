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
    hits INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

$pdo->exec($sql);

/* ==========================
   INSERT BASE ROUTE (SAFE)
========================== */
$stmt = $pdo->prepare("
    INSERT INTO links (short_code, long_url)
    SELECT :code, :url
    FROM DUAL
    WHERE NOT EXISTS (
        SELECT 1 FROM links WHERE short_code = :code
    )
");

$stmt->execute([
    ":code" => "__home__",
    ":url"  => "https://banoo.khas.shop"
]);

echo "Migration completed ✅";
