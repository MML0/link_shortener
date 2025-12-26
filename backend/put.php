<?php
require "db.php";

/* ==========================
   CONFIG
========================== */
$ADMIN_PASS = "CHANGE_ME_1234";

/* ==========================
   SHOW HTML FORM IF NOT JSON
========================== */
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$isJson = str_contains($contentType, 'application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET' || !$isJson) {
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Add Short Link</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f3f3;
        }
        .box {
            max-width: 400px;
            margin: 60px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,.1);
        }
        input, button {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
        }
        button {
            background: #007bff;
            color: #fff;
            border: 0;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
<div class="box">
    <h3>Add / Update Link</h3>
    <form method="post">
        <input name="pass" placeholder="Admin password" required>
        <input name="code" placeholder="Short code (e.g. mml)" required>
        <input name="url" placeholder="Long URL" required>
        <button>Add / Update</button>
    </form>
</div>
</body>
</html>
<?php
    exit;
}

/* ==========================
   HANDLE FORM POST
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($contentType)) {

    if ($_POST['pass'] !== $ADMIN_PASS) {
        die("Unauthorized");
    }

    $code = trim($_POST['code']);
    $url  = trim($_POST['url']);

    $stmt = $pdo->prepare("SELECT id, long_url FROM links WHERE short_code = ?");
    $stmt->execute([$code]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['long_url'] !== $url) {
            $pdo->prepare("UPDATE links SET long_url=? WHERE id=?")
                ->execute([$url, $existing['id']]);
            echo "Updated ✅";
        } else {
            echo "No change ⏭";
        }
    } else {
        $pdo->prepare("INSERT INTO links (short_code, long_url) VALUES (?,?)")
            ->execute([$code, $url]);
        echo "Inserted ✅";
    }
    exit;
}

/* ==========================
   JSON API MODE
========================== */
header("Content-Type: application/json");
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || ($input["pass"] ?? '') !== $ADMIN_PASS) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$result = ["inserted"=>[], "updated"=>[], "skipped"=>[]];

foreach ($input["links"] as $code => $url) {

    $stmt = $pdo->prepare("SELECT id, long_url FROM links WHERE short_code=?");
    $stmt->execute([$code]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['long_url'] === $url) {
            $result["skipped"][] = $code;
        } else {
            $pdo->prepare("UPDATE links SET long_url=? WHERE id=?")
                ->execute([$url, $existing['id']]);
            $result["updated"][] = $code;
        }
    } else {
        $pdo->prepare("INSERT INTO links (short_code,long_url) VALUES (?,?)")
            ->execute([$code,$url]);
        $result["inserted"][] = $code;
    }
}

echo json_encode($result, JSON_PRETTY_PRINT);
