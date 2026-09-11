// assets/js/create.js
(function () {
  const BASE = window.BASE;
  const csrf = window.CSRF;

  function showToast(msg) {
    const t = document.getElementById('toast');
    document.getElementById('toast-message').textContent = msg;
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 4000);
  }

  window.createMeeting = function () {
    const title = document.getElementById('meeting-title').value.trim();
    const btn = document.getElementById('create-btn');
    btn.disabled = true;
    fetch('api/create_meeting.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ csrf, title })
    })
      .then(r => r.json())
      .then(d => {
        if (d.ok) location.href = d.redirect;
        else { showToast(d.msg || 'خطا'); btn.disabled = false; }
      })
      .catch(() => { showToast('خطا در ارتباط'); btn.disabled = false; });
  };
})();
