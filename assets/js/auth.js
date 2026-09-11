// assets/js/auth.js
(function () {
  const csrf = window.CSRF;
  let currentPhone = '';
  let currentPurpose = '';
  let lastOtp = '';
  let timerInterval = null;

  const VIEWS = ['view-login-options', 'view-login-email', 'view-login-phone', 'view-signup', 'view-otp'];

  window.switchView = function (id) {
    VIEWS.forEach(v => {
      const el = document.getElementById(v);
      el.classList.add('hidden');
      el.classList.remove('view');
    });
    const t = document.getElementById(id);
    t.classList.remove('hidden');
    // retrigger animation
    void t.offsetWidth;
    t.classList.add('view');
    if (id === 'view-otp') {
      const disp = document.getElementById('otp-display');
      if (lastOtp) {
        document.getElementById('otp-code').textContent = lastOtp;
        disp.classList.remove('hidden');
      }
      startTimer();
    }
  };

  function startTimer() {
    clearInterval(timerInterval);
    let t = 30;
    const span = document.getElementById('countdown');
    const txt = document.getElementById('timer-text');
    const fa = n => n.toString().replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
    span.textContent = fa(t);
    timerInterval = setInterval(() => {
      t--;
      if (t <= 0) {
        clearInterval(timerInterval);
        txt.innerHTML = '<button class="text-primary hover:text-primary-fixed" onclick="resendOtp()">ارسال مجدد کد تأیید</button>';
        return;
      }
      span.textContent = fa(t);
    }, 1000);
  }

  window.resendOtp = function () {
    postJSON('api/resend_otp.php', { csrf, phone: currentPhone, purpose: currentPurpose })
      .then(d => {
        if (d.ok) {
          lastOtp = d.otp;
          document.getElementById('otp-code').textContent = d.otp;
          document.getElementById('otp-display').classList.remove('hidden');
          startTimer();
          showToast('کد جدید ارسال شد', 3000);
        } else showToast(d.msg || 'خطا');
      });
  };

  function postJSON(url, data) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams(data)
    }).then(r => r.json());
  }

  function showToast(msg, dur = 10000) {
    const toast = document.getElementById('toast');
    document.getElementById('toast-message').textContent = msg;
    toast.classList.remove('translate-y-20', 'opacity-0');
    toast.classList.add('show');
    setTimeout(() => {
      toast.classList.add('translate-y-20', 'opacity-0');
      toast.classList.remove('show');
    }, dur);
  }
  window.showToast = showToast;

  function persistSession() {
    localStorage.setItem('blackmeet_logged_in', '1');
  }

  window.loginPhone = function () {
    const phone = document.getElementById('lp-phone').value.trim();
    if (!phone) return showToast('شماره تلفن را وارد کنید');
    postJSON('api/login_phone.php', { csrf, phone })
      .then(d => {
        if (d.ok) {
          currentPhone = d.phone; currentPurpose = d.purpose; lastOtp = d.otp;
          switchView('view-otp');
        } else showToast(d.msg || 'خطا');
      });
  };

  window.signup = function () {
    const data = {
      csrf,
      full_name: document.getElementById('su-name').value.trim(),
      username: document.getElementById('su-user').value.trim(),
      email: document.getElementById('su-email').value.trim(),
      phone: document.getElementById('su-phone').value.trim(),
      password: document.getElementById('su-pass').value
    };
    if (!data.full_name || !data.username || !data.email || !data.phone || !data.password)
      return showToast('تمام فیلدها را پر کنید');
    postJSON('api/signup.php', data)
      .then(d => {
        if (d.ok) {
          currentPhone = d.phone; currentPurpose = d.purpose; lastOtp = d.otp;
          switchView('view-otp');
        } else showToast(d.msg || 'خطا');
      });
  };

  window.verifyOtp = function () {
    const code = document.getElementById('otp-input').value.trim();
    if (!code) return showToast('کد تأیید را وارد کنید');
    postJSON('api/verify_otp.php', { csrf, phone: currentPhone, code, purpose: currentPurpose })
      .then(d => {
        if (d.ok) { persistSession(); location.href = d.redirect; }
        else showToast(d.msg || 'خطا');
      });
  };

  window.loginEmail = function () {
    const email = document.getElementById('le-email').value.trim();
    const password = document.getElementById('le-pass').value;
    if (!email || !password) return showToast('ایمیل و رمز عبور را وارد کنید');
    postJSON('api/login_email.php', { csrf, email, password })
      .then(d => {
        if (d.ok) { persistSession(); location.href = d.redirect; }
        else showToast(d.msg || 'خطا');
      });
  };
})();
