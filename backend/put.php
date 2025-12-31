<?php
require "db.php";

$ADMIN_PASS = "CHANGE_ME_1234";
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

// Verify admin password in JSON mode
if ($_SERVER['REQUEST_METHOD'] === 'POST' && str_contains($contentType, 'application/json')) {
    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input || ($input['pass'] ?? '') !== $ADMIN_PASS) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        exit;
    }

    $result = ["inserted" => [], "updated" => [], "skipped" => []];

    foreach ($input['links'] as $code => $data) {
        $url = $data['url'];
        $name = $data['name'];
        $phone = $data['phone'];

        $stmt = $pdo->prepare("SELECT id, long_url FROM links WHERE short_code = ?");
        $stmt->execute([$code]);
        $row = $stmt->fetch();

        if ($row) {
            if ($row['long_url'] !== $url) {
                $pdo->prepare("UPDATE links SET long_url = ?, name = ?, phone = ? WHERE id = ?")
                    ->execute([$url, $name, $phone, $row['id']]);
                $result["updated"][] = $code;
            } else {
                $result["skipped"][] = $code;
            }
        } else {
            $pdo->prepare("INSERT INTO links (short_code, long_url, name, phone) VALUES (?, ?, ?, ?)")
                ->execute([$code, $url, $name, $phone]);
            $result["inserted"][] = $code;
        }
    }

    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

// Handle HTML Form
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Add Short Link</title>
<style>
body { font-family: Arial; background:#f3f3f3; }
.box {
    max-width:400px;
    margin:60px auto;
    background:#fff;
    padding:20px;
    border-radius:8px;
}
input,button {
    width:90%;
    padding:10px;
    margin-top:10px;
}
button {
    background:#007bff;
    color:#fff;
    border:0;
}
</style>
</head>
<body>
<div class="box">
<h3>Add / Update Link</h3>
<form method="post">
    <input name="pass" placeholder="Admin password" required>
    <input name="code" placeholder="Short code" required>
    <input name="url" placeholder="Long URL" required>
    <input name="name" placeholder="Name" required>
    <input name="phone" placeholder="Phone number" required>
    <button>Save</button>
</form>
</div>
</body>
</html>
<?php
exit;
}

// Form POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && str_contains($contentType, 'application/x-www-form-urlencoded')) {
    if ($_POST['pass'] !== $ADMIN_PASS) {
        die("Unauthorized");
    }

    $code = trim($_POST['code']);
    $url  = trim($_POST['url']);
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);

    $stmt = $pdo->prepare("SELECT id, long_url FROM links WHERE short_code = ?");
    $stmt->execute([$code]);
    $row = $stmt->fetch();

    if ($row) {
        if ($row['long_url'] !== $url) {
            $pdo->prepare("UPDATE links SET long_url = ?, name = ?, phone = ? WHERE id = ?")
                ->execute([$url, $name, $phone, $row['id']]);
            echo "Updated ✅";
        } else {
            echo "No change ⏭";
        }
    } else {
        $pdo->prepare("INSERT INTO links (short_code, long_url, name, phone) VALUES (?, ?, ?, ?)")
            ->execute([$code, $url, $name, $phone]);
        echo "Inserted ✅";
    }
    exit;
}
?>
