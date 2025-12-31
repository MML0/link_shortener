<?php
require "db.php";
header("Content-Type: application/json");

if (isset($_GET['code'])) {
    // Single link stats
    $stmt = $pdo->prepare("
        SELECT short_code, long_url, hits, created_at, name, phone
        FROM links WHERE short_code = ?
    ");
    $stmt->execute([$_GET['code']]);
    $data = $stmt->fetch();

    if (!$data) {
        http_response_code(404);
        echo json_encode(["error" => "Link not found"]);
        exit;
    }

    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// All links stats
$stmt = $pdo->query("
    SELECT short_code, long_url, hits, created_at, name, phone
    FROM links ORDER BY hits DESC
");

echo json_encode($stmt->fetchAll(), JSON_PRETTY_PRINT);
