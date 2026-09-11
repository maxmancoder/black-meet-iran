<?php
// api/verify_otp.php  (shared by signup + login_phone)
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'msg' => 'متد نامعتبر'], 405);
if (!csrf_valid($_POST['csrf'] ?? '')) json_out(['ok' => false, 'msg' => 'درخواست نامعتبر'], 403);

$phone   = trim($_POST['phone'] ?? '');
$code    = trim($_POST['code'] ?? '');
$purpose = $_POST['purpose'] === 'signup' ? 'signup' : 'login_phone';

if ($phone === '' || $code === '') json_out(['ok' => false, 'msg' => 'داده ناقص']);

$pdo = db_connect();
$stmt = $pdo->prepare('SELECT code, expires_at FROM verification_codes
                       WHERE identifier=? AND purpose=?
                       ORDER BY id DESC LIMIT 1');
$stmt->execute([$phone, $purpose]);
$row = $stmt->fetch();
if (!$row) {
    json_out(['ok' => false, 'msg' => 'کد نامعتبر یا منقضی شده است']);
}
// Expiry is checked in PHP to avoid PHP/MySQL timezone mismatch
if (strtotime($row['expires_at']) <= time()) {
    json_out(['ok' => false, 'msg' => 'کد نامعتبر یا منقضی شده است']);
}
if (!hash_equals((string)$row['code'], (string)$code)) {
    json_out(['ok' => false, 'msg' => 'کد نامعتبر یا منقضی شده است']);
}

// consume code
$pdo->prepare('DELETE FROM verification_codes WHERE identifier=? AND purpose=?')
    ->execute([$phone, $purpose]);

$stmt = $pdo->prepare('SELECT id FROM users WHERE phone=?');
$stmt->execute([$phone]);
$user = $stmt->fetch();
if (!$user) json_out(['ok' => false, 'msg' => 'حساب یافت نشد']);

$_SESSION['user_id'] = $user['id'];
json_out(['ok' => true, 'redirect' => base_url() . '/home.php']);
