<?php
// api/logout.php
require_once __DIR__ . '/../auth.php';
session_destroy();
json_out(['ok' => true, 'redirect' => base_url() . '/index.php']);
