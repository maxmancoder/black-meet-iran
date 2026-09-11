// assets/js/admin_members.js
(function () {
  const $ = id => document.getElementById(id);
  let allUsers = window.INIT_USERS || [];

  function showToast(msg) {
    const t = $('toast'); $('toast-message').textContent = msg;
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 4000);
  }

  function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  function statusCell(u) {
    if (u.is_limited) {
      return '<span class="inline-flex items-center gap-1 text-error-container bg-error-container/20 px-2 py-1 rounded-full font-label-sm">' +
        '<span class="material-symbols-outlined text-[16px]">block</span> محدود</span>';
    }
    return '<span class="inline-flex items-center gap-1 text-secondary-container bg-secondary-container/20 px-2 py-1 rounded-full font-label-sm">' +
      '<span class="material-symbols-outlined text-[16px]">check_circle</span> عادی</span>';
  }

  function actionCell(u) {
    if (u.is_manager) return '<span class="font-body-sm text-on-surface-variant">—</span>';
    const limited = !!u.is_limited;
    const cls = limited ? 'bg-secondary-container text-on-secondary-container' : 'bg-error-container/80 text-on-error-container';
    const label = limited ? 'رفع محدودیت' : 'محدود کردن';
    return '<div class="flex flex-col gap-1.5 items-start">' +
      '<button id="btn-lim-' + u.id + '" onclick="toggleLimit(' + u.id + ')" class="text-[12px] px-3 py-1.5 rounded-md ' + cls + ' hover:opacity-80 transition-opacity">' + label + '</button>' +
      '<button onclick="resetPassword(' + u.id + ')" class="text-[12px] px-3 py-1.5 rounded-md bg-surface-container-high text-on-surface hover:opacity-80 transition-opacity">تغییر رمز</button>' +
      '</div>';
  }

  function avatarHtml(u, size) {
    const sz = size || 'w-9 h-9';
    const initial = esc((u.display_name || '?').slice(0, 2));
    if (u.avatar) {
      const src = (u.avatar.indexOf('http') === 0) ? u.avatar : (window.BASE + '/' + u.avatar.replace(/^\/+/, ''));
      return '<div class="' + sz + ' rounded-full overflow-hidden border border-outline-variant flex items-center justify-center text-white text-[12px]" style="background:' + esc(u.avatar_color) + '"><img src="' + esc(src) + '" class="w-full h-full object-cover" alt=""/></div>';
    }
    return '<div class="' + sz + ' rounded-full flex items-center justify-center text-white text-[12px]" style="background:' + esc(u.avatar_color) + '">' + initial + '</div>';
  }

  function rowEl(u) {
    const tr = document.createElement('tr');
    tr.id = 'user-row-' + u.id;
    tr.className = 'border-t border-outline-variant/10 hover:bg-surface-container-low/60';
    tr.innerHTML = `
      <td class="p-3 whitespace-nowrap">
        <div class="flex items-center gap-2">
          ${avatarHtml(u)}
          <span class="font-body-sm text-on-surface">${esc(u.full_name)}</span>
        </div>
      </td>
      <td class="p-3 font-label-sm text-on-surface-variant" dir="ltr">${esc(u.username)}</td>
      <td class="p-3 font-body-sm text-on-surface">${esc(u.display_name)}</td>
      <td class="p-3 font-body-sm text-on-surface-variant" dir="ltr">${esc(u.email)}</td>
      <td class="p-3 font-body-sm text-on-surface-variant" dir="ltr">${u.is_manager ? '•••••• (هش‌شده)' : esc(u.password_hash)}</td>
      <td class="p-3 font-body-sm text-on-surface-variant" dir="ltr">${esc(u.phone)}</td>
      <td class="p-3 font-body-sm text-on-surface">${u.is_manager ? 'مدیر' : 'عضو'}</td>
      <td class="p-3" id="lim-${u.id}">${statusCell(u)}</td>
      <td class="p-3" id="act-${u.id}">${actionCell(u)}</td>`;
    return tr;
  }

  function render() {
    const q = ($('member-search').value || '').trim().toLowerCase();
    const size = parseInt($('page-size').value, 10) || 25;
    let list = allUsers;
    if (q) {
      list = allUsers.filter(u =>
        (u.display_name || '').toLowerCase().includes(q) ||
        (u.username || '').toLowerCase().includes(q) ||
        (u.email || '').toLowerCase().includes(q) ||
        (u.phone || '').includes(q)
      );
    }
    const shown = list.slice(0, size);
    const tbody = $('user-tbody');
    tbody.innerHTML = '';
    if (!shown.length) {
      tbody.innerHTML = '<tr><td colspan="9" class="p-6 text-center text-on-surface-variant font-body-sm">موردی یافت نشد</td></tr>';
    } else {
      const frag = document.createDocumentFragment();
      shown.forEach(u => frag.appendChild(rowEl(u)));
      tbody.appendChild(frag);
    }
    $('result-count').textContent = list.length + ' کاربر' + (list.length > shown.length ? ' (نمایش ' + shown.length + ' مورد)' : '');
  }

  $('member-search').addEventListener('input', render);
  $('page-size').addEventListener('change', render);

  window.toggleLimit = function (userId) {
    const btn = $('btn-lim-' + userId);
    if (btn) { btn.disabled = true; btn.textContent = '...'; }
    fetch('api/admin_members.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ csrf: window.CSRF, user_id: String(userId) })
    })
      .then(r => r.json())
      .then(d => {
        if (!d.ok) { showToast(d.msg || 'خطا'); if (btn) { btn.disabled = false; btn.textContent = 'تلاش مجدد'; } return; }
        const u = allUsers.find(x => x.id === userId);
        if (u) u.is_limited = d.is_limited ? 1 : 0;
        render();
        showToast(d.is_limited ? 'کاربر محدود شد' : 'محدودیت برداشته شد');
      })
      .catch(() => { showToast('خطای شبکه'); if (btn) { btn.disabled = false; btn.textContent = 'تلاش مجدد'; } });
  };

  window.resetPassword = function (userId) {
    const np = prompt('رمز عبور جدید را وارد کنید (خالی = تولید خودکار):');
    if (np === null) return;
    fetch('api/admin_members.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ csrf: window.CSRF, user_id: String(userId), action: 'reset_password', new_password: np.trim() })
    })
      .then(r => r.json())
      .then(d => {
        if (!d.ok) { showToast(d.msg || 'خطا'); return; }
        showToast(d.generated ? ('رمز جدید: ' + d.generated) : 'رمز عبور به‌روزرسانی شد');
      })
      .catch(() => showToast('خطای شبکه'));
  };

  render();
})();
