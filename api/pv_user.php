<?php
// api/pv_user.php - used by the "forgot password" page (no login required)
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../functions.php';
ensure_schema_extras();

function find_user_by_identifier($pdo, $id) {
    $id = trim($id);
    if ($id === '') return null;
    if (filter_var($id, FILTER_VALIDATE_EMAIL)) {
        $s = $pdo->prepare('SELECT id, username, display_name, avatar FROM users WHERE email=?');
    } else {
        $s = $pdo->prepare('SELECT id, username, display_name, avatar FROM users WHERE phone=?');
    }
    $s->execute([$id]);
    return $s->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $identifier = trim($_GET['identifier'] ?? '');
    $pdo = db_connect();
    $u = find_user_by_identifier($pdo, $identifier);
    if (!$u) json_out(['ok' => false, 'msg' => 'کاربری با این مشخصات یافت نشد']);
    $stmt = $pdo->prepare('SELECT id, from_manager, body, created_at FROM pv_messages WHERE user_id=? ORDER BY id ASC');
    $stmt->execute([$u['id']]);
    json_out(['ok' => true, 'user' => $u, 'messages' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false], 405);
$identifier = trim($_POST['identifier'] ?? '');
$body = trim($_POST['body'] ?? '');
if ($body === '') json_out(['ok' => false, 'msg' => 'متن پیام خالی است']);
$pdo = db_connect();
$u = find_user_by_identifier($pdo, $identifier);
if (!$u) json_out(['ok' => false, 'msg' => 'کاربری با این مشخصات یافت نشد']);
$pdo->prepare('INSERT INTO pv_messages (user_id, from_manager, body) VALUES (?, 0, ?)')->execute([$u['id'], $body]);
$pdo->prepare('UPDATE users SET last_activity = NOW() WHERE id=?')->execute([$u['id']]);
json_out(['ok' => true]);
