<?php
// api/close_meeting.php - called by the signaling server when a room becomes empty
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false], 405);

$cfg = app_config();
$secret = $_POST['secret'] ?? '';
$room   = trim($_POST['room'] ?? '');
if (!hash_equals($cfg['secret'], $secret) || $room === '') {
    json_out(['ok' => false, 'msg' => 'unauthorized'], 403);
}

$pdo = db_connect();
$pdo->prepare('UPDATE meetings SET active=0 WHERE room_id=? AND active=1')
    ->execute([$room]);
json_out(['ok' => true]);
