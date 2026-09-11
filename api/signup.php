<?php
// api/signup.php
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'msg' => 'متد نامعتبر'], 405);
if (!csrf_valid($_POST['csrf'] ?? '')) json_out(['ok' => false, 'msg' => 'درخواست نامعتبر'], 403);

$full  = trim($_POST['full_name'] ?? '');
$user  = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$pass  = $_POST['password'] ?? '';

if ($full === '' || $user === '' || $email === '' || $phone === '' || $pass === '') {
    json_out(['ok' => false, 'msg' => 'تمام فیلدها الزامی هستند']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['ok' => false, 'msg' => 'ایمیل نامعتبر است']);
if (!preg_match('/^[0-9]{10,15}$/', $phone)) json_out(['ok' => false, 'msg' => 'شماره تلفن نامعتبر است']);

$pdo = db_connect();
$stmt = $pdo->prepare('SELECT id FROM users WHERE username=? OR email=? OR phone=?');
$stmt->execute([$user, $email, $phone]);
if ($stmt->fetch()) {
    json_out(['ok' => false, 'msg' => 'اطلاعات وارد شده تکراری میباشد']);
}

$hash = $pass; // stored as plaintext (insecure, per owner request)
$display = $full;
$colors = ['#4f46e5', '#00a572', '#bf0f3c', '#c3c0ff', '#4edea3', '#ffb2b7'];
$avatarColor = $colors[array_rand($colors)];

$stmt = $pdo->prepare('INSERT INTO users (full_name,username,email,phone,password_hash,display_name,avatar_color)
                       VALUES (?,?,?,?,?,?,?)');
$stmt->execute([$full, $user, $email, $phone, $hash, $display, $avatarColor]);

// Generate OTP for phone verification
$code = generate_otp();
$expires = date('Y-m-d H:i:s', time() + 300);
$pdo->prepare('DELETE FROM verification_codes WHERE identifier=? AND purpose=?')
    ->execute([$phone, 'signup']);
$pdo->prepare('INSERT INTO verification_codes (identifier,code,purpose,expires_at) VALUES (?,?,?,?)')
    ->execute([$phone, $code, 'signup', $expires]);

json_out([
    'ok'       => true,
    'otp'      => $code,
    'phone'    => $phone,
    'purpose'  => 'signup',
    'msg'      => 'کد تأیید تولید شد'
]);
