<?php
// auth.php - session bootstrap + auth guards + current user
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function current_user() {
    if (empty($_SESSION['user_id'])) return null;
    // Self-heal: ensure new columns exist (older databases)
    static $patched = false;
    if (!$patched) {
        $patched = true;
        try {
            $p = db_connect();
            $cols = ['is_limited' => 'TINYINT(1) NOT NULL DEFAULT 0',
                     'avatar' => 'VARCHAR(255) NULL',
                     'last_activity' => 'DATETIME NULL'];
            foreach ($cols as $col => $def) {
                $r = $p->query("SELECT 1 FROM information_schema.columns
                                WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = '$col'");
                if ($r->fetchColumn() === false) {
                    $p->exec("ALTER TABLE users ADD COLUMN $col $def");
                }
            }
        } catch (\Throwable $e) { /* non-fatal */ }
    }
    static $pwHashed = false;
    if (!$pwHashed) {
        $pwHashed = true;
        ensure_manager_password_hashed();
    }
    static $u = false;
    if ($u !== false) return $u ?: null;
    $pdo = db_connect();
    $stmt = $pdo->prepare('SELECT id, full_name, username, email, phone, display_name, is_manager, is_limited, avatar_color, avatar, last_activity
                           FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    return $u ?: null;
}

// Redirect to login if not authenticated (for internal pages)
function require_login() {
    if (current_user() === null) {
        header('Location: ' . base_url() . '/index.php');
        exit;
    }
}

// API guard: returns JSON error instead of redirect
function require_login_api() {
    if (current_user() === null) {
        json_out(['ok' => false, 'msg' => 'احراز هویت نشده'], 401);
    }
}
