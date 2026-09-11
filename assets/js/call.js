// assets/js/call.js
(function () {
  const I = window.INIT;
  const ICE = (I && I.iceServers && I.iceServers.length) ? I.iceServers : [
    { urls: 'stun:stun.l.google.com:19302' },
    { urls: 'stun:stun1.l.google.com:19302' }
  ];
  const EMOJIS = ['👍', '❤️', '👏', '😂', '🎉', '😮', '🔥', '✅', '🙏', '💡', '😎', '🌟'];

  let socket = null;
  let localStream = null;
  let cameraTrack = null;
  let screenTrack = null;
  let currentVideoTrack = null;
  let micOn = false, camOn = false, sharing = false, approved = (I.me.status === 'approved');
  const pcMap = new Map();        // userId -> RTCPeerConnection
  const peerMeta = new Map();     // userId -> {name, avatar_color}
  const memberStatus = new Map(); // userId -> {muted,cam,sharing}
  const memberNames = new Map();  // userId -> display name
  const localBlock = {};           // userId -> {audio,video,screen}  (admin local-only blocks)
  const localAudio = {};           // userId -> {muted, volume}       (per-user local audio)
  const remoteStreams = new Map();
  let menuUserId = null;
  let currentList = [];

  const $ = id => document.getElementById(id);
  const grid = $('video-grid');

  function showToast(msg) {
    const t = $('toast'); $('toast-message').textContent = msg;
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 4000);
  }
  window.showToast = showToast;

  // Short notification beep via WebAudio
  function playBeep() {
    try {
      const Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx) return;
      const ctx = new Ctx();
      const o = ctx.createOscillator(), g = ctx.createGain();
      o.type = 'sine'; o.frequency.value = 880;
      g.gain.value = 0.15;
      o.connect(g); g.connect(ctx.destination);
      o.start();
      setTimeout(() => { o.stop(); ctx.close(); }, 250);
    } catch (e) {}
  }

  window.alertAdmin = function () {
    socket && socket.emit('alert-admin');
    playBeep();
    showToast('درخواست ورود به ادمین ارسال شد');
  };

  window.adminBlock = function (userId) {
    socket && socket.emit('admin-block', { userId });
    fetch('api/admin_block.php', {
      method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ csrf: I.csrf, meeting_id: I.meeting.id, user_id: String(userId) })
    }).catch(() => {});
    showToast('کاربر از این تماس مسدود شد');
  };

  // ---------- Media ----------
  function startMedia() {
    if (!navigator.mediaDevices) { renderSelf(); return Promise.resolve(); }
    return navigator.mediaDevices.getUserMedia({ audio: true, video: true })
      .then(stream => {
        localStream = stream;
        cameraTrack = stream.getVideoTracks()[0] || null;
        micOn = true; camOn = true;
        currentVideoTrack = cameraTrack;
        updateSelfVideo();
        updateControlUI();
        // add tracks to existing pcs
        pcMap.forEach(pc => addLocalTracks(pc));
      })
      .catch(() => { renderSelf(); updateControlUI(); });
  }

  function addLocalTracks(pc) {
    if (!localStream) return;
    localStream.getTracks().forEach(t => {
      // avoid duplicates
      const exists = pc.getSenders().some(s => s.track && s.track.kind === t.kind);
      if (!exists) pc.addTrack(t, localStream);
    });
  }

  function updateSelfVideo() {
    const v = $('self-video');
    const av = $('self-avatar');
    if (currentVideoTrack) {
      const s = new MediaStream([currentVideoTrack]);
      v.srcObject = s; v.style.display = ''; if (av) av.style.display = 'none';
    } else {
      v.srcObject = null; v.style.display = 'none'; if (av) av.style.display = '';
    }
  }

  function updateControlUI() {
    $('ic-mic').textContent = micOn ? 'mic' : 'mic_off';
    $('btn-mic').classList.toggle('ctrl-off', !micOn);
    $('ic-cam').textContent = camOn ? 'videocam' : 'videocam_off';
    $('btn-cam').classList.toggle('ctrl-off', !camOn);
    $('btn-share').classList.toggle('ctrl-off', !sharing);
  }

  // ---------- Tiles ----------
  function initials(name) {
    const p = (name || '').trim().split(/\s+/);
    if (p.length >= 2) return p[0][0] + p[1][0];
    return (name || '?').slice(0, 2);
  }

  function avatarUrl(a) {
    if (!a) return '';
    if (/^https?:\/\//i.test(a)) return a;
    return I.base + '/' + a.replace(/^\/+/, '');
  }

  function avatarMarkup(meta, sizeCls) {
    meta = meta || {};
    const init = initials(meta.name || '؟');
    if (meta.avatar) {
      return '<img src="' + escapeHtml(avatarUrl(meta.avatar)) + '" class="w-full h-full object-cover" alt=""/>';
    }
    return '<div class="w-full h-full flex items-center justify-center font-display-md text-white" style="background:' + (meta.avatar_color || '#4f46e5') + '">' + escapeHtml(init) + '</div>';
  }

  function renderSelf() {
    if ($('tile-self')) return;
    const tile = document.createElement('div');
    tile.id = 'tile-self';
    tile.className = 'aspect-video bg-surface-container relative rounded-xl overflow-hidden shadow-lg border-2 border-secondary';
    tile.innerHTML = `
      <video id="self-video" autoplay muted playsinline class="w-full h-full object-cover"></video>
      <div id="self-avatar" class="hidden absolute inset-0 flex items-center justify-center">
        <div class="w-24 h-24 rounded-full flex items-center justify-center font-display-md text-white" style="background:${I.me.avatar_color}">${initials(I.me.name)}</div>
      </div>
      <div class="absolute bottom-3 right-3 bg-background/80 backdrop-blur-md px-3 py-1.5 rounded-lg border border-white/10 flex items-center gap-2">
        <span class="font-label-md text-on-surface">${escapeHtml(I.me.name)} (شما)</span>
      </div>`;
    grid.appendChild(tile);
    updateSelfVideo();
  }

  function getOrCreateTile(userId, meta) {
    let tile = $('tile-' + userId);
    if (tile) return tile;
    tile = document.createElement('div');
    tile.id = 'tile-' + userId;
    tile.className = 'aspect-video bg-surface-container relative rounded-xl border border-white/10 overflow-hidden shadow-lg';
    tile.innerHTML = `
      <video id="vid-${userId}" autoplay playsinline class="w-full h-full object-cover"></video>
      <div id="av-${userId}" class="hidden absolute inset-0 flex items-center justify-center">
        ${avatarMarkup(meta, 'w-24 h-24')}
      </div>
      <div class="absolute top-3 right-3 bg-surface-container-highest/80 backdrop-blur-md px-2 py-1 rounded-md border border-white/10">
        <span id="mic-${userId}" class="material-symbols-outlined text-[14px] text-secondary">mic</span>
      </div>
      <div class="absolute bottom-3 right-3 bg-background/80 backdrop-blur-md px-3 py-1.5 rounded-lg border border-white/10 flex items-center gap-2">
        <span class="font-label-md text-on-surface" id="name-${userId}">${escapeHtml(meta.name || '')}</span>
      </div>`;
    tile.oncontextmenu = (e) => openMemberMenu(e, userId, meta.name);
    grid.appendChild(tile);
    applyMediaOverrides(userId);
    updateTileName(userId);
    layoutGrid();
    return tile;
  }

  function removeTile(userId) {
    const t = $('tile-' + userId); if (t) t.remove();
    remoteStreams.delete(userId);
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  // ---------- WebRTC ----------
  function createPC(peer, initiator) {
    if (pcMap.has(peer.userId)) return pcMap.get(peer.userId);
    peerMeta.set(peer.userId, { name: peer.name, avatar_color: peer.avatar_color, avatar: peer.avatar || '' });
    const pc = new RTCPeerConnection({ iceServers: ICE });
    pcMap.set(peer.userId, pc);
    getOrCreateTile(peer.userId, peer);
    addLocalTracks(pc);

    pc.onicecandidate = e => {
      if (e.candidate) socket.emit('signal', { to: peer.userId, data: { type: 'ice', candidate: e.candidate } });
    };
    pc.ontrack = e => {
      let rs = remoteStreams.get(peer.userId);
      if (!rs) { rs = new MediaStream(); remoteStreams.set(peer.userId, rs); }
      rs.addTrack(e.track);
      const v = $('vid-' + peer.userId);
      if (v) v.srcObject = rs;
      applyMediaOverrides(peer.userId);
    };
    pc.onconnectionstatechange = () => {
      if (['failed', 'closed', 'disconnected'].includes(pc.connectionState) && pc.connectionState !== 'connected') {
        // leave cleanup to peer-left
      }
    };

    if (initiator) {
      pc.createOffer().then(offer => {
        return pc.setLocalDescription(offer).then(() =>
          socket.emit('signal', { to: peer.userId, data: pc.localDescription }));
      }).catch(() => {});
    }
    return pc;
  }

  function replaceOutgoingVideo(track) {
    currentVideoTrack = track;
    pcMap.forEach(pc => {
      const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
      if (sender) sender.replaceTrack(track).catch(() => {});
    });
    updateSelfVideo();
  }

  // ---------- Socket ----------
  let joined = false, mediaReady = false, pendingJoin = false;
  function broadcastStatus() {
    if (socket) socket.emit('member-status', { muted: !micOn, cam: camOn, sharing });
  }
  function doJoin() {
    if (joined || !socket || !socket.connected || !mediaReady) { pendingJoin = true; return; }
    joined = true;
    socket.emit('join', {
      room: I.meeting.room, token: I.token,
      user: { id: I.me.id, name: I.me.name, username: I.me.username, avatar_color: I.me.avatar_color, avatar: I.me.avatar || '', is_admin: I.me.is_admin, status: I.me.status }
    });
  }
  function connect() {
    socket = io(I.socketUrl || undefined, {
      transports: ['polling', 'websocket'],
      reconnection: true,
      reconnectionAttempts: Infinity,
      reconnectionDelay: 1000,
      reconnectionDelayMax: 5000,
      timeout: 20000
    });
    socket.on('connect', () => { if (mediaReady) doJoin(); });
    socket.on('error-msg', d => showToast(d.msg || 'خطا'));
    socket.on('approved', () => { approved = true; $('waiting-overlay').classList.add('hidden'); broadcastStatus(); });
    socket.on('you-approved', () => { approved = true; $('waiting-overlay').classList.add('hidden'); broadcastStatus(); });
    socket.on('room-peers', peers => {
      peers.forEach(p => { if (p.userId !== I.me.id) createPC(p, I.me.id > p.userId); });
    });
    socket.on('peer-joined', p => {
      if (p.userId === I.me.id) return;
      createPC(p, I.me.id > p.userId);
    });
    socket.on('signal', async ({ from, data }) => {
      if (!pcMap.has(from)) {
        const meta = peerMeta.get(from) || { name: memberNames.get(from) || '', avatar_color: '#4f46e5' };
        createPC({ userId: from, ...meta }, false);
        updateTileName(from);
      }
      const pc = pcMap.get(from);
      try {
        if (data.type === 'offer') {
          await pc.setRemoteDescription(new RTCSessionDescription(data));
          const ans = await pc.createAnswer();
          await pc.setLocalDescription(ans);
          socket.emit('signal', { to: from, data: pc.localDescription });
        } else if (data.type === 'answer') {
          await pc.setRemoteDescription(new RTCSessionDescription(data));
        } else if (data.type === 'ice') {
          try { await pc.addIceCandidate(new RTCIceCandidate(data.candidate)); } catch (e) {}
        }
      } catch (e) {}
    });
    socket.on('peer-left', ({ userId }) => {
      const pc = pcMap.get(userId);
      if (pc) { pc.close(); pcMap.delete(userId); }
      removeTile(userId);
      layoutGrid();
      peerMeta.delete(userId);
    });
    socket.on('member-list', list => renderMembers(list));
    socket.on('chat', d => appendMessage(d.userId, memberNames.get(d.userId) || d.name || 'بدون نام', d.body, false));
    socket.on('emoji', d => spawnEmoji(d.emoji));
    socket.on('member-status', d => {
      const cur = memberStatus.get(d.userId) || {};
      if (d.muted !== undefined) cur.muted = d.muted;
      if (d.cam !== undefined) cur.cam = d.cam;
      if (d.sharing !== undefined) cur.sharing = d.sharing;
      memberStatus.set(d.userId, cur);
      updateTileStatus(d.userId);
    });
    socket.on('force-disable', d => {
      if (d.kind === 'audio') {
        if (localStream) localStream.getAudioTracks().forEach(t => t.enabled = !d.off);
        micOn = !d.off; updateControlUI(); broadcastStatus();
      } else if (d.kind === 'video') {
        if (localStream) localStream.getVideoTracks().forEach(t => t.enabled = !d.off);
        camOn = !d.off;
        if (camOn && !sharing) currentVideoTrack = cameraTrack;
        else if (!camOn && !sharing) currentVideoTrack = null;
        updateSelfVideo(); updateControlUI(); broadcastStatus();
      } else if (d.kind === 'screen') {
        if (d.off) stopShare();
      }
    });
    socket.on('you-rejected', () => { showToast('درخواست شما رد شد'); setTimeout(() => location.href = I.base + '/home.php', 1500); });
    socket.on('you-removed', () => { showToast('از تماس حذف شدید'); setTimeout(() => location.href = I.base + '/home.php', 1500); });
    socket.on('you-blocked', () => { showToast('شما از طرف ادمین مسدود شدید و اجازه ورود به این تماس را ندارید'); setTimeout(() => location.href = I.base + '/home.php', 2500); });
    socket.on('join-alert', d => {
      playBeep();
      showToast('درخواست ورود از سمت ' + (d.name || 'یک کاربر'));
      try { if ('Notification' in window && Notification.permission === 'granted') new Notification('درخواست ورود', { body: d.name || '' }); } catch (e) {}
    });

    // Periodic re-sync (every 60s) so the UI stays consistent without refresh
    setInterval(() => { if (socket && socket.connected) socket.emit('sync'); }, 60000);
  }

  // Apply local (per-viewer) audio/video overrides and global member status
  function applyMediaOverrides(userId) {
    const v = $('vid-' + userId), av = $('av-' + userId);
    if (!v) return;
    const blk = localBlock[userId] || {};
    const la = localAudio[userId] || {};
    const st = memberStatus.get(userId) || {};
    v.muted = !!(blk.audio || la.muted);
    v.volume = (la.volume !== undefined) ? la.volume : 1;
    const hideVideo = blk.video || st.cam === false;
    if (hideVideo) { v.style.display = 'none'; if (av) av.style.display = 'flex'; }
    else { v.style.display = ''; if (av) av.style.display = 'none'; }
  }

  // Keep a tile's displayed name in sync with the real display name
  function updateTileName(userId) {
    const el = $('name-' + userId);
    if (!el) return;
    const nm = memberNames.get(userId) || (peerMeta.get(userId) || {}).name;
    el.textContent = nm || 'بدون نام';
  }

  // Arrange the grid so tiles wrap responsively based on available width
  function layoutGrid() {
    if (!grid) return;
    grid.style.display = 'grid';
    grid.style.gap = '1rem';
    // As many boxes per row as fit (>=260px each); wraps to 2/3/... rows on narrow screens
    grid.style.gridTemplateColumns = 'repeat(auto-fit, minmax(min(100%, 260px), 1fr))';
  }
  window.addEventListener('resize', layoutGrid);

  function updateTileStatus(userId) {
    const st = memberStatus.get(userId) || {};
    const mic = $('mic-' + userId);
    if (mic) {
      mic.textContent = st.muted ? 'mic_off' : 'mic';
      mic.classList.toggle('text-error', !!st.muted);
      mic.classList.toggle('text-secondary', !st.muted);
    }
    applyMediaOverrides(userId);
  }

  // ---------- Sidebar / members ----------
  function renderMembers(list) {
    currentList = list;
    list.forEach(m => memberNames.set(m.userId, m.name));
    const wrap = $('member-list');
    const req = $('join-requests');
    wrap.innerHTML = ''; req.innerHTML = '';
    let pendingCount = 0;
    list.forEach(m => {
      const isSelf = m.userId === I.me.id;
      const row = document.createElement('div');
      row.className = 'flex items-center gap-3 p-2 hover:bg-surface-container rounded-lg transition-colors group';
      const mutedIcon = m.muted ? '<span class="material-symbols-outlined text-[10px] text-on-error">mic_off</span>' : '<span class="material-symbols-outlined text-[10px] text-on-secondary">mic</span>';
      let actions = '';
      if (I.me.is_admin && !isSelf) {
        actions = `<div class="opacity-0 group-hover:opacity-100 flex gap-1 transition-opacity">
          <button class="p-1.5 text-on-surface-variant hover:text-error hover:bg-error/10 rounded-md" title="حذف" onclick="adminRemove(${m.userId})"><span class="material-symbols-outlined text-[18px]">person_remove</span></button>
        </div>`;
      }
      row.innerHTML = `
        <div class="relative">
          <div class="w-10 h-10 rounded-full overflow-hidden border border-outline-variant">${avatarMarkup(m, '')}</div>
          <span class="absolute -bottom-1 -right-1 w-4 h-4 ${m.status === 'approved' ? 'bg-secondary' : 'bg-error'} rounded-full border-2 border-surface-container-low flex items-center justify-center">${mutedIcon}</span>
        </div>
        <div class="flex-1 min-w-0">
          <h4 class="font-body-sm font-semibold text-on-surface truncate">${escapeHtml(m.name)}${isSelf ? ' (شما)' : ''}</h4>
          <p class="font-label-sm text-on-surface-variant text-[10px]">${m.status === 'approved' ? (m.is_admin ? 'مدیر تماس' : 'عضو') : 'در انتظار تایید'}</p>
        </div>${actions}`;
      wrap.appendChild(row);
      row.oncontextmenu = (e) => openMemberMenu(e, m.userId, m.name);

      if (m.status === 'pending' && I.me.is_admin && !isSelf) {
        pendingCount++;
        const card = document.createElement('div');
        card.className = 'bg-surface-container border border-outline-variant/30 rounded-xl p-3 flex flex-col gap-3';
        card.innerHTML = `
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full overflow-hidden border border-outline-variant">${avatarMarkup(m, '')}</div>
            <div><h4 class="font-body-sm font-semibold text-on-surface">${escapeHtml(m.name)}</h4><p class="font-label-sm text-on-surface-variant text-[10px]">@${escapeHtml(m.username)}</p></div>
          </div>
          <div class="flex gap-2">
            <button class="flex-1 bg-secondary-container hover:bg-secondary text-on-secondary-container font-label-md py-1.5 rounded-md transition-colors" onclick="adminApprove(${m.userId})">تایید</button>
            <button class="flex-1 border border-error/50 text-error hover:bg-error/10 font-label-md py-1.5 rounded-md transition-colors" onclick="adminReject(${m.userId})">رد</button>
          </div>`;
        req.appendChild(card);
      }
    });
    $('participant-count').textContent = list.length;
    const badge = $('admin-badge'); if (badge) badge.classList.toggle('hidden', pendingCount === 0);
    list.forEach(m => updateTileName(m.userId));
    layoutGrid();
    if (I.me.is_admin) renderAdminMembers(list);
  }

  function renderAdminMembers(list) {
    const wrap = $('admin-members');
    if (!wrap) return;
    wrap.innerHTML = '';
    list.forEach(m => {
      if (m.userId === I.me.id) return;
      const st = memberStatus.get(m.userId) || {};
      const card = document.createElement('div');
      card.className = 'bg-surface-container border border-outline-variant/30 rounded-xl p-3 mb-3';
      const ctrl = (kind, label) => {
        const selfOn = localBlock[m.userId] && localBlock[m.userId][kind];
        const allOff = kind === 'audio' ? st.muted
                     : kind === 'video' ? (st.cam === false)
                     : (st.sharing === false);
        return `<div class="flex flex-col items-center gap-1">
          <span class="font-label-sm text-on-surface-variant text-[11px]">${label}</span>
          <div class="flex gap-1">
            <button class="text-[11px] px-2 py-1 rounded-md ${selfOn ? 'bg-error-container text-on-error-container' : 'bg-surface-container-high text-on-surface'} hover:opacity-80" onclick="adminBlockSelf(${m.userId},'${kind}')">برای من</button>
            <button class="text-[11px] px-2 py-1 rounded-md ${allOff ? 'bg-error-container text-on-error-container' : 'bg-secondary-container text-on-secondary-container'} hover:opacity-80" onclick="adminDisableAll(${m.userId},'${kind}')">${allOff ? 'روشن همه' : 'قطع همه'}</button>
          </div>
        </div>`;
      };
      card.innerHTML = `
        <div class="flex items-center gap-2 mb-3">
          <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-[12px]" style="background:${m.avatar_color}">${initials(m.name)}</div>
          <span class="font-body-sm text-on-surface truncate">${escapeHtml(m.name)}</span>
        </div>
        <div class="grid grid-cols-3 gap-2 text-center">
          ${ctrl('audio', 'صدا')}
          ${ctrl('video', 'تصویر')}
          ${ctrl('screen', 'صفحه')}
        </div>
        <div class="flex gap-2 mt-3">
          <button class="flex-1 bg-error-container/80 hover:bg-error-container text-on-error-container font-label-md py-1.5 rounded-md transition-colors" onclick="adminRemove(${m.userId})">حذف عضو</button>
          <button class="flex-1 bg-surface-container-high hover:bg-surface-bright text-on-surface font-label-md py-1.5 rounded-md transition-colors" onclick="adminBlock(${m.userId})">مسدود کردن</button>
        </div>`;
      wrap.appendChild(card);
    });
    if (!list.some(m => m.userId !== I.me.id)) {
      wrap.innerHTML = '<p class="font-body-sm text-body-sm text-on-surface-variant">عضو دیگری در تماس نیست.</p>';
    }
  }

  // ---------- Chat ----------
  function appendMessage(userId, name, body, self) {
    const box = $('chat-messages');
    const wrap = document.createElement('div');
    wrap.className = 'flex flex-col ' + (self ? 'items-end' : 'items-start') + ' gap-1';
    const meta = peerMeta.get(userId) || { name: name, avatar: '' };
    const av = self
      ? '<div class="w-7 h-7 rounded-full overflow-hidden border border-outline-variant">' + avatarMarkup({ name: I.me.name, avatar: I.me.avatar, avatar_color: I.me.avatar_color }, '') + '</div>'
      : '<div class="w-7 h-7 rounded-full overflow-hidden border border-outline-variant">' + avatarMarkup(meta, '') + '</div>';
    wrap.innerHTML = `
      <div class="flex items-center gap-2 mb-1">
        ${av}
        <span class="font-label-sm text-on-surface-variant">${escapeHtml(self ? 'شما' : (name || 'بدون نام'))}</span>
      </div>
      <div class="${self ? 'bg-primary-container text-on-primary-container' : 'bg-surface-container-high text-on-surface'} p-3 rounded-2xl ${self ? 'rounded-tl-sm' : 'rounded-tr-sm'} text-body-sm max-w-[85%] border border-white/5">${escapeHtml(body)}</div>`;
    box.appendChild(wrap);
    box.scrollTop = box.scrollHeight;
  }

  window.sendChat = function () {
    const inp = $('chat-input');
    const body = inp.value.trim();
    if (!body) return;
    appendMessage(I.me.id, I.me.name, body, true);
    inp.value = '';
    socket && socket.emit('chat', { body });
    fetch('api/save_message.php', {
      method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ csrf: I.csrf, body, meeting_id: I.meeting.id })
    }).catch(() => {});
  };

  // ---------- Emoji ----------
  function buildEmojiGrid() {
    const g = $('emoji-grid');
    EMOJIS.forEach(em => {
      const b = document.createElement('button');
      b.className = 'text-2xl hover:scale-110 transition-transform p-1';
      b.textContent = em;
      b.onclick = () => { spawnEmoji(em, true); toggleEmojiPicker(true); };
      g.appendChild(b);
    });
  }
  function spawnEmoji(em, broadcast) {
    const canvas = $('emoji-canvas');
    const d = document.createElement('div');
    d.className = 'emoji-float';
    d.textContent = em;
    d.style.left = (Math.random() * 80 + 20) + 'px';
    d.style.bottom = '100px';
    canvas.appendChild(d);
    setTimeout(() => d.remove(), 2000);
    if (broadcast) {
      socket && socket.emit('emoji', { emoji: em });
      fetch('api/save_emoji.php', {
        method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ csrf: I.csrf, emoji: em, meeting_id: I.meeting.id })
      }).catch(() => {});
    }
  }
  window.spawnEmoji = spawnEmoji;
  window.toggleEmojiPicker = function (forceHide) {
    const p = $('emoji-picker');
    if (forceHide === true) { p.classList.add('hidden'); return; }
    p.classList.toggle('hidden');
  };

  // ---------- Controls ----------
  window.toggleMic = function () {
    if (!localStream) { showToast('دسترسی به میکروفون وجود ندارد'); return; }
    micOn = !micOn;
    localStream.getAudioTracks().forEach(t => t.enabled = micOn);
    updateControlUI();
    socket && socket.emit('member-status', { muted: !micOn, cam: camOn, sharing });
  };
  window.toggleCam = function () {
    if (!localStream) { showToast('دسترسی به دوربین وجود ندارد'); return; }
    camOn = !camOn;
    localStream.getVideoTracks().forEach(t => t.enabled = camOn);
    if (camOn && !sharing) { currentVideoTrack = cameraTrack; }
    else if (!camOn && !sharing) { currentVideoTrack = null; }
    updateSelfVideo();
    updateControlUI();
    socket && socket.emit('member-status', { muted: !micOn, cam: camOn, sharing });
  };
  window.toggleShare = function () {
    if (sharing) { stopShare(); return; }
    navigator.mediaDevices.getDisplayMedia({ video: true }).then(screenStream => {
      screenTrack = screenStream.getVideoTracks()[0];
      screenTrack.onended = stopShare;
      sharing = true;
      replaceOutgoingVideo(screenTrack);
      updateControlUI();
      socket && socket.emit('member-status', { muted: !micOn, cam: camOn, sharing });
    }).catch(() => {});
  };
  function stopShare() {
    if (screenTrack) { screenTrack.stop(); screenTrack = null; }
    sharing = false;
    currentVideoTrack = camOn ? cameraTrack : null;
    replaceOutgoingVideo(currentVideoTrack);
    updateControlUI();
    socket && socket.emit('member-status', { muted: !micOn, cam: camOn, sharing });
  }

  window.copyLink = function () {
    const url = location.href;
    if (navigator.clipboard) navigator.clipboard.writeText(url).then(() => showToast('لینک کپی شد')).catch(() => showToast('لینک: ' + url));
    else showToast('لینک تماس: ' + url);
  };

  window.leaveCall = function () {
    socket && socket.emit('leave');
    fetch('api/leave_meeting.php', {
      method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ csrf: I.csrf, meeting_id: I.meeting.id })
    }).catch(() => {});
    pcMap.forEach(pc => pc.close());
    if (localStream) localStream.getTracks().forEach(t => t.stop());
    location.href = I.base + '/home.php';
  };

  // ---------- Admin actions ----------
  function postAdmin(url, userId) {
    return fetch(url, {
      method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ csrf: I.csrf, participant_id: String(userId) })
    }).then(r => r.json());
  }
  window.adminApprove = function (userId) {
    postAdmin('api/approve_member.php', userId).then(() => socket && socket.emit('admin-approve', { userId }));
  };
  window.adminReject = function (userId) {
    postAdmin('api/reject_member.php', userId).then(() => socket && socket.emit('admin-reject', { userId }));
  };
  window.adminRemove = function (userId) {
    postAdmin('api/remove_member.php', userId).then(() => socket && socket.emit('admin-remove', { userId }));
  };
  window.adminBlockSelf = function (userId, kind) {
    localBlock[userId] = localBlock[userId] || {};
    localBlock[userId][kind] = !localBlock[userId][kind];
    applyMediaOverrides(userId);
    if (I.me.is_admin) renderAdminMembers(currentList);
  };
  window.adminDisableAll = function (userId, kind) {
    const st = memberStatus.get(userId) || {};
    const off = kind === 'audio' ? !st.muted : kind === 'video' ? (st.cam !== false) : (st.sharing !== false);
    socket && socket.emit('admin-disable', { userId, kind, off });
  };

  // ---------- Tabs ----------
  window.switchTab = function (tab) {
    ['members', 'chat', 'admin'].forEach(t => {
      const c = $('content-' + t); if (c) { c.classList.add('hidden'); c.classList.remove('flex'); }
      const el = $('tab-' + t); if (el) { el.classList.remove('text-primary', 'border-primary'); el.classList.add('text-on-surface-variant', 'border-transparent'); }
    });
    const c2 = $('content-' + tab); if (c2) { c2.classList.remove('hidden'); c2.classList.add('flex'); }
    const a = $('tab-' + tab); if (a) { a.classList.add('text-primary', 'border-primary'); a.classList.remove('text-on-surface-variant', 'border-transparent'); }
  };

  // ---------- Right-click member menu (per-viewer audio control) ----------
  function openMemberMenu(e, userId, name) {
    e.preventDefault();
    menuUserId = userId;
    const la = localAudio[userId] || {};
    $('member-menu-name').textContent = name || 'عضو';
    $('mm-mute').checked = !!la.muted;
    $('mm-vol').value = (la.volume !== undefined) ? Math.round(la.volume * 100) : 100;
    const menu = $('member-menu');
    menu.classList.remove('hidden');
    menu.style.left = Math.min(e.clientX, window.innerWidth - 230) + 'px';
    menu.style.top = Math.min(e.clientY, window.innerHeight - 150) + 'px';
  }
  window.openMemberMenu = openMemberMenu;
  window.memberMenuMute = function (checked) {
    if (!menuUserId) return;
    localAudio[menuUserId] = localAudio[menuUserId] || {};
    localAudio[menuUserId].muted = checked;
    applyMediaOverrides(menuUserId);
  };
  window.memberMenuVolume = function (val) {
    if (!menuUserId) return;
    localAudio[menuUserId] = localAudio[menuUserId] || {};
    localAudio[menuUserId].volume = val / 100;
    applyMediaOverrides(menuUserId);
  };

  // ---------- Init ----------
  function init() {
    renderSelf();
    if (!approved) $('waiting-overlay').classList.remove('hidden');
    // Seed participants from server data immediately so member boxes + sidebar
    // are visible even before the realtime channel connects.
    if (I.participants && I.participants.length) {
      const mapped = I.participants.map(p => ({
        userId: p.user_id, name: p.display_name, username: p.username,
        status: p.status, is_admin: (p.role === 'admin'),
        muted: !!p.muted, cam: !!p.cam_on, sharing: !!p.sharing,
        avatar_color: p.avatar_color, avatar: p.avatar
      }));
      mapped.forEach(m => { if (m.userId !== I.me.id) getOrCreateTile(m.userId, m); });
      renderMembers(mapped);
    }
    // seed messages
    (I.messages || []).forEach(m => appendMessage(m.user_id, m.display_name, m.body, m.user_id === I.me.id));
    // seed emoji (show briefly)
    (I.emojis || []).forEach(e => spawnEmoji(e.emoji, false));
    buildEmojiGrid();
    layoutGrid();
    const mm = $('member-menu');
    if (mm) mm.addEventListener('click', e => e.stopPropagation());
    document.addEventListener('click', () => { const m = $('member-menu'); if (m) m.classList.add('hidden'); });
    connect();
    startMedia().finally(() => {
      mediaReady = true;
      if (pendingJoin || (socket && socket.connected)) doJoin();
      broadcastStatus();
    });
  }
  init();
})();
