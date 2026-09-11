<?php
$title = 'خانه - Black Meet';
require_once __DIR__ . '/partials/head.php';
require_once __DIR__ . '/auth.php';
require_login();
ensure_users_limited_column();
ensure_schema_extras();

$me = current_user();
$limited = !empty($me['is_limited']);
$pdo = db_connect();

// recent meetings created by user
$stmt = $pdo->prepare('SELECT m.id, m.room_id, m.title, m.created_at, m.active,
                       (SELECT COUNT(*) FROM meeting_participants p WHERE p.meeting_id=m.id AND p.status<>"removed") AS members
                       FROM meetings m WHERE m.creator_id=? ORDER BY m.created_at DESC LIMIT 6');
$stmt->execute([$me['id']]);
$recent = $stmt->fetchAll();

function time_ago($dt) {
    $t = time() - strtotime($dt);
    if ($t < 60) return 'همین الان';
    if ($t < 3600) return floor($t/60) . ' دقیقه پیش';
    if ($t < 86400) return floor($t/3600) . ' ساعت پیش';
    return floor($t/86400) . ' روز پیش';
}
$initials = initials($me['display_name']);
$csrf = csrf_token();
?>
</head>
<body class="bg-background text-on-background font-body-md min-h-screen overflow-x-hidden flex">

<!-- ambient background -->
<div class="fixed inset-0 z-0 opacity-20 pointer-events-none" style="background-image:linear-gradient(135deg,#0b1020 0%,#1e1b4b 55%,#0b1020 100%);background-size:cover;background-position:center;"></div>

<!-- Sidebar (desktop) -->
<nav aria-label="Sidebar" class="hidden md:flex flex-col py-lg bg-surface-container-low border-r border-white/5 shadow-2xl fixed inset-y-0 left-0 w-80 z-[60]">
    <div class="flex items-center gap-sm px-lg mb-8">
        <div class="w-12 h-12 rounded-full overflow-hidden border border-outline-variant flex items-center justify-center font-display-md text-display-md text-white" style="background:<?= e($me['avatar_color']) ?>"><?php if (!empty($me['avatar'])): ?><img src="<?= e(avatar_url($me['avatar'])) ?>" class="w-full h-full object-cover" alt=""/><?php else: ?><?= e($initials) ?><?php endif; ?></div>
        <div class="flex flex-col">
            <span class="font-headline-md text-headline-md text-primary"><?= e($me['display_name']) ?></span>
            <span class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-2">
                <?= $me['is_manager'] ? 'مدیر' : 'کاربر عادی' ?>
                <span class="w-2 h-2 rounded-full bg-secondary"></span> آنلاین
            </span>
        </div>
    </div>
    <ul class="flex-1 space-y-2 font-body-md text-body-md">
        <li><a class="flex items-center gap-4 py-3 px-4 bg-primary-container text-on-primary-container rounded-lg mx-2" href="<?= base_url() ?>/home.php">
            <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">video_library</span> تماس‌ها</a></li>
        <li><a class="flex items-center gap-4 py-3 px-4 text-on-surface-variant hover:bg-surface-container-high rounded-lg mx-2 transition-all" href="<?= base_url() ?>/profile.php">
            <span class="material-symbols-outlined">person</span> پروفایل</a></li>
        <?php if ($me['is_manager']): ?>
        <li><a class="flex items-center gap-4 py-3 px-4 text-on-surface-variant hover:bg-surface-container-high rounded-lg mx-2 transition-all" href="<?= base_url() ?>/messages.php">
            <span class="material-symbols-outlined">forum</span> پیام‌ها</a></li>
        <li><a class="flex items-center gap-4 py-3 px-4 text-on-surface-variant hover:bg-surface-container-high rounded-lg mx-2 transition-all" href="<?= base_url() ?>/admin_members.php">
            <span class="material-symbols-outlined">group_off</span> مدیریت اعضا</a></li>
        <?php endif; ?>
        <li><a class="flex items-center gap-4 py-3 px-4 text-on-surface-variant hover:bg-surface-container-high rounded-lg mx-2 transition-all" href="#" onclick="logout(event)">
            <span class="material-symbols-outlined">logout</span> خروج</a></li>
    </ul>
</nav>

<!-- Main -->
<div class="flex-1 flex flex-col min-h-screen relative z-10 w-full md:pl-80">
    <!-- Top bar -->
    <header class="bg-background/80 backdrop-blur-md fixed top-0 w-full z-50 flex items-center justify-between px-lg py-md">
        <div class="flex items-center gap-3">
            <a href="<?= base_url() ?>/home.php"><span class="font-display-md text-display-md font-bold text-primary">Black Meet</span></a>
        </div>
            <div class="flex items-center gap-3">
            <button id="bell-btn" class="relative text-on-surface-variant hover:bg-surface-variant/20 p-2 rounded-full" onclick="toggleNotifications()" title="اعلان‌ها">
                <span class="material-symbols-outlined">notifications</span>
                <span id="bell-dot" class="hidden absolute top-1 right-1 w-2 h-2 bg-error rounded-full"></span>
            </button>
            <a href="<?= base_url() ?>/profile.php" title="پروفایل" class="w-10 h-10 rounded-full overflow-hidden border border-outline-variant flex items-center justify-center font-headline-md text-white hover:opacity-90 transition" style="background:<?= e($me['avatar_color']) ?>"><?php if (!empty($me['avatar'])): ?><img src="<?= e(avatar_url($me['avatar'])) ?>" class="w-full h-full object-cover" alt=""/><?php else: ?><?= e($initials) ?><?php endif; ?></a>
            <button class="text-on-surface-variant hover:bg-surface-variant/20 p-2 rounded-full" onclick="logout(event)"><span class="material-symbols-outlined">logout</span></button>
        </div>
    </header>

    <!-- Dashboard -->
    <main class="flex-1 pt-24 pb-28 px-4 md:px-lg max-w-7xl mx-auto w-full flex flex-col gap-8">
        <div class="flex items-center gap-4 mt-4">
            <h1 class="font-display-md text-display-md text-on-surface">خوش آمدی <?= e(explode(' ', $me['display_name'])[0]) ?></h1>
            <?php if ($me['is_manager']): ?>
            <span class="manager-badge font-label-sm text-label-sm px-3 py-1 rounded-full flex items-center gap-1 shadow-lg">
                <span class="material-symbols-outlined text-[16px]" style="font-variation-settings:'FILL' 1;">shield_person</span> مدیر
            </span>
            <?php endif; ?>
        </div>

        <!-- Core actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
            <?php if ($limited): ?>
            <button class="hero-glow group relative overflow-hidden bg-primary-container text-on-primary-container rounded-xl p-8 flex flex-col items-center justify-center min-h-[200px] opacity-60 cursor-not-allowed" onclick="document.getElementById('toast-message').textContent='حساب شما محدود شده و امکان ایجاد تماس جدید وجود ندارد';document.getElementById('toast').classList.remove('hidden');setTimeout(()=>document.getElementById('toast').classList.add('hidden'),4000)">
                <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <span class="material-symbols-outlined text-[64px] mb-4 drop-shadow-md" style="font-variation-settings:'FILL' 1;">block</span>
                <h2 class="font-headline-lg text-headline-lg font-semibold">ایجاد تماس جدید</h2>
                <p class="font-body-sm text-body-sm text-on-primary-container/80 mt-2">شروع یک تماس ویدیویی امن</p>
            </button>
            <?php else: ?>
            <button class="hero-glow group relative overflow-hidden bg-primary-container text-on-primary-container rounded-xl p-8 flex flex-col items-center justify-center min-h-[200px] transition-transform duration-300 hover:scale-[1.02]" onclick="location.href='<?= base_url() ?>/create.php'">
                <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <span class="material-symbols-outlined text-[64px] mb-4 drop-shadow-md" style="font-variation-settings:'FILL' 1;">video_call</span>
                <h2 class="font-headline-lg text-headline-lg font-semibold">ایجاد تماس جدید</h2>
                <p class="font-body-sm text-body-sm text-on-primary-container/80 mt-2">شروع یک تماس ویدیویی امن</p>
            </button>
            <?php endif; ?>

            <div class="glass-panel rounded-xl p-8 flex flex-col justify-center min-h-[200px] shadow-lg">
                <h2 class="font-headline-md text-headline-md text-on-surface mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary">link</span> پیوستن با لینک
                </h2>
                <form id="join-form" class="flex flex-col sm:flex-row gap-4 w-full">
                    <div class="relative flex-1">
                        <input id="join-input" class="w-full bg-surface-container-highest text-on-surface border border-outline-variant rounded-lg px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all placeholder:text-on-surface-variant/50" placeholder="آدرس لینک یا کد تماس را وارد کنید..." type="text"/>
                    </div>
                    <button type="submit" class="bg-surface-variant text-on-surface-variant hover:bg-secondary hover:text-on-secondary px-6 py-3 rounded-lg font-label-md text-label-md transition-colors duration-200 flex items-center justify-center gap-2 whitespace-nowrap">
                        <span class="material-symbols-outlined">login</span> پیوستن
                    </button>
                </form>
            </div>
        </div>

        <!-- Recent meetings -->
        <section class="flex flex-col gap-4 mt-4">
            <h3 class="font-headline-md text-headline-md text-on-surface mb-2">جلسات اخیر</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php if (empty($recent)): ?>
                <div class="border border-dashed border-outline-variant rounded-lg p-5 flex flex-col items-center justify-center text-center opacity-70 min-h-[140px]">
                    <span class="material-symbols-outlined text-outline mb-2 text-[32px]">calendar_add_on</span>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">هنوز جلسه‌ای ایجاد نکرده‌اید</p>
                </div>
                <?php else: foreach ($recent as $m): ?>
                <div class="glass-panel rounded-lg p-5 transition-colors group relative cursor-not-allowed opacity-80">
                    <div class="flex justify-between items-start mb-4">
                        <div class="bg-surface-variant rounded-md p-2 text-primary"><span class="material-symbols-outlined">groups</span></div>
                        <span class="font-label-sm text-label-sm text-on-surface-variant"><?= e(time_ago($m['created_at'])) ?></span>
                    </div>
                    <h4 class="font-body-lg text-body-lg text-on-surface font-medium mb-1"><?= e($m['title']) ?></h4>
                    <p class="font-body-sm text-body-sm text-on-surface-variant"><?= (int)$m['members'] ?> عضو</p>
                    <span class="absolute top-3 left-3 material-symbols-outlined text-outline-variant" title="عضو خصوصی">lock</span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </section>
    </main>
</div>

<!-- Notifications popup (manager only) -->
<div id="notif-popup" class="hidden fixed top-16 left-auto right-4 z-[70] w-80 max-h-[70vh] glass-panel rounded-2xl shadow-2xl p-4 flex flex-col">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-headline-md text-headline-md text-on-surface">اعلان‌ها</h3>
        <button onclick="toggleNotifications()" class="text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
    </div>
    <div class="flex items-center gap-2 mb-3">
        <span class="font-label-sm text-on-surface-variant">مرتب‌سازی:</span>
        <button id="sort-old" class="text-[12px] px-2 py-1 rounded-md bg-surface-container-high text-on-surface" onclick="setNotifSort('old')">قدیمی</button>
        <button id="sort-new" class="text-[12px] px-2 py-1 rounded-md bg-secondary-container text-on-secondary-container" onclick="setNotifSort('new')">جدید</button>
    </div>
    <div id="notif-list" class="overflow-y-auto custom-scrollbar flex-1 flex flex-col gap-2"></div>
</div>

<!-- Mobile bottom nav -->
<nav class="md:hidden bg-surface-container/60 fixed bottom-0 w-full z-50 rounded-t-xl backdrop-blur-xl border-t border-white/10 shadow-xl flex justify-around items-center h-20 px-md">
    <a class="flex flex-col items-center justify-center bg-secondary-container text-on-secondary-container rounded-full px-4 py-1" href="<?= base_url() ?>/home.php">
        <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">home</span><span class="font-label-md text-label-md mt-1">خانه</span></a>
    <a class="flex flex-col items-center justify-center text-on-surface-variant" href="<?= $limited ? '#' : base_url() . '/create.php' ?>" onclick="<?= $limited ? "document.getElementById('toast-message').textContent='حساب شما محدود شده و امکان ایجاد تماس جدید وجود ندارد';document.getElementById('toast').classList.remove('hidden');setTimeout(()=>document.getElementById('toast').classList.add('hidden'),4000);return false;" : '' ?>">
        <span class="material-symbols-outlined">video_call</span><span class="font-label-md text-label-md mt-1">تماس</span></a>
    <a class="flex flex-col items-center justify-center text-on-surface-variant" href="<?= base_url() ?>/profile.php">
        <span class="material-symbols-outlined">person</span><span class="font-label-md text-label-md mt-1">پروفایل</span></a>
</nav>

<div class="fixed bottom-6 right-6 hidden z-50" id="toast"><div class="bg-error-container text-on-error-container px-6 py-3 rounded-lg shadow-xl flex items-center gap-3">
    <span class="material-symbols-outlined">error</span><span id="toast-message"></span></div></div>

<script>window.CSRF = <?= json_encode($csrf) ?>; window.BASE = <?= json_encode(base_url()) ?>; window.__isManager = <?= json_encode(!empty($me['is_manager'])) ?>;</script>
<script src="<?= base_url() ?>/assets/js/home.js"></script>
</body>
</html>
