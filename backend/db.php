<?php
// db.php
$DB_HOST = "localhost";
$DB_NAME = "shortener";
$DB_USER = "root";
$DB_PASS = "";

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}

// Telegram config
$config = [
    'telegram' => [
        'bot_token' => '8566:beqa71E',
        'bot_username' => 'Awefwefbot',
        'admin_chatid' => '234234345',
    ]
];

// Function to send message via Telegram Bot
function sendTelegramMessage($chatId, $text): void {
    global $config;

    $token = $config['telegram']['bot_token'];
    $url   = "https://api.telegram.org/bot{$token}/sendMessage";

    $data = [
        'chat_id' => $chatId,
        'text'    => $text,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
    ]);
    curl_exec($ch);
    curl_close($ch);
}
?>
