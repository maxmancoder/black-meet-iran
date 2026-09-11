<?php
$title = 'ایجاد تماس - Black Meet';
require_once __DIR__ . '/partials/head.php';
require_once __DIR__ . '/auth.php';
require_login();
$me = current_user();
$csrf = csrf_token();
$limited = !empty($me['is_limited']);
?>
</head>
<body class="bg-background text-on-background font-body-md min-h-screen flex flex-col relative overflow-hidden">

<div class="fixed inset-0 z-0 opacity-20 pointer-events-none" style="background-image:linear-gradient(135deg,#0b1020 0%,#1e1b4b 55%,#0b1020 100%);background-size:cover;background-position:center;"></div>

<header class="relative z-10 bg-background/80 backdrop-blur-md flex items-center justify-between px-lg py-md">
    <a href="<?= base_url() ?>/home.php" class="flex items-center gap-3">
        <span class="material-symbols-outlined text-on-surface-variant">arrow_forward</span>
        <span class="font-display-md text-display-md font-bold text-primary">Black Meet</span>
    </a>
    <a href="<?= base_url() ?>/home.php" class="text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">close</span></a>
</header>

<main class="relative z-10 flex-1 flex items-center justify-center p-grid-margin">
    <div class="glass-panel rounded-2xl shadow-2xl p-xl w-full max-w-[520px]">
        <div class="flex items-center gap-3 mb-lg">
            <span class="material-symbols-outlined text-[40px] text-primary" style="font-variation-settings:'FILL' 1;">video_call</span>
            <div>
                <h1 class="font-headline-lg text-headline-lg text-on-surface">ایجاد تماس جدید</h1>
                <p class="font-body-sm text-body-sm text-on-surface-variant">عنوان تماس را وارد کنید</p>
            </div>
        </div>

        <div class="input-glow rounded-lg bg-surface-container-low border border-outline-variant transition-all">
            <input id="meeting-title" class="w-full bg-transparent border-none focus:ring-0 text-body-lg text-on-surface placeholder-on-surface-variant/50 px-4 py-4 outline-none" placeholder="مثال: جلسه هفتگی تیم" type="text" maxlength="120"/>
        </div>

        <button id="create-btn" class="btn-primary mt-lg flex items-center justify-center gap-2" onclick="createMeeting()" <?= $limited ? 'disabled' : '' ?>>
            <span class="material-symbols-outlined">arrow_back</span> ادامه
        </button>

        <?php if ($limited): ?>
        <div class="mt-lg flex items-center gap-2 bg-error-container/80 text-on-error-container rounded-lg px-4 py-3">
            <span class="material-symbols-outlined">block</span>
            <span class="font-body-sm">حساب کاربری شما محدود شده است؛ امکان ایجاد تماس جدید وجود ندارد، فقط می‌توانید به تماس‌های دیگر بپیوندید.</span>
        </div>
        <?php endif; ?>
    </div>
</main>

<div class="fixed bottom-6 right-6 hidden z-50" id="toast"><div class="bg-error-container text-on-error-container px-6 py-3 rounded-lg shadow-xl flex items-center gap-3">
    <span class="material-symbols-outlined">error</span><span id="toast-message"></span></div></div>

<script>window.CSRF = <?= json_encode($csrf) ?>; window.BASE = <?= json_encode(base_url()) ?>;</script>
<script src="<?= base_url() ?>/assets/js/create.js"></script>
</body>
</html>
