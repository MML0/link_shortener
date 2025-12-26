<?php
require "db.php";
header("Content-Type: application/json");

/* ==========================
   CONFIG
========================== */
$ADMIN_PASS = "CHANGE_ME_1234";

/* ==========================
   READ JSON BODY
========================== */
$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON"]);
    exit;
}

/* ==========================
   AUTH
========================== */
if (!isset($input["pass"]) || $input["pass"] !== $ADMIN_PASS) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

/* ==========================
   VALIDATE LINKS
========================== */
if (!isset($input["links"]) || !is_array($input["links"])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing links array"]);
    exit;
}

$links = $input["links"];

/* ==========================
   PROCESS
========================== */
$result = [
    "inserted" => [],
    "updated"  => [],
    "skipped"  => []
];

foreach ($links as $code => $url) {

    if (!$code || !$url) continue;

    // Check existing
    $stmt = $pdo->prepare("SELECT id, long_url FROM links WHERE short_code = ?");
    $stmt->execute([$code]);
    $existing = $stmt->fetch();

    if ($existing) {

        if ($existing["long_url"] === $url) {
            $result["skipped"][] = $code;
            continue;
        }

        $pdo->prepare(
            "UPDATE links SET long_url = ? WHERE id = ?"
        )->execute([$url, $existing["id"]]);

        $result["updated"][] = $code;

    } else {

        $pdo->prepare(
            "INSERT INTO links (short_code, long_url) VALUES (?, ?)"
        )->execute([$code, $url]);

        $result["inserted"][] = $code;
    }
}

/* ==========================
   RESPONSE
========================== */
echo json_encode([
    "status" => "ok",
    "summary" => [
        "inserted" => count($result["inserted"]),
        "updated"  => count($result["updated"]),
        "skipped"  => count($result["skipped"])
    ],
    "details" => $result
], JSON_PRETTY_PRINT);
