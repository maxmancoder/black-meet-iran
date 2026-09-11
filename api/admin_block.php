<?php
// api/admin_block.php - meeting admin persists a block (server also enforces live)
require_once __DIR__ . '/../auth.php';
require_login_api();
ensure_schema_extras();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false], 405);
if (!csrf_valid($_POST['csrf'] ?? '')) json_out(['ok' => false, 'msg' => 'درخواست نامعتبر'], 403);

$me = current_user();
$mid = (int)($_POST['meeting_id'] ?? 0);
$uid = (int)($_POST['user_id'] ?? 0);
if ($mid === 0 || $uid === 0) json_out(['ok' => false, 'msg' => 'داده ناقص']);

// caller must be the meeting admin (or site manager)
$pdo = db_connect();
$stmt = $pdo->prepare('SELECT role FROM meeting_participants WHERE meeting_id=? AND user_id=?');
$stmt->execute([$mid, $me['id']]);
$role = $stmt->fetchColumn();
if ($role !== 'admin' && empty($me['is_manager'])) json_out(['ok' => false, 'msg' => 'دسترسی ندارید'], 403);

$pdo->prepare('INSERT IGNORE INTO meeting_blocks (meeting_id, user_id) VALUES (?, ?)')->execute([$mid, $uid]);
json_out(['ok' => true]);
