<?php
// api/mute_member.php  (admin force mute)
require_once __DIR__ . '/../auth.php';
require_login_api();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'msg' => 'متد نامعتبر'], 405);
if (!csrf_valid($_POST['csrf'] ?? '')) json_out(['ok' => false, 'msg' => 'درخواست نامعتبر'], 403);

$pid  = (int)($_POST['participant_id'] ?? 0);
$muted = (int)($_POST['muted'] ?? 1);
$me   = current_user();
$pdo  = db_connect();

$stmt = $pdo->prepare('SELECT p.*, m.creator_id FROM meeting_participants p
                       JOIN meetings m ON m.id=p.meeting_id WHERE p.id=?');
$stmt->execute([$pid]);
$part = $stmt->fetch();
if (!$part) json_out(['ok' => false, 'msg' => 'عضو یافت نشد']);
if ($part['creator_id'] != $me['id']) json_out(['ok' => false, 'msg' => 'دسترسی ندارید'], 403);

$pdo->prepare('UPDATE meeting_participants SET muted=? WHERE id=?')
    ->execute([$muted ? 1 : 0, $pid]);
json_out(['ok' => true, 'user_id' => (int)$part['user_id'], 'muted' => (bool)$muted]);
