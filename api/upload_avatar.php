<?php
// api/upload_avatar.php
require_once __DIR__ . '/../auth.php';
require_login_api();
ensure_schema_extras();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false], 405);
if (!csrf_valid($_POST['csrf'] ?? '')) json_out(['ok' => false, 'msg' => 'درخواست نامعتبر'], 403);

$me = current_user();
if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    json_out(['ok' => false, 'msg' => 'فایل انتخاب نشده']);
}
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($_FILES['avatar']['tmp_name']);
if (!isset($allowed[$mime])) json_out(['ok' => false, 'msg' => 'فرمت تصویر پشتیبانی نمی‌شود']);
if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) json_out(['ok' => false, 'msg' => 'حجم تصویر بیش از ۲ مگابایت است']);

$dir = __DIR__ . '/../uploads/avatars';
if (!is_dir($dir)) mkdir($dir, 0755, true);
$ext = $allowed[$mime];
$rel = 'uploads/avatars/' . $me['id'] . '.' . $ext;
$abs = __DIR__ . '/../' . $rel;
if (is_file($abs)) @unlink($abs);
if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $abs)) json_out(['ok' => false, 'msg' => 'آپلود ناموفق']);

$pdo = db_connect();
$pdo->prepare('UPDATE users SET avatar = ? WHERE id = ?')->execute([$rel, $me['id']]);
json_out(['ok' => true, 'avatar' => avatar_url($rel)]);
