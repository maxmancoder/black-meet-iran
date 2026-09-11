<?php
// api/login_email.php
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'msg' => 'متد نامعتبر'], 405);
if (!csrf_valid($_POST['csrf'] ?? '')) json_out(['ok' => false, 'msg' => 'درخواست نامعتبر'], 403);

$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';

$pdo = db_connect();
$stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email=?');
$stmt->execute([$email]);
$user = $stmt->fetch();

$ok = hash_equals((string)$user['password_hash'], (string)$pass)
    || password_verify($pass, $user['password_hash']); // accept plaintext or legacy bcrypt
if (!$user || !$ok) {
    json_out(['ok' => false, 'msg' => 'حساب وجود ندارد']);
}

$_SESSION['user_id'] = $user['id'];
json_out(['ok' => true, 'redirect' => base_url() . '/home.php']);
