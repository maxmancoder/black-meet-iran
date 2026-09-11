<?php
// api/login_phone.php  (step 1: send OTP)
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'msg' => 'متد نامعتبر'], 405);
if (!csrf_valid($_POST['csrf'] ?? '')) json_out(['ok' => false, 'msg' => 'درخواست نامعتبر'], 403);

$phone = trim($_POST['phone'] ?? '');
if (!preg_match('/^[0-9]{10,15}$/', $phone)) json_out(['ok' => false, 'msg' => 'شماره تلفن نامعتبر است']);

$pdo = db_connect();
$stmt = $pdo->prepare('SELECT id FROM users WHERE phone=?');
$stmt->execute([$phone]);
if (!$stmt->fetch()) {
    json_out(['ok' => false, 'msg' => 'حساب وجود ندارد']);
}

$code = generate_otp();
$expires = date('Y-m-d H:i:s', time() + 300);
$pdo->prepare('DELETE FROM verification_codes WHERE identifier=? AND purpose=?')
    ->execute([$phone, 'login_phone']);
$pdo->prepare('INSERT INTO verification_codes (identifier,code,purpose,expires_at) VALUES (?,?,?,?)')
    ->execute([$phone, $code, 'login_phone', $expires]);

json_out(['ok' => true, 'otp' => $code, 'phone' => $phone, 'purpose' => 'login_phone']);
