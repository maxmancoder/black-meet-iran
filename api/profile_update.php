<?php
// api/profile_update.php
require_once __DIR__ . '/../auth.php';
require_login_api();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'msg' => 'متد نامعتبر'], 405);
if (!csrf_valid($_POST['csrf'] ?? '')) json_out(['ok' => false, 'msg' => 'درخواست نامعتبر'], 403);

$me = current_user();
$display = trim($_POST['display_name'] ?? '');
if ($display === '') json_out(['ok' => false, 'msg' => 'نام نمایشی الزامی است']);

$pdo = db_connect();
$pdo->prepare('UPDATE users SET display_name=? WHERE id=?')
    ->execute([$display, $me['id']]);
$_SESSION['display_name'] = $display;
json_out(['ok' => true, 'display_name' => $display]);
