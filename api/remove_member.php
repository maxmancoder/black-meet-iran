<?php
// api/remove_member.php  (kick from live call)
require_once __DIR__ . '/../auth.php';
require_login_api();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'msg' => 'متد نامعتبر'], 405);
if (!csrf_valid($_POST['csrf'] ?? '')) json_out(['ok' => false, 'msg' => 'درخواست نامعتبر'], 403);

$pid = (int)($_POST['participant_id'] ?? 0);
$me  = current_user();
$pdo = db_connect();

$stmt = $pdo->prepare('SELECT p.*, m.creator_id FROM meeting_participants p
                       JOIN meetings m ON m.id=p.meeting_id WHERE p.id=?');
$stmt->execute([$pid]);
$part = $stmt->fetch();
if (!$part) json_out(['ok' => false, 'msg' => 'عضو یافت نشد']);
if ($part['creator_id'] != $me['id']) json_out(['ok' => false, 'msg' => 'دسترسی ندارید'], 403);

$pdo->prepare('UPDATE meeting_participants SET status=? WHERE id=?')
    ->execute(['removed', $pid]);
json_out(['ok' => true, 'user_id' => (int)$part['user_id']]);
