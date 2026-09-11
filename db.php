<?php
// db.php - PDO connection loader (reads config.json)
require_once __DIR__ . '/functions.php';

function db_connect() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $cfg = app_config();
    $dsn = 'mysql:host=' . $cfg['db']['host'] . ';dbname=' . $cfg['db']['name']
         . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, $cfg['db']['user'], $cfg['db']['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'msg' => 'خطای پایگاه داده']);
        exit;
    }
    return $pdo;
}
