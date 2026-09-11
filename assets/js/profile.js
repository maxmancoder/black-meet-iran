// assets/js/profile.js
(function () {
  const BASE = window.BASE;
  const csrf = window.CSRF;

  function showToast(msg) {
    const t = document.getElementById('toast');
    document.getElementById('toast-message').textContent = msg;
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 4000);
  }

  window.saveProfile = function () {
    const name = document.getElementById('display-name').value.trim();
    if (!name) return showToast('نام نمایشی الزامی است');
    fetch('api/profile_update.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ csrf, display_name: name })
    })
      .then(r => r.json())
      .then(d => { if (d.ok) showToast('ذخیره شد'); else showToast(d.msg || 'خطا'); });
  };

  window.logout = function (e) {
    if (e) e.preventDefault();
    fetch('api/logout.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'csrf=' + encodeURIComponent(csrf) })
      .then(r => r.json())
      .then(d => { localStorage.removeItem('blackmeet_logged_in'); location.href = d.redirect || BASE + '/index.php'; });
  };

  window.uploadAvatar = function () {
    const inp = document.getElementById('avatar-input');
    if (!inp.files || !inp.files[0]) return;
    const fd = new FormData();
    fd.append('csrf', csrf);
    fd.append('avatar', inp.files[0]);
    fetch('api/upload_avatar.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => { if (d.ok) location.reload(); else showToast(d.msg || 'آپلود ناموفق'); });
  };
})();
