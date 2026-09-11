<?php
$title = 'پیام‌ها - Black Meet';
require_once __DIR__ . '/partials/head.php';
require_once __DIR__ . '/auth.php';
require_login();
$me = current_user();
if (empty($me['is_manager'])) { header('Location: ' . base_url() . '/home.php'); exit; }
ensure_schema_extras();
$csrf = csrf_token();
?>
</head>
<body class="bg-background text-on-background font-body-md h-screen overflow-hidden flex flex-col">

<header class="relative z-10 bg-background/80 backdrop-blur-md flex items-center justify-between px-lg py-md border-b border-outline-variant/20">
    <a href="<?= base_url() ?>/home.php" class="flex items-center gap-3">
        <span class="material-symbols-outlined text-on-surface-variant">arrow_forward</span>
        <span class="font-display-md text-display-md font-bold text-primary">Black Meet</span>
    </a>
    <span class="font-headline-md text-headline-md text-on-surface">پیام‌ها (مدیر)</span>
</header>

<main class="relative z-10 flex-1 flex min-h-0">
    <!-- Left: conversations -->
    <aside class="w-72 md:w-80 flex-shrink-0 bg-surface-container-low border-l border-outline-variant/20 flex flex-col">
        <div class="p-md">
            <input id="conv-search" class="w-full bg-surface-container rounded-lg border border-outline-variant/30 py-2 px-3 text-body-sm text-on-surface focus:outline-none focus:border-primary-container" placeholder="جستجوی کاربر..." oninput="filterConvs()"/>
        </div>
        <div id="conv-list" class="flex-1 overflow-y-auto custom-scrollbar p-md pt-0 flex flex-col gap-1"></div>
    </aside>

    <!-- Center: chat -->
    <section class="flex-1 flex flex-col min-w-0 bg-surface-container-lowest">
        <!-- profile header -->
        <div id="profile-header" class="hidden items-center gap-3 px-md py-md border-b border-outline-variant/20 bg-surface-container-low cursor-pointer" onclick="openProfileModal()">
            <div id="ph-avatar" class="w-11 h-11 rounded-full overflow-hidden border border-outline-variant"></div>
            <div>
                <h3 id="ph-name" class="font-headline-md text-headline-md text-on-surface"></h3>
                <p id="ph-status" class="font-label-sm text-on-surface-variant text-[11px]"></p>
            </div>
        </div>

        <div id="chat-area" class="flex-1 overflow-y-auto custom-scrollbar p-md flex flex-col gap-3">
            <div class="m-auto text-center text-on-surface-variant font-body-sm">یک گفتگو را انتخاب کنید</div>
        </div>

        <div class="p-md border-t border-outline-variant/20 bg-surface-container-low">
            <div class="flex items-center gap-2">
                <input id="msg-input" class="flex-1 bg-surface-container rounded-lg border border-outline-variant/30 py-3 px-4 text-body-sm text-on-surface focus:outline-none focus:border-primary-container" placeholder="پیام خود را بنویسید..." onkeydown="if(event.key==='Enter')sendMessage()"/>
                <button class="bg-secondary-container text-on-secondary-container px-4 py-3 rounded-lg" onclick="sendMessage()"><span class="material-symbols-outlined">send</span></button>
            </div>
        </div>
    </section>
</main>

<!-- Profile modal -->
<div id="profile-modal" class="hidden fixed inset-0 z-[80] flex items-center justify-center bg-background/70 backdrop-blur-sm p-4">
    <div class="glass-panel rounded-2xl p-xl max-w-[420px] w-full text-center relative">
        <button class="absolute top-3 left-3 text-on-surface-variant hover:text-on-surface" onclick="closeProfileModal()"><span class="material-symbols-outlined">close</span></button>
        <div id="pm-avatar" class="w-24 h-24 mx-auto rounded-full overflow-hidden border border-outline-variant mb-4"></div>
        <h3 id="pm-name" class="font-headline-md text-headline-md text-on-surface"></h3>
        <p id="pm-username" class="font-label-sm text-on-surface-variant text-[12px] mt-1"></p>
        <p id="pm-email" class="font-label-sm text-on-surface-variant text-[12px] mt-1" dir="ltr"></p>
    </div>
</div>

<div class="fixed bottom-6 right-6 hidden z-50" id="toast"><div class="bg-error-container text-on-error-container px-6 py-3 rounded-lg shadow-xl flex items-center justify-center gap-3">
    <span class="material-symbols-outlined">error</span><span id="toast-message"></span></div></div>

<script>window.CSRF = <?= json_encode($csrf) ?>; window.BASE = <?= json_encode(base_url()) ?>;</script>
<script src="<?= base_url() ?>/assets/js/messages.js"></script>
</body>
</html>
