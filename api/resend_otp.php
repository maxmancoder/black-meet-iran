<?php
// api/resend_otp.php
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'msg' => 'متد نامعتبر'], 405);
if (!csrf_valid($_POST['csrf'] ?? '')) json_out(['ok' => false, 'msg' => 'درخواست نامعتبر'], 403);

$phone   = trim($_POST['phone'] ?? '');
$purpose = $_POST['purpose'] === 'signup' ? 'signup' : 'login_phone';
if (!preg_match('/^[0-9]{10,15}$/', $phone)) json_out(['ok' => false, 'msg' => 'شماره نامعتبر است']);

$pdo = db_connect();
$code = generate_otp();
$expires = date('Y-m-d H:i:s', time() + 300);
$pdo->prepare('DELETE FROM verification_codes WHERE identifier=? AND purpose=?')
    ->execute([$phone, $purpose]);
$pdo->prepare('INSERT INTO verification_codes (identifier,code,purpose,expires_at) VALUES (?,?,?,?)')
    ->execute([$phone, $code, $purpose, $expires]);

json_out(['ok' => true, 'otp' => $code, 'msg' => 'کد جدید ارسال شد']);
