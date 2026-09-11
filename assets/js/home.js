// assets/js/home.js
(function () {
  const BASE = window.BASE;
  localStorage.setItem('blackmeet_logged_in', '1');

  function showToast(msg) {
    const t = document.getElementById('toast');
    document.getElementById('toast-message').textContent = msg;
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 4000);
  }

  function extractRoom(val) {
    val = val.trim();
    if (!val) return '';
    // full URL with ?room=xxx
    const m = val.match(/[?&]room=([^&\s]+)/);
    if (m) return m[1];
    // trailing path segment
    const u = val.match(/call\.php\/([^/\s]+)/);
    if (u) return u[1];
    return val;
  }

  const form = document.getElementById('join-form');
  if (form) form.addEventListener('submit', function (e) {
    e.preventDefault();
    const raw = document.getElementById('join-input').value;
    const room = extractRoom(raw);
    if (!room) return showToast('لینک یا کد تماس را وارد کنید');
    fetch('api/meeting_info.php?room=' + encodeURIComponent(room))
      .then(r => r.json())
      .then(d => {
        if (d.ok) location.href = BASE + '/call.php?room=' + encodeURIComponent(d.room_id);
        else showToast('لینک تماس معتبر نیست');
      })
      .catch(() => showToast('خطا در برقراری ارتباط'));
  });

  window.logout = function (e) {
    if (e) e.preventDefault();
    fetch('api/logout.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'csrf=' + encodeURIComponent(window.CSRF) })
      .then(r => r.json())
      .then(d => {
        localStorage.removeItem('blackmeet_logged_in');
        location.href = d.redirect || BASE + '/index.php';
      });
  };

  // Blocked redirect message
  const params = new URLSearchParams(location.search);
  if (params.get('err') === 'blocked') showToast('شما از طرف ادمین مسدود شدید و اجازه ورود به این تماس را ندارید');

  // ---- Manager notifications (announcements) ----
  let notifSort = 'new';
  window.toggleNotifications = function () {
    const p = document.getElementById('notif-popup');
    if (!p) return;
    p.classList.toggle('hidden');
    if (!p.classList.contains('hidden')) loadNotifications();
  };
  window.setNotifSort = function (s) {
    notifSort = s;
    document.getElementById('sort-old').className = 'text-[12px] px-2 py-1 rounded-md ' + (s === 'old' ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container-high text-on-surface');
    document.getElementById('sort-new').className = 'text-[12px] px-2 py-1 rounded-md ' + (s === 'new' ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container-high text-on-surface');
    renderNotifications(window._notifs || []);
  };
  function loadNotifications() {
    fetch('api/announcements.php')
      .then(r => r.json())
      .then(d => { window._notifs = (d.announcements || []); renderNotifications(window._notifs); const dot = document.getElementById('bell-dot'); if (dot) dot.classList.toggle('hidden', (window._notifs.length === 0)); })
      .catch(() => {});
  }
  function renderNotifications(list) {
    const box = document.getElementById('notif-list');
    if (!box) return;
    const arr = list.slice();
    if (notifSort === 'new') arr.reverse();
    if (!arr.length) { box.innerHTML = '<p class="text-on-surface-variant font-body-sm text-center py-4">اعلانى وجود ندارد</p>'; return; }
    box.innerHTML = '';
    arr.forEach(a => {
      const el = document.createElement('div');
      el.className = 'bg-surface-container rounded-lg p-3 border border-outline-variant/20';
      el.innerHTML = '<p class="font-body-sm text-on-surface">' + escapeHtml(a.body) + '</p><p class="font-label-sm text-on-surface-variant text-[11px] mt-1">' + (a.created_at || '') + '</p>';
      box.appendChild(el);
    });
  }
  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }
  loadNotifications();
})();
