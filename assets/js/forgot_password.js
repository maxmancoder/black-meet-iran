// assets/js/forgot_password.js
(function () {
  const BASE = window.BASE;
  let currentId = '';

  function showToast(msg) {
    const t = document.getElementById('toast');
    document.getElementById('toast-message').textContent = msg;
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 4000);
  }
  function esc(s) {
    return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  window.openThread = function () {
    const id = document.getElementById('identifier').value.trim();
    if (!id) return showToast('شماره تلفن یا ایمیل را وارد کنید');
    currentId = id;
    fetch('api/pv_user.php?identifier=' + encodeURIComponent(id))
      .then(r => r.json())
      .then(d => {
        if (!d.ok) return showToast(d.msg || 'خطا');
        document.getElementById('identify-box').classList.add('hidden');
        document.getElementById('thread-box').classList.remove('hidden');
        render(d.messages || []);
      })
      .catch(() => showToast('خطای ارتباط'));
  };
  window.closeThread = function () {
    document.getElementById('thread-box').classList.add('hidden');
    document.getElementById('identify-box').classList.remove('hidden');
  };

  function render(messages) {
    const box = document.getElementById('pv-messages');
    box.innerHTML = '';
    messages.forEach(m => {
      const el = document.createElement('div');
      el.className = 'flex flex-col ' + (m.from_manager ? 'items-start' : 'items-end');
      el.innerHTML = '<div class="' + (m.from_manager ? 'bg-surface-container-high text-on-surface' : 'bg-primary-container text-on-primary-container') + ' p-3 rounded-2xl ' + (m.from_manager ? 'rounded-tr-sm' : 'rounded-tl-sm') + ' text-body-sm max-w-[85%] border border-white/5">' + esc(m.body) + '</div>';
      box.appendChild(el);
    });
    box.scrollTop = box.scrollHeight;
    window._lastPvId = (messages || []).reduce((mx, m) => Math.max(mx, Number(m.id) || 0), 0);
  }

  // Live update: show manager replies without refresh
  function pollUserThread() {
    if (!currentId || document.getElementById('thread-box').classList.contains('hidden')) return;
    fetch('api/pv_user.php?identifier=' + encodeURIComponent(currentId))
      .then(r => r.json())
      .then(d => {
        if (!d.ok) return;
        const msgs = d.messages || [];
        const maxId = msgs.reduce((mx, m) => Math.max(mx, Number(m.id) || 0), 0);
        if (maxId > (window._lastPvId || 0)) render(msgs);
      })
      .catch(() => {});
  }
  setInterval(pollUserThread, 4000);

  window.sendPv = function () {
    const inp = document.getElementById('pv-input');
    const body = inp.value.trim();
    if (!body || !currentId) return;
    fetch('api/pv_user.php', {
      method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ csrf: window.CSRF, identifier: currentId, body })
    })
      .then(r => r.json())
      .then(d => {
        if (!d.ok) return showToast(d.msg || 'خطا');
        inp.value = '';
        return fetch('api/pv_user.php?identifier=' + encodeURIComponent(currentId)).then(r => r.json());
      })
      .then(d => { if (d && d.ok) render(d.messages || []); })
      .catch(() => showToast('خطای ارتباط'));
  };

  window.closeHelp = function () { document.getElementById('help-modal').classList.add('hidden'); };
  document.getElementById('help-btn').addEventListener('click', () => document.getElementById('help-modal').classList.remove('hidden'));
})();

