<?php
$title = 'مدیریت اعضا - Black Meet';
require_once __DIR__ . '/partials/head.php';
require_once __DIR__ . '/auth.php';
require_login();
$me = current_user();
if (empty($me['is_manager'])) { header('Location: ' . base_url() . '/home.php'); exit; }
ensure_users_limited_column();
$csrf = csrf_token();
$pdo = db_connect();
$stmt = $pdo->query('SELECT id, full_name, username, display_name, email, phone, is_manager, is_limited, avatar_color, avatar, created_at, password_hash
                     FROM users ORDER BY id');
$users = $stmt->fetchAll();
?>
</head>
<body class="bg-background text-on-background font-body-md min-h-screen flex flex-col relative overflow-x-hidden">

<div class="fixed inset-0 z-0 opacity-20 pointer-events-none" style="background-image:linear-gradient(135deg,#0b1020 0%,#1e1b4b 55%,#0b1020 100%);background-size:cover;background-position:center;"></div>

<header class="relative z-10 bg-background/80 backdrop-blur-md flex items-center justify-between px-lg py-md border-b border-outline-variant/20">
    <a href="<?= base_url() ?>/home.php" class="flex items-center gap-3">
        <span class="material-symbols-outlined text-on-surface-variant">arrow_forward</span>
        <span class="font-display-md text-display-md font-bold text-primary">Black Meet</span>
    </a>
    <span class="font-headline-md text-headline-md text-on-surface">مدیریت اعضا</span>
</header>

<main class="relative z-10 flex-1 p-grid-margin">
    <div class="glass-panel rounded-2xl shadow-2xl p-xl w-full max-w-6xl mx-auto">
        <div class="flex items-center gap-3 mb-md">
            <span class="material-symbols-outlined text-[36px] text-primary" style="font-variation-settings:'FILL' 1;">group_off</span>
            <div>
                <h1 class="font-headline-lg text-headline-lg text-on-surface">لیست همه اعضا</h1>
                <p class="font-body-sm text-body-sm text-on-surface-variant">مدیر می‌تواند هر کاربری را محدود یا از محدودیت خارج کند. کاربر محدود‌شده نمی‌تواند تماس جدید ایجاد کند، اما می‌تواند به تماس‌ها بپیوندد.</p>
            </div>
        </div>

        <!-- Toolbar: live search + page size -->
        <div class="flex flex-col md:flex-row md:items-center gap-md mb-md">
            <div class="relative flex-1">
                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">search</span>
                <input id="member-search" class="w-full bg-surface-container rounded-lg border border-outline-variant/30 py-3 pr-11 pl-4 text-body-sm text-on-surface placeholder-on-surface-variant/50 focus:outline-none focus:border-primary-container focus:ring-1 focus:ring-primary-container transition-all" placeholder="جستجوی نام نمایشی، نام کاربری، ایمیل یا شماره تلفن..." type="text" autocomplete="off"/>
            </div>
            <div class="flex items-center gap-2">
                <span class="font-body-sm text-on-surface-variant whitespace-nowrap">نمایش</span>
                <select id="page-size" class="bg-surface-container rounded-lg border border-outline-variant/30 py-3 px-3 text-body-sm text-on-surface focus:outline-none focus:border-primary-container transition-all">
                    <option value="25">۲۵ کاربر</option>
                    <option value="50">۵۰ کاربر</option>
                    <option value="100">۱۰۰ کاربر</option>
                </select>
            </div>
        </div>
        <p id="result-count" class="font-body-sm text-on-surface-variant mb-md"></p>

        <div class="overflow-x-auto custom-scrollbar rounded-xl border border-outline-variant/20">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant font-label-md">
                        <th class="p-3 whitespace-nowrap">کاربر</th>
                        <th class="p-3 whitespace-nowrap">نام کاربری</th>
                        <th class="p-3 whitespace-nowrap">نام نمایشی</th>
                        <th class="p-3 whitespace-nowrap">ایمیل</th>
                        <th class="p-3 whitespace-nowrap">رمز عبور</th>
                        <th class="p-3 whitespace-nowrap">تلفن</th>
                        <th class="p-3 whitespace-nowrap">نقش</th>
                        <th class="p-3 whitespace-nowrap">وضعیت</th>
                        <th class="p-3 whitespace-nowrap">عملیات</th>
                    </tr>
                </thead>
                <tbody id="user-tbody"></tbody>
            </table>
        </div>
        <p class="font-body-sm text-on-surface-variant mt-md">رمز مدیر به‌صورت هش (غیرقابل بازگشت) ذخیره و مخفی است؛ رمز سایر کاربران به‌صورت متن ساده نمایش داده می‌شود. برای تغییر رمز هر کاربر از دکمه «تغییر رمز» استفاده کنید.</p>
    </div>
</main>

<div class="fixed bottom-6 right-6 hidden z-50" id="toast"><div class="bg-error-container text-on-error-container px-6 py-3 rounded-lg shadow-xl flex items-center gap-3">
    <span class="material-symbols-outlined">error</span><span id="toast-message"></span></div></div>

<script>window.CSRF = <?= json_encode($csrf) ?>; window.BASE = <?= json_encode(base_url()) ?>; window.ME_ID = <?= (int)$me['id'] ?>; window.INIT_USERS = <?= json_encode($users, JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="<?= base_url() ?>/assets/js/admin_members.js"></script>
</body>
</html>
