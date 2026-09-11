<?php
// api/meeting_info.php  (public lookup by room id)
require_once __DIR__ . '/../auth.php';

$room = trim($_GET['room'] ?? '');
if ($room === '') json_out(['ok' => false, 'msg' => 'لینک نامعتبر'], 404);

$pdo = db_connect();
$stmt = $pdo->prepare('SELECT room_id, title FROM meetings WHERE room_id=? AND active=1');
$stmt->execute([$room]);
$row = $stmt->fetch();
if (!$row) json_out(['ok' => false, 'msg' => 'تماسی با این لینک یافت نشد'], 404);

json_out(['ok' => true, 'room_id' => $row['room_id'], 'title' => $row['title']]);
