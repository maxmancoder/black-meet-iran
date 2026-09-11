<?php
// api/pv_manager.php - manager private messages console
require_once __DIR__ . '/../auth.php';
require_login_api();
ensure_schema_extras();

$me = current_user();
if (empty($me['is_manager'])) json_out(['ok' => false, 'msg' => 'دسترسی ندارید'], 403);

$pdo = db_connect();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $uid = (int)($_GET['user_id'] ?? 0);
    if ($uid === 0) {
        // conversation list
        $stmt = $pdo->query('SELECT DISTINCT m.user_id AS user_id, u.username, u.display_name, u.avatar, u.email, u.last_activity,
                              (SELECT COUNT(*) FROM pv_messages p WHERE p.user_id=m.user_id AND p.from_manager=0 AND p.seen=0) AS unread
                             FROM pv_messages m JOIN users u ON u.id=m.user_id ORDER BY m.user_id');
        json_out(['ok' => true, 'conversations' => $stmt->fetchAll()]);
    }
    // thread for a user
    $stmt = $pdo->prepare('SELECT id, from_manager, body, created_at FROM pv_messages WHERE user_id=? ORDER BY id ASC');
    $stmt->execute([$uid]);
    $msgs = $stmt->fetchAll();
    $pdo->prepare('UPDATE pv_messages SET seen=1 WHERE user_id=? AND from_manager=0')->execute([$uid]);
    json_out(['ok' => true, 'messages' => $msgs]);
}

if (!csrf_valid($_POST['csrf'] ?? '')) json_out(['ok' => false, 'msg' => 'درخواست نامعتبر'], 403);
$uid = (int)($_POST['user_id'] ?? 0);
$body = trim($_POST['body'] ?? '');
if ($uid === 0 || $body === '') json_out(['ok' => false, 'msg' => 'داده ناقص']);
$pdo->prepare('INSERT INTO pv_messages (user_id, from_manager, body) VALUES (?, 1, ?)')->execute([$uid, $body]);
json_out(['ok' => true]);
