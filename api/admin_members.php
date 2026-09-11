<?php
// api/admin_members.php - manager-only: list users + toggle limitation
require_once __DIR__ . '/../auth.php';
require_login_api();
ensure_users_limited_column();

$me = current_user();
if (empty($me['is_manager'])) json_out(['ok' => false, 'msg' => 'دسترسی ندارید'], 403);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pdo = db_connect();
    $stmt = $pdo->query('SELECT id, full_name, username, display_name, email, phone, is_manager, is_limited, avatar_color, created_at, password_hash
                         FROM users ORDER BY id');
    json_out(['ok' => true, 'users' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false], 405);
if (!csrf_valid($_POST['csrf'] ?? '')) json_out(['ok' => false, 'msg' => 'درخواست نامعتبر'], 403);

$action = $_POST['action'] ?? '';
$uid = (int)($_POST['user_id'] ?? 0);
if ($uid === 0) json_out(['ok' => false, 'msg' => 'داده ناقص']);
if ($uid === (int)$me['id']) json_out(['ok' => false, 'msg' => 'نمی‌توانید حساب خود را مدیریت کنید']);

$pdo = db_connect();
$t = $pdo->prepare('SELECT is_manager FROM users WHERE id = ?');
$t->execute([$uid]);
$row = $t->fetch();
if (!$row) json_out(['ok' => false, 'msg' => 'کاربر یافت نشد']);
if ($row['is_manager']) json_out(['ok' => false, 'msg' => 'مدیر را نمی‌توان محدود/تغییر داد']);

// ---- Reset password ----
if ($action === 'reset_password') {
    $np = trim($_POST['new_password'] ?? '');
    if ($np === '') {
        $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $np = '';
        for ($i = 0; $i < 10; $i++) $np .= $chars[random_int(0, strlen($chars) - 1)];
    }
    if (mb_strlen($np) < 6) json_out(['ok' => false, 'msg' => 'رمز عبور حداقل ۶ کاراکتر باشد']);
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$np, $uid]); // plaintext (insecure)
    json_out(['ok' => true, 'generated' => (trim($_POST['new_password'] ?? '') === '') ? $np : null]);
}

// ---- Toggle limitation ----
$pdo->prepare('UPDATE users SET is_limited = 1 - is_limited WHERE id = ?')->execute([$uid]);
$lim = $pdo->prepare('SELECT is_limited FROM users WHERE id = ?');
$lim->execute([$uid]);
json_out(['ok' => true, 'is_limited' => (bool)$lim->fetchColumn()]);
