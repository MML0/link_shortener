<?php
require "db.php";
header("Content-Type: application/json");

function getLast7DaysHits($pdo) {
    $stmt = $pdo->query("
        SELECT hit_date, hits
        FROM daily_hits
        ORDER BY hit_date DESC
        LIMIT 7
    ");
    $rows = $stmt->fetchAll();

    // Reverse to show oldest → newest
    return array_reverse($rows);
}
function formatLast7DaysHitsForTelegram($hitsArray) {
    $text = "📊 Daily Hits Last 7 Days:\n";
    foreach ($hitsArray as $row) {
        $date = date("M d", strtotime($row['hit_date']));
        $text .= "• $date: {$row['hits']} hits\n";
    }
    return $text;
}
$dailyHits = getLast7DaysHits($pdo);
$message = formatLast7DaysHitsForTelegram($dailyHits);

// Send to multiple admins
$adminChatId = $config['telegram']['admin_chatid'];
$admins = [$adminChatId, '681048151'];
foreach ($admins as $id) {
    sendTelegramMessage($id, $message);
}
$response = [
    "daily_hits_last_7_days" => $dailyHits
];

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

    $response["link"] = $data;

    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

// All links stats
$stmt = $pdo->query("
    SELECT short_code, long_url, hits, created_at, name, phone
    FROM links ORDER BY hits DESC
");

$response["links"] = $stmt->fetchAll();

echo json_encode($response, JSON_PRETTY_PRINT);
