<?php
require "db.php";

$ADMIN_PASS = "CHANGE_ME_1234";

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

/* ==========================
   HTML FORM (GET ONLY)
========================== */
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
    <button>Save</button>
</form>
</div>
</body>
</html>
<?php
exit;
}

/* ==========================
   FORM POST MODE
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && str_contains($contentType, 'application/x-www-form-urlencoded')
) {
    if ($_POST['pass'] !== $ADMIN_PASS) {
        die("Unauthorized");
    }

    $code = trim($_POST['code']);
    $url  = trim($_POST['url']);

    $stmt = $pdo->prepare("SELECT id,long_url FROM links WHERE short_code=?");
    $stmt->execute([$code]);
    $row = $stmt->fetch();

    if ($row) {
        if ($row['long_url'] !== $url) {
            $pdo->prepare("UPDATE links SET long_url=? WHERE id=?")
                ->execute([$url,$row['id']]);
            echo "Updated ✅";
        } else {
            echo "No change ⏭";
        }
    } else {
        $pdo->prepare("INSERT INTO links (short_code,long_url) VALUES (?,?)")
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

if (!$input || ($input['pass'] ?? '') !== $ADMIN_PASS) {
    http_response_code(401);
    echo json_encode(["error"=>"Unauthorized"]);
    exit;
}

$result = ["inserted"=>[], "updated"=>[], "skipped"=>[]];

foreach ($input['links'] as $code => $url) {
    $stmt = $pdo->prepare("SELECT id,long_url FROM links WHERE short_code=?");
    $stmt->execute([$code]);
    $row = $stmt->fetch();

    if ($row) {
        if ($row['long_url'] === $url) {
            $result["skipped"][] = $code;
        } else {
            $pdo->prepare("UPDATE links SET long_url=? WHERE id=?")
                ->execute([$url,$row['id']]);
            $result["updated"][] = $code;
        }
    } else {
        $pdo->prepare("INSERT INTO links (short_code,long_url) VALUES (?,?)")
            ->execute([$code,$url]);
        $result["inserted"][] = $code;
    }
}

echo json_encode($result, JSON_PRETTY_PRINT);
