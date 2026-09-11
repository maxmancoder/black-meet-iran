<?php
$title = 'ورود / ثبت‌نام - Black Meet';
require_once __DIR__ . '/partials/head.php';
require_once __DIR__ . '/auth.php';

if (current_user() !== null) {
    header('Location: ' . base_url() . '/home.php');
    exit;
}
$csrf = csrf_token();
?>
</head>
<body class="bg-background text-on-surface min-h-screen flex items-center justify-center overflow-hidden relative font-body-md">

<!-- Immersive background -->
<div class="absolute inset-0 bg-cover bg-center z-0 opacity-50" style="background-image:linear-gradient(135deg,#0b1020 0%,#1e1b4b 55%,#0b1020 100%);background-size:cover;"></div>
<div class="absolute inset-0 bg-gradient-to-b from-background/40 to-background z-0"></div>

<!-- main container -->
<div class="relative z-10 w-full max-w-[480px] p-grid-margin">
    <div class="text-center mb-xl">
        <h1 class="font-display-lg text-display-lg text-primary tracking-tight mb-2">Black Meet</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">پناهگاه دیجیتال شما</p>
    </div>

    <div class="glass-panel rounded-2xl shadow-2xl p-lg relative overflow-hidden" id="auth-container">

        <!-- LOGIN OPTIONS -->
        <div class="view space-y-md" id="view-login-options">
            <h2 class="font-headline-lg text-headline-lg text-center mb-md">ورود به سیستم</h2>
            <button class="btn-primary flex items-center justify-center gap-2" onclick="switchView('view-login-phone')">
                <span class="material-symbols-outlined">phone_iphone</span> ورود با شماره موبایل
            </button>
            <button class="btn-secondary flex items-center justify-center gap-2" onclick="switchView('view-login-email')">
                <span class="material-symbols-outlined">mail</span> ورود با ایمیل
            </button>
            <div class="text-center mt-md">
                <button class="text-primary hover:text-primary-fixed transition-colors font-body-sm text-body-sm" onclick="switchView('view-signup')">
                    حساب کاربری ندارید؟ ثبت نام کنید
                </button>
            </div>
        </div>

        <!-- LOGIN EMAIL -->
        <div class="view hidden space-y-md" id="view-login-email">
            <div class="flex items-center mb-md">
                <button class="text-on-surface-variant hover:text-on-surface transition-colors" onclick="switchView('view-login-options')">
                    <span class="material-symbols-outlined">arrow_forward</span>
                </button>
                <h2 class="font-headline-lg text-headline-lg flex-1 text-center pr-6">ورود با ایمیل</h2>
            </div>
            <input class="form-input" dir="ltr" id="le-email" placeholder="ایمیل" type="email" autocomplete="email"/>
            <input class="form-input" dir="ltr" id="le-pass" placeholder="رمز عبور" type="password" autocomplete="current-password"/>
            <button class="btn-primary" onclick="loginEmail()">ورود</button>
            <div class="text-center mt-3">
                <a href="<?= base_url() ?>/forgot_password.php" class="text-primary hover:text-primary-fixed font-body-sm text-body-sm">فراموشی رمز عبور؟</a>
            </div>
        </div>

        <!-- LOGIN PHONE -->
        <div class="view hidden space-y-md" id="view-login-phone">
            <div class="flex items-center mb-md">
                <button class="text-on-surface-variant hover:text-on-surface transition-colors" onclick="switchView('view-login-options')">
                    <span class="material-symbols-outlined">arrow_forward</span>
                </button>
                <h2 class="font-headline-lg text-headline-lg flex-1 text-center pr-6">ورود با موبایل</h2>
            </div>
            <input class="form-input" dir="ltr" id="lp-phone" placeholder="شماره موبایل" type="tel" inputmode="numeric"/>
            <button class="btn-primary" onclick="loginPhone()">ارسال کد تایید</button>
        </div>

        <!-- SIGNUP -->
        <div class="view hidden space-y-md" id="view-signup">
            <div class="flex items-center mb-md">
                <button class="text-on-surface-variant hover:text-on-surface transition-colors" onclick="switchView('view-login-options')">
                    <span class="material-symbols-outlined">arrow_forward</span>
                </button>
                <h2 class="font-headline-lg text-headline-lg flex-1 text-center pr-6">ثبت نام</h2>
            </div>
            <input class="form-input" id="su-name" placeholder="نام و نام خانوادگی" type="text"/>
            <input class="form-input" dir="ltr" id="su-user" placeholder="نام کاربری (یکتا)" type="text"/>
            <input class="form-input" dir="ltr" id="su-email" placeholder="ایمیل (یکتا)" type="email"/>
            <input class="form-input" dir="ltr" id="su-phone" placeholder="شماره موبایل (یکتا)" type="tel" inputmode="numeric"/>
            <input class="form-input" dir="ltr" id="su-pass" placeholder="رمز عبور" type="password"/>
            <button class="btn-primary" onclick="signup()">ادامه</button>
        </div>

        <!-- OTP -->
        <div class="view hidden space-y-md" id="view-otp">
            <div class="flex items-center mb-md">
                <button class="text-on-surface-variant hover:text-on-surface transition-colors" onclick="switchView('view-login-options')">
                    <span class="material-symbols-outlined">arrow_forward</span>
                </button>
                <h2 class="font-headline-lg text-headline-lg flex-1 text-center pr-6">تایید شماره</h2>
            </div>
            <p class="font-body-sm text-body-sm text-on-surface-variant text-center mb-2">کد ارسال شده به شماره خود را وارد کنید</p>

            <!-- DEV/DEMO code display -->
            <div id="otp-display" class="hidden mb-2 rounded-lg bg-surface-container-low border border-outline-variant/40 px-4 py-3 text-center">
                <span class="font-label-sm text-on-surface-variant text-[12px]">کد تأیید شما (نمایش آزمایشی):</span>
                <div id="otp-code" class="font-label-md text-primary text-2xl tracking-[0.4em] mt-1 select-all">------</div>
            </div>

            <input class="form-input text-center tracking-widest text-lg" dir="ltr" maxlength="6" id="otp-input" placeholder="کد تایید ۶ رقمی" type="text" inputmode="numeric"/>
            <button class="btn-primary" onclick="verifyOtp()">تایید و ورود</button>
            <div class="text-center mt-4">
                <p class="font-body-sm text-body-sm text-on-surface-variant" id="timer-text">ارسال مجدد کد تا <span id="countdown">۳۰</span> ثانیه دیگر</p>
            </div>
        </div>
    </div>
</div>

<!-- Toast (bottom-right) -->
<div class="fixed bottom-6 right-6 transform translate-y-20 opacity-0 transition-all duration-300 z-50" id="toast">
    <div class="bg-error-container text-on-error-container px-6 py-3 rounded-lg shadow-xl flex items-center gap-3">
        <span class="material-symbols-outlined">error</span>
        <span class="font-body-md text-body-md" id="toast-message">پیام خطا</span>
    </div>
</div>

<script>window.CSRF = <?= json_encode($csrf) ?>;</script>
<script src="<?= base_url() ?>/assets/js/auth.js"></script>
</body>
</html>
