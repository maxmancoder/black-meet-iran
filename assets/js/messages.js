// assets/js/messages.js
(function () {
  const BASE = window.BASE;
  const CSRF = window.CSRF;
  let convs = [];
  let view = null; // {type:'user', user_id, name, username, avatar, email} | {type:'public'}

  function esc(s) {
    return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }
  function aUrl(a) {
    if (!a) return '';
    if (/^https?:\/\//i.test(a)) return a;
    return BASE + '/' + a.replace(/^\/+/, '');
  }
  function showToast(msg) {
    const t = document.getElementById('toast');
    document.getElementById('toast-message').textContent = msg;
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 4000);
  }
  function avatarInner(avatar, color, name, sizeCls) {
    if (avatar) return '<img src="' + esc(aUrl(avatar)) + '" class="w-full h-full object-cover" alt=""/>';
    const p = (name || '').trim().split(/\s+/);
    const init = p.length >= 2 ? p[0][0] + p[1][0] : (name || '؟').slice(0, 2);
    return '<div class="w-full h-full flex items-center justify-center font-display-md text-white" style="background:' + (color || '#4f46e5') + '">' + esc(init) + '</div>';
  }
  function isOnline(la) {
    if (!la) return false;
    return (new Date().getTime() - new Date(la.replace(' ', 'T') + 'Z').getTime()) < 5 * 60 * 1000;
  }

  function loadConvs() {
    fetch('api/pv_manager.php')
      .then(r => r.json())
      .then(d => {
        if (!d.ok) return;
        convs = d.conversations || [];
        renderConvs();
      })
      .catch(() => {});
  }

  function renderConvs() {
    const q = (document.getElementById('conv-search').value || '').trim().toLowerCase();
    const box = document.getElementById('conv-list');
    box.innerHTML = '';
    // public item
    const pub = document.createElement('div');
    pub.className = 'flex items-center gap-3 p-2 rounded-lg cursor-pointer transition-colors ' + (view && view.type === 'public' ? 'bg-primary-container/40' : 'hover:bg-surface-container-high');
    pub.innerHTML = '<div class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center text-on-secondary-container"><span class="material-symbols-outlined">campaign</span></div>' +
      '<div class="flex-1 min-w-0"><h4 class="font-body-sm font-semibold text-on-surface truncate">پیام عمومی</h4><p class="font-label-sm text-on-surface-variant text-[10px]">اعلان‌ها (فقط مدیر)</p></div>';
    pub.onclick = openPublic;
    box.appendChild(pub);

    convs.filter(c => !q || (c.display_name + ' ' + c.username).toLowerCase().includes(q)).forEach(c => {
      const el = document.createElement('div');
      el.className = 'flex items-center gap-3 p-2 rounded-lg cursor-pointer transition-colors ' + (view && view.type === 'user' && view.user_id === c.user_id ? 'bg-primary-container/40' : 'hover:bg-surface-container-high');
      el.innerHTML = '<div class="relative w-10 h-10 rounded-full overflow-hidden border border-outline-variant">' + avatarInner(c.avatar, '#4f46e5', c.display_name, '') +
        (c.unread > 0 ? '<span class="absolute top-0 right-0 w-3 h-3 bg-primary rounded-full border-2 border-surface-container-low"></span>' : '') + '</div>' +
        '<div class="flex-1 min-w-0"><h4 class="font-body-sm font-semibold text-on-surface truncate">' + esc(c.display_name) + '</h4><p class="font-label-sm text-on-surface-variant text-[10px]">@' + esc(c.username) + '</p></div>';
      el.onclick = () => openUser(c);
      box.appendChild(el);
    });
  }

  window.filterConvs = renderConvs;

  function setHeader(c) {
    const h = document.getElementById('profile-header');
    h.classList.remove('hidden');
    h.classList.add('flex');
    document.getElementById('ph-avatar').innerHTML = avatarInner(c.avatar, '#4f46e5', c.display_name, '');
    document.getElementById('ph-name').textContent = c.display_name;
    document.getElementById('ph-status').textContent = c.type === 'public' ? 'اعلان عمومی' : (isOnline(c.last_activity) ? 'آنلاین' : 'آفلاین');
  }

  function renderChat(messages, type, color) {
    const box = document.getElementById('chat-area');
    box.innerHTML = '';
    (messages || []).forEach(m => {
      const fromMgr = (type === 'public') ? true : !!m.from_manager;
      const el = document.createElement('div');
      el.className = 'flex flex-col ' + (fromMgr ? 'items-start' : 'items-end');
      el.innerHTML = '<div class="' + (fromMgr ? 'bg-surface-container-high text-on-surface' : 'bg-primary-container text-on-primary-container') + ' p-3 rounded-2xl ' + (fromMgr ? 'rounded-tr-sm' : 'rounded-tl-sm') + ' text-body-sm max-w-[80%] border border-white/5">' + esc(m.body) + '</div>';
      box.appendChild(el);
    });
    box.scrollTop = box.scrollHeight;
    window._lastThreadId = (messages || []).reduce((mx, m) => Math.max(mx, Number(m.id) || 0), 0);
  }

  function openUser(c) {
    view = { type: 'user', user_id: c.user_id, name: c.display_name, username: c.username, avatar: c.avatar, email: c.email, last_activity: c.last_activity };
    setHeader(view);
    fetch('api/pv_manager.php?user_id=' + c.user_id)
      .then(r => r.json())
      .then(d => { if (d.ok) renderChat(d.messages, 'user'); loadConvs(); })
      .catch(() => {});
  }

  function openPublic() {
    view = { type: 'public' };
    setHeader(view);
    fetch('api/announcements.php')
      .then(r => r.json())
      .then(d => { if (d.ok) renderChat((d.announcements || []).map(a => ({ id: a.id, body: a.body, from_manager: 1 })), 'public'); })
      .catch(() => {});
  }

  // Live update: re-fetch the active thread and render only if new messages arrived
  function maybeRender(msgs, type) {
    const maxId = (msgs || []).reduce((mx, m) => Math.max(mx, Number(m.id) || 0), 0);
    if (maxId <= (window._lastThreadId || 0)) return;
    renderChat(msgs, type);
  }
  function pollThread() {
    if (!view) return;
    if (view.type === 'public') {
      fetch('api/announcements.php')
        .then(r => r.json())
        .then(d => { if (d.ok) maybeRender((d.announcements || []).map(a => ({ id: a.id, body: a.body, from_manager: 1 })), 'public'); })
        .catch(() => {});
    } else {
      fetch('api/pv_manager.php?user_id=' + view.user_id)
        .then(r => r.json())
        .then(d => { if (d.ok) { maybeRender(d.messages || [], 'user'); loadConvs(); } })
        .catch(() => {});
    }
  }
  setInterval(pollThread, 3000);

  window.sendMessage = function () {
    if (!view) return showToast('ابتدا یک گفتگو انتخاب کنید');
    const inp = document.getElementById('msg-input');
    const body = inp.value.trim();
    if (!body) return;
    if (view.type === 'public') {
      fetch('api/announcements.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ csrf: CSRF, body }) })
        .then(r => r.json()).then(d => {
          if (!d.ok) return showToast(d.msg || 'خطا');
          inp.value = ''; openPublic(); showToast('اعلان ارسال شد');
        }).catch(() => showToast('خطا'));
    } else {
      fetch('api/pv_manager.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ csrf: CSRF, user_id: String(view.user_id), body }) })
        .then(r => r.json()).then(d => {
          if (!d.ok) return showToast(d.msg || 'خطا');
          inp.value = '';
          fetch('api/pv_manager.php?user_id=' + view.user_id).then(r => r.json()).then(d2 => { if (d2.ok) renderChat(d2.messages, 'user'); });
        }).catch(() => showToast('خطا'));
    }
  };

  window.openProfileModal = function () {
    if (!view || view.type !== 'user') return;
    document.getElementById('pm-avatar').innerHTML = avatarInner(view.avatar, '#4f46e5', view.name, '');
    document.getElementById('pm-name').textContent = view.name;
    document.getElementById('pm-username').textContent = '@' + view.username;
    document.getElementById('pm-email').textContent = view.email || '';
    document.getElementById('profile-modal').classList.remove('hidden');
  };
  window.closeProfileModal = function () { document.getElementById('profile-modal').classList.add('hidden'); };

  loadConvs();
  setInterval(loadConvs, 15000);
})();
