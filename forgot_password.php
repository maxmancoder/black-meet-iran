<?php
$title = 'بازیابی رمز عبور - Black Meet';
require_once __DIR__ . '/partials/head.php';
$csrf = csrf_token();
?>
</head>
<body class="bg-background text-on-background font-body-md min-h-screen flex flex-col relative overflow-x-hidden">

<div class="fixed inset-0 z-0 opacity-20 pointer-events-none" style="background-image:linear-gradient(135deg,#0b1020 0%,#1e1b4b 55%,#0b1020 100%);background-size:cover;background-position:center;"></div>

<header class="relative z-10 bg-background/80 backdrop-blur-md flex items-center justify-between px-lg py-md border-b border-outline-variant/20">
    <a href="<?= base_url() ?>/index.php" class="font-display-md text-display-md font-bold text-primary">Black Meet</a>
    <button id="help-btn" class="relative text-on-surface-variant hover:text-primary p-2 rounded-full help-bounce" title="راهنما">
        <span class="material-symbols-outlined">help</span>
    </button>
</header>

<main class="relative z-10 flex-1 flex items-center justify-center p-grid-margin">
    <div class="glass-panel rounded-2xl shadow-2xl p-xl w-full max-w-[560px]">
        <h1 class="font-headline-lg text-headline-lg text-on-surface mb-2">بازیابی رمز عبور</h1>
        <p class="font-body-sm text-body-sm text-on-surface-variant mb-lg">شماره تلفن یا ایمیل خود را وارد کنید تا پیام‌های شما با مدیر نمایش داده شود.</p>

        <div id="identify-box" class="flex flex-col gap-3">
            <div class="input-glow rounded-lg bg-surface-container-low border border-outline-variant transition-all">
                <input id="identifier" class="w-full bg-transparent border-none focus:ring-0 text-body-lg text-on-surface placeholder-on-surface-variant/50 px-4 py-4 outline-none" placeholder="شماره تلفن یا ایمیل" type="text"/>
            </div>
            <button class="btn-primary flex items-center justify-center gap-2" onclick="openThread()">
                <span class="material-symbols-outlined">forum</span> مشاهده پیام‌ها
            </button>
        </div>

        <div id="thread-box" class="hidden flex-col gap-3 mt-2">
            <div class="flex items-center justify-between mb-1">
                <h2 class="font-headline-md text-headline-md text-on-surface">گفتگو با مدیر</h2>
                <button class="text-on-surface-variant hover:text-primary text-[12px]" onclick="closeThread()">بستن</button>
            </div>
            <div id="pv-messages" class="h-72 overflow-y-auto custom-scrollbar flex flex-col gap-3 bg-surface-container-low rounded-xl p-3 border border-outline-variant/20"></div>
            <div class="flex items-center gap-2">
                <input id="pv-input" class="flex-1 bg-surface-container rounded-lg border border-outline-variant/30 py-3 px-4 text-body-sm text-on-surface focus:outline-none focus:border-primary-container" placeholder="پیام خود را بنویسید..." onkeydown="if(event.key==='Enter')sendPv()"/>
                <button class="bg-secondary-container text-on-secondary-container px-4 py-3 rounded-lg" onclick="sendPv()"><span class="material-symbols-outlined">send</span></button>
            </div>
        </div>
    </div>
</main>

<!-- Help modal -->
<div id="help-modal" class="hidden fixed inset-0 z-[80] flex items-center justify-center bg-background/70 backdrop-blur-sm p-4">
    <div class="glass-panel rounded-2xl p-xl max-w-[460px] w-full relative">
        <button class="absolute top-3 left-3 text-on-surface-variant hover:text-on-surface" onclick="closeHelp()"><span class="material-symbols-outlined">close</span></button>
        <h3 class="font-headline-md text-headline-md text-on-surface mb-3">راهنمای بازیابی رمز عبور</h3>
        <p class="font-body-sm text-body-sm text-on-surface leading-relaxed">برای بازیابی رمز عبور، لطفاً شماره تلفن، ایمیل و نام کاربری خود را برای مدیر ارسال کنید. پاسخ مدیر در همین صفحه برای شما نمایش داده می‌شود. لطفاً از ارسال پیام‌های غیرضروری خودداری کنید.</p>
    </div>
</div>

<div class="fixed bottom-6 right-6 hidden z-50" id="toast"><div class="bg-error-container text-on-error-container px-6 py-3 rounded-lg shadow-xl flex items-center gap-3">
    <span class="material-symbols-outlined">error</span><span id="toast-message"></span></div></div>

<style>
.help-bounce { animation: helpBounce 1.4s ease-in-out infinite; }
@keyframes helpBounce { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
</style>

<script>window.CSRF = <?= json_encode($csrf) ?>; window.BASE = <?= json_encode(base_url()) ?>;</script>
<script src="<?= base_url() ?>/assets/js/forgot_password.js"></script>
</body>
</html>
