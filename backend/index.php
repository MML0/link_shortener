<?php
require "db.php";

$route = $_GET['route'] ?? '__home__';

// Find short link (including home)
$stmt = $pdo->prepare("SELECT * FROM links WHERE short_code = ?");
$stmt->execute([$route]);
$link = $stmt->fetch();

if ($link) {
    $pdo->prepare("UPDATE links SET hits = hits + 1 WHERE id = ?")
        ->execute([$link['id']]);

    header("Location: " . $link['long_url']);
    exit;
}

// Unknown route
http_response_code(404);
echo "404 - Page not found";
