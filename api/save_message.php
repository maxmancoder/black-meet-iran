<?php
// api/save_message.php
require_once __DIR__ . '/../auth.php';
require_login_api();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false], 405);

$body = trim($_POST['body'] ?? '');
$mid  = (int)($_POST['meeting_id'] ?? 0);
if ($body === '' || $mid === 0) json_out(['ok' => false, 'msg' => 'داده ناقص']);

$me = current_user();
$pdo = db_connect();
// verify participation
$stmt = $pdo->prepare('SELECT id FROM meeting_participants WHERE meeting_id=? AND user_id=? AND status<>?');
$stmt->execute([$mid, $me['id'], 'removed']);
if (!$stmt->fetch()) json_out(['ok' => false, 'msg' => 'عضو نیستید'], 403);

$stmt = $pdo->prepare('INSERT INTO messages (meeting_id,user_id,display_name,body) VALUES (?,?,?,?)');
$stmt->execute([$mid, $me['id'], $me['display_name'], $body]);
json_out(['ok' => true]);
