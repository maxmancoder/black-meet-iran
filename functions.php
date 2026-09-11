<?php
// functions.php - shared helpers: config, csrf, otp, token, escaping

function app_config() {
    static $cfg = null;
    if ($cfg === null) {
        $cfg = json_decode(file_get_contents(__DIR__ . '/config.json'), true);
    }
    return $cfg;
}

function socket_url() {
    // Empty = same origin (works behind a tunnel/proxy that forwards /socket.io to the signaling server)
    return '';
}

function turn_config() {
    $c = app_config();
    $t = $c['turn'] ?? null;
    $servers = [
        ['urls' => 'stun:stun.l.google.com:19302'],
        ['urls' => 'stun:stun1.l.google.com:19302'],
    ];
    // Local TURN relay exposed through a public TCP tunnel (bore), so it is
    // reachable by both peers regardless of their NAT. Preferred.
    $hostFile = __DIR__ . '/turn_host.txt';
    if (!empty($t['user']) && !empty($t['pass']) && file_exists($hostFile)) {
        $line = trim(file_get_contents($hostFile));
        $line = str_replace("\xEF\xBB\xBF", '', $line);
        $th = null; $tp = 3478;
        if (preg_match('/^([^:]+):(\d+)$/', $line, $m)) { $th = $m[1]; $tp = (int)$m[2]; }
        elseif ($line !== '') { $th = $line; }
        if ($th) {
            $servers[] = [
                'urls' => 'turn:' . $th . ':' . $tp . '?transport=tcp',
                'username' => $t['user'],
                'credential' => $t['pass'],
            ];
        }
    }
    // Public open-relay fallback (no credentials required)
    $servers[] = ['urls' => 'turn:openrelay.metered.ca:443?transport=tcp'];
    $servers[] = ['urls' => 'turn:openrelay.metered.ca:80?transport=tcp'];
    return $servers;
}

function base_url() {
    // Derive from the incoming request so it works both locally (localhost)
    // and behind a public tunnel (friend uses the real host + https).
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? null);
    if ($host) {
        $proto = 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
        } elseif (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            $proto = 'https';
        } elseif (($p = $_SERVER['SERVER_PORT'] ?? null) && $p == 443) {
            $proto = 'https';
        } else {
            // Behind a public tunnel: friend reaches us over https.
            $h = strtolower($host);
            if ($h !== 'localhost' && $h !== '127.0.0.1' && $h !== '::1') {
                $proto = 'https';
            }
        }
        return rtrim($proto . '://' . $host . '/black-meet', '/');
    }
    $c = app_config();
    return rtrim($c['base_url'], '/');
}

// ---- CSRF ----
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid($token) {
    return isset($_SESSION['csrf']) && is_string($token)
        && hash_equals($_SESSION['csrf'], $token);
}

// ---- OTP ----
function generate_otp($len = 6) {
    $otp = '';
    for ($i = 0; $i < $len; $i++) $otp .= random_int(0, 9);
    return $otp;
}

// ---- Secure token (HMAC) for socket auth ----
function make_socket_token($user_id, $meeting_id) {
    $c = app_config();
    $payload = $user_id . '|' . $meeting_id . '|' . time();
    $sig = hash_hmac('sha256', $payload, $c['secret']);
    return $payload . '.' . $sig;
}

function verify_socket_token($token, &$out = []) {
    $c = app_config();
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) return false;
    $payload = $parts[0];
    $sig = $parts[1];
    $expected = hash_hmac('sha256', $payload, $c['secret']);
    if (!hash_equals($expected, $sig)) return false;
    $fields = explode('|', $payload);
    if (count($fields) !== 3) return false;
    if (time() - (int)$fields[2] > 86400) return false; // 24h
    $out = ['user_id' => (int)$fields[0], 'meeting_id' => (int)$fields[1]];
    return true;
}

// ---- Output escaping (XSS) ----
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function to_fa_digits($num) {
    $map = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    return strtr((string)$num, array_combine(range(0,9), $map));
}

// ---- JSON response helper ----
function json_out($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- Random public room id ----
function generate_room_id($len = 16) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $out = '';
    for ($i = 0; $i < $len; $i++) $out .= $chars[random_int(0, strlen($chars) - 1)];
    return $out;
}

// ---- Avatar initials ----
function initials($name) {
    $parts = preg_split('/\s+/u', trim($name));
    if (count($parts) >= 2) return mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1);
    return mb_substr($name, 0, 2);
}

// ---- On-the-fly schema patch (adds is_limited if missing) ----
function ensure_users_limited_column() {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo = db_connect();
        $r = $pdo->query("SELECT 1 FROM information_schema.columns
                          WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'is_limited'");
        if ($r->fetchColumn() === false) {
            $pdo->exec('ALTER TABLE users ADD COLUMN is_limited TINYINT(1) NOT NULL DEFAULT 0');
        }
    } catch (\Throwable $e) { /* non-fatal */ }
}

// ---- On-the-fly schema patch: avatars, presence, blocks, PM, announcements ----
function ensure_schema_extras() {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo = db_connect();
        // users.avatar + last_activity
        $cols = $pdo->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users'")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('avatar', $cols, true)) $pdo->exec('ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL');
        if (!in_array('last_activity', $cols, true)) $pdo->exec('ALTER TABLE users ADD COLUMN last_activity DATETIME NULL');
        $pdo->exec('CREATE TABLE IF NOT EXISTS meeting_blocks (
            meeting_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (meeting_id, user_id), KEY idx_mb_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $pdo->exec('CREATE TABLE IF NOT EXISTS pv_messages (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT, user_id INT UNSIGNED NOT NULL,
            from_manager TINYINT(1) NOT NULL DEFAULT 0, body TEXT NOT NULL,
            seen TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY idx_uid (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $pdo->exec('CREATE TABLE IF NOT EXISTS announcements (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT, body TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    } catch (\Throwable $e) { /* non-fatal */ }
}

// ---- Ensure manager password is hashed (regular users stay plaintext) ----
function ensure_manager_password_hashed() {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo = db_connect();
        $stmt = $pdo->query("SELECT id, password_hash FROM users WHERE is_manager=1");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ph = (string)$row['password_hash'];
            if ($ph === '' || strpos($ph, '$2y$') === 0) continue; // already hashed
            $hash = password_hash($ph, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$hash, $row['id']]);
        }
    } catch (\Throwable $e) { /* non-fatal */ }
}

// ---- Avatar URL helper ----
function avatar_url($avatar) {
    if (!$avatar) return '';
    if (preg_match('#^https?://#i', $avatar)) return $avatar;
    return rtrim(base_url(), '/') . '/' . ltrim($avatar, '/');
}
