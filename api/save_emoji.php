<?php
// api/save_emoji.php
require_once __DIR__ . '/../auth.php';
require_login_api();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false], 405);

$emoji = $_POST['emoji'] ?? '';
$mid   = (int)($_POST['meeting_id'] ?? 0);
if ($emoji === '' || $mid === 0) json_out(['ok' => false, 'msg' => 'داده ناقص']);

$me = current_user();
$pdo = db_connect();
$stmt = $pdo->prepare('SELECT id FROM meeting_participants WHERE meeting_id=? AND user_id=? AND status<>?');
$stmt->execute([$mid, $me['id'], 'removed']);
if (!$stmt->fetch()) json_out(['ok' => false, 'msg' => 'عضو نیستید'], 403);

$stmt = $pdo->prepare('INSERT INTO emoji_events (meeting_id,user_id,display_name,emoji) VALUES (?,?,?,?)');
$stmt->execute([$mid, $me['id'], $me['display_name'], $emoji]);
json_out(['ok' => true]);
