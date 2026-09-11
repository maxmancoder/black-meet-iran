<?php
// api/create_meeting.php
require_once __DIR__ . '/../auth.php';
require_login_api();
ensure_users_limited_column();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'msg' => 'متد نامعتبر'], 405);
if (!csrf_valid($_POST['csrf'] ?? '')) json_out(['ok' => false, 'msg' => 'درخواست نامعتبر'], 403);

$title = trim($_POST['title'] ?? '');
if ($title === '') $title = 'تماس بدون عنوان';
$me = current_user();

if (!empty($me['is_limited'])) {
    json_out(['ok' => false, 'msg' => 'حساب شما محدود شده و اجازه ایجاد تماس ندارید'], 403);
}

$pdo = db_connect();
$roomId = generate_room_id();
$stmt = $pdo->prepare('INSERT INTO meetings (room_id,title,creator_id) VALUES (?,?,?)');
$stmt->execute([$roomId, $title, $me['id']]);
$meetingId = $pdo->lastInsertId();

$stmt = $pdo->prepare('INSERT INTO meeting_participants
                       (meeting_id,user_id,display_name,username,status,role)
                       VALUES (?,?,?,?,?,?)');
$stmt->execute([$meetingId, $me['id'], $me['display_name'], $me['username'], 'approved', 'admin']);

json_out(['ok' => true, 'room_id' => $roomId, 'redirect' => base_url() . '/call.php?room=' . $roomId]);
