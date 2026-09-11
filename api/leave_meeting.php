<?php
// api/leave_meeting.php - mark current user as removed from the meeting
require_once __DIR__ . '/../auth.php';
require_login_api();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false], 405);

$mid = (int)($_POST['meeting_id'] ?? 0);
if ($mid === 0) json_out(['ok' => false, 'msg' => 'داده ناقص']);

$me = current_user();
$pdo = db_connect();
$stmt = $pdo->prepare('SELECT id FROM meeting_participants WHERE meeting_id=? AND user_id=? AND status<>?');
$stmt->execute([$mid, $me['id'], 'removed']);
if ($stmt->fetch()) {
    $pdo->prepare('UPDATE meeting_participants SET status=? WHERE meeting_id=? AND user_id=?')
        ->execute(['removed', $mid, $me['id']]);
}
json_out(['ok' => true]);
