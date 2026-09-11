<?php
$title = 'پروفایل - Black Meet';
require_once __DIR__ . '/partials/head.php';
require_once __DIR__ . '/auth.php';
require_login();
$me = current_user();
$initials = initials($me['display_name']);
$avatarUrl = avatar_url($me['avatar']);
$csrf = csrf_token();
?>
</head>
<body class="bg-background text-on-background font-body-md min-h-screen overflow-x-hidden flex">

<div class="fixed inset-0 z-0 opacity-20 pointer-events-none" style="background-image:linear-gradient(135deg,#0b1020 0%,#1e1b4b 55%,#0b1020 100%);background-size:cover;background-position:center;"></div>

<nav aria-label="Sidebar" class="hidden md:flex flex-col py-lg bg-surface-container-low border-r border-white/5 shadow-2xl fixed inset-y-0 left-0 w-80 z-[60]">
        <div class="flex items-center gap-sm px-lg mb-8">
            <div class="w-12 h-12 rounded-full overflow-hidden flex items-center justify-center font-display-md text-display-md text-white" style="background:<?= e($me['avatar_color']) ?>">
                <?php if ($avatarUrl): ?><img src="<?= e($avatarUrl) ?>" class="w-full h-full object-cover" alt=""/><?php else: ?><?= e($initials) ?><?php endif; ?>
            </div>
        <div class="flex flex-col">
            <span class="font-headline-md text-headline-md text-primary"><?= e($me['display_name']) ?></span>
            <span class="font-body-sm text-body-sm text-on-surface-variant"><?= $me['is_manager'] ? 'مدیر' : 'کاربر عادی' ?></span>
        </div>
    </div>
    <ul class="flex-1 space-y-2 font-body-md text-body-md">
        <li><a class="flex items-center gap-4 py-3 px-4 text-on-surface-variant hover:bg-surface-container-high rounded-lg mx-2 transition-all" href="<?= base_url() ?>/home.php"><span class="material-symbols-outlined">video_library</span> تماس‌ها</a></li>
        <li><a class="flex items-center gap-4 py-3 px-4 bg-primary-container text-on-primary-container rounded-lg mx-2" href="<?= base_url() ?>/profile.php"><span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">person</span> پروفایل</a></li>
        <?php if ($me['is_manager']): ?>
        <li><a class="flex items-center gap-4 py-3 px-4 text-on-surface-variant hover:bg-surface-container-high rounded-lg mx-2 transition-all" href="<?= base_url() ?>/admin_members.php"><span class="material-symbols-outlined">group_off</span> مدیریت اعضا</a></li>
        <?php endif; ?>
        <li><a class="flex items-center gap-4 py-3 px-4 text-on-surface-variant hover:bg-surface-container-high rounded-lg mx-2 transition-all" href="#" onclick="logout(event)"><span class="material-symbols-outlined">logout</span> خروج</a></li>
    </ul>
</nav>

<div class="flex-1 flex flex-col min-h-screen relative z-10 w-full md:pl-80">
    <header class="bg-background/80 backdrop-blur-md fixed top-0 w-full z-50 flex items-center justify-between px-lg py-md">
        <a href="<?= base_url() ?>/home.php" class="font-display-md text-display-md font-bold text-primary">Black Meet</a>
        <a href="<?= base_url() ?>/home.php" class="text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">close</span></a>
    </header>

    <main class="flex-1 pt-24 pb-20 px-4 md:px-lg max-w-3xl mx-auto w-full">
        <div class="glass-panel rounded-2xl shadow-2xl p-xl">
            <div class="flex items-center gap-md mb-lg">
                <div class="w-20 h-20 rounded-full overflow-hidden flex items-center justify-center font-display-lg text-display-lg text-white" style="background:<?= e($me['avatar_color']) ?>">
                    <?php if ($avatarUrl): ?><img src="<?= e($avatarUrl) ?>" class="w-full h-full object-cover" alt=""/><?php else: ?><?= e($initials) ?><?php endif; ?>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="font-display-md text-display-md text-on-surface"><?= e($me['display_name']) ?></h1>
                        <?php if ($me['is_manager']): ?>
                        <span class="manager-badge font-label-sm text-label-sm px-3 py-1 rounded-full flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]" style="font-variation-settings:'FILL' 1;">shield_person</span> مدیر
                        </span>
                        <?php endif; ?>
                    </div>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">@<?= e($me['username']) ?></p>
                </div>
            </div>

            <div class="mt-4">
                <input id="avatar-input" type="file" accept="image/*" class="hidden" onchange="uploadAvatar()"/>
                <button type="button" onclick="document.getElementById('avatar-input').click()" class="text-[12px] px-3 py-1.5 rounded-md bg-surface-container-high text-on-surface hover:opacity-80 transition">تغییر تصویر پروفایل</button>
                <p class="font-label-sm text-on-surface-variant text-[11px] mt-1">فرمت‌های مجاز تصویر (حداکثر ۲ مگابایت).</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-md mb-lg">
                <div><p class="font-label-sm text-on-surface-variant text-[12px]">نام نمایشی</p><p class="font-body-md text-on-surface"><?= e($me['display_name']) ?></p></div>
                <div><p class="font-label-sm text-on-surface-variant text-[12px]">نام کامل</p><p class="font-body-md text-on-surface"><?= e($me['full_name']) ?></p></div>
                <div><p class="font-label-sm text-on-surface-variant text-[12px]">ایمیل</p><p class="font-body-md text-on-surface" dir="ltr"><?= e($me['email']) ?></p></div>
                <div><p class="font-label-sm text-on-surface-variant text-[12px]">شماره موبایل</p><p class="font-body-md text-on-surface" dir="ltr"><?= e($me['phone']) ?></p></div>
                <div><p class="font-label-sm text-on-surface-variant text-[12px]">نام کاربری</p><p class="font-body-md text-on-surface" dir="ltr">@<?= e($me['username']) ?></p></div>
                <div><p class="font-label-sm text-on-surface-variant text-[12px]">نقش</p><p class="font-body-md text-on-surface"><?= $me['is_manager'] ? 'مدیر' : 'کاربر عادی' ?></p></div>
            </div>
            <div class="rounded-lg bg-surface-container-low border border-outline-variant/30 px-4 py-3 text-body-sm text-on-surface-variant">
                برای تغییر رمز عبور به صفحه فراموشی رمز مراجعه کنید.
            </div>

            <?php if ($me['is_manager']): ?>
            <a href="<?= base_url() ?>/admin_members.php" class="mt-lg flex items-center justify-between bg-error-container/15 border border-error/30 hover:bg-error-container/25 transition-colors rounded-xl px-5 py-4">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[28px] text-error">group_off</span>
                    <div>
                        <p class="font-label-md text-on-surface">مدیریت اعضا</p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">مدیریت کاربران و دسترسی‌ها</p>
                    </div>
                </div>
                <span class="material-symbols-outlined text-on-surface-variant">arrow_forward</span>
            </a>
            <?php endif; ?>
        </div>
    </main>
</div>

<div class="fixed bottom-6 right-6 hidden z-50" id="toast"><div class="bg-error-container text-on-error-container px-6 py-3 rounded-lg shadow-xl flex items-center gap-3">
    <span class="material-symbols-outlined">error</span><span id="toast-message"></span></div></div>

<script>window.CSRF = <?= json_encode($csrf) ?>; window.BASE = <?= json_encode(base_url()) ?>;</script>
<script src="<?= base_url() ?>/assets/js/profile.js"></script>
</body>
</html>
