// server.js - Black Meet real-time signaling server (Socket.IO)
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const http = require('http');
const { Server } = require('socket.io');

const cfgPath = path.join(__dirname, 'config.json');
const cfg = JSON.parse(fs.readFileSync(cfgPath, 'utf8'));
const SECRET = cfg.secret;
const BASE = cfg.base_url || 'http://localhost/black-meet';
const PORT = cfg.socket_port || 3000;
const pendingClose = new Map(); // roomId -> timeout

function scheduleCloseMeeting(roomId) {
  if (pendingClose.has(roomId)) return;
  const t = setTimeout(() => {
    pendingClose.delete(roomId);
    fetch(BASE + '/api/close_meeting.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ secret: SECRET, room: roomId })
    }).catch(() => {});
  }, 5000);
  pendingClose.set(roomId, t);
}

function verifyToken(token, out = {}) {
  if (typeof token !== 'string') return false;
  const idx = token.lastIndexOf('.');
  if (idx < 0) return false;
  const payload = token.slice(0, idx);
  const sig = token.slice(idx + 1);
  const expected = crypto.createHmac('sha256', SECRET).update(payload).digest('hex');
  if (sig !== expected) return false;
  const f = payload.split('|');
  if (f.length !== 3) return false;
  if (Date.now() / 1000 - parseInt(f[2], 10) > 86400) return false;
  out.user_id = parseInt(f[0], 10);
  out.meeting_id = parseInt(f[1], 10);
  return true;
}

const server = http.createServer((req, res) => {
  res.writeHead(200, { 'Content-Type': 'text/plain; charset=utf-8' });
  res.end('Black Meet signaling server is running.');
});
const io = new Server(server, { cors: { origin: '*' } });

// roomId -> { meetingId, sockets:Map<socketId,info>, userSockets:Map<userId,Set>, pending:Set<userId> }
const rooms = new Map();

function peerInfo(s) {
  return {
    userId: s.userId, name: s.name, username: s.username,
    avatar_color: s.avatar_color, is_admin: s.is_admin,
    muted: s.muted, cam: s.cam, sharing: s.sharing,
    approved: s.approved, status: s.approved ? 'approved' : 'pending'
  };
}

function broadcastMemberList(roomId) {
  const r = rooms.get(roomId);
  if (!r) return;
  const list = [];
  r.sockets.forEach(s => list.push(peerInfo(s)));
  io.to(roomId).emit('member-list', list);
}

io.on('connection', (socket) => {
  socket.on('join', (payload = {}) => {
    const { room, token, user } = payload;
    const dec = {};
    if (!room || !verifyToken(token, dec)) {
      return socket.emit('error-msg', { msg: 'توکن نامعتبر' });
    }
    if (!user || user.id !== dec.user_id) {
      return socket.emit('error-msg', { msg: 'توکن نامعتبر' });
    }
    const meetingId = dec.meeting_id;
    const userId = dec.user_id;

    const roomState = rooms.get(room);
    if (roomState && roomState.blocked && roomState.blocked.has(userId)) {
      return socket.emit('you-blocked');
    }

    if (!rooms.has(room)) {
      rooms.set(room, { meetingId, sockets: new Map(), userSockets: new Map(), pending: new Set(), blocked: new Set() });
      if (pendingClose.has(room)) { clearTimeout(pendingClose.get(room)); pendingClose.delete(room); }
    }
    const r = rooms.get(room);
    const info = {
      userId, name: user.name, username: user.username, avatar_color: user.avatar_color, avatar: user.avatar || '',
      is_admin: !!user.is_admin, muted: false, cam: false, sharing: false,
      approved: user.status === 'approved', socketId: socket.id
    };
    socket.data = { room, meetingId, userId, is_admin: info.is_admin, name: user.name };
    socket.join(room);
    r.sockets.set(socket.id, info);
    if (!r.userSockets.has(userId)) r.userSockets.set(userId, new Set());
    r.userSockets.get(userId).add(socket.id);

    if (info.approved) {
      // send full peer list to this socket
      const peers = [];
      r.sockets.forEach((s, sid) => { if (sid !== socket.id) peers.push(peerInfo(s)); });
      socket.emit('room-peers', peers);
      socket.emit('approved');
      socket.to(room).emit('peer-joined', peerInfo(info));
    } else {
      r.pending.add(userId);
      socket.to(room).emit('join-request', peerInfo(info));
    }
    broadcastMemberList(room);
  });

  socket.on('signal', (msg = {}) => {
    const r = rooms.get(socket.data.room);
    if (!r) return;
    const targets = r.userSockets.get(msg.to);
    if (!targets) return;
    targets.forEach(sid => {
      io.to(sid).emit('signal', { from: socket.data.userId, data: msg.data });
    });
  });

  socket.on('chat', (msg = {}) => {
    if (!socket.data.room) return;
    socket.to(socket.data.room).emit('chat', {
      userId: socket.data.userId, name: socket.data.name || '', body: msg.body, ts: Date.now()
    });
  });

  socket.on('emoji', (msg = {}) => {
    if (!socket.data.room) return;
    socket.to(socket.data.room).emit('emoji', {
      userId: socket.data.userId, name: socket.data.name || '', emoji: msg.emoji
    });
  });

  socket.on('member-status', (st = {}) => {
    const r = rooms.get(socket.data.room);
    if (!r) return;
    const s = r.sockets.get(socket.id);
    if (s) { s.muted = !!st.muted; s.cam = !!st.cam; s.sharing = !!st.sharing; }
    socket.to(socket.data.room).emit('member-status', {
      userId: socket.data.userId, muted: s.muted, cam: s.cam, sharing: s.sharing
    });
  });

  // ---- admin actions ----
  function isAdminHere() { return socket.data && socket.data.is_admin; }

  socket.on('admin-approve', (msg = {}) => {
    const r = rooms.get(socket.data.room);
    if (!r || !isAdminHere()) return;
    const uid = msg.userId;
    r.pending.delete(uid);
    // mark all sockets of that user approved
    (r.userSockets.get(uid) || []).forEach && r.userSockets.get(uid).forEach(sid => {
      const s = r.sockets.get(sid); if (s) s.approved = true;
    });
    // notify target
    (r.userSockets.get(uid) || new Set()).forEach(sid => {
      const s = r.sockets.get(sid);
      io.to(sid).emit('you-approved');
      const peers = [];
      r.sockets.forEach((ss, ssid) => { if (ssid !== sid) peers.push(peerInfo(ss)); });
      io.to(sid).emit('room-peers', peers);
    });
    // notify others about the new peer
    socket.to(socket.data.room).emit('peer-joined', peerInfo(r.sockets.get([...r.userSockets.get(uid)][0])));
    broadcastMemberList(socket.data.room);
  });

  socket.on('admin-reject', (msg = {}) => kickUser(msg.userId, 'you-rejected'));
  socket.on('admin-remove', (msg = {}) => kickUser(msg.userId, 'you-removed'));

  function kickUser(uid, evt) {
    const r = rooms.get(socket.data.room);
    if (!r || !isAdminHere()) return;
    r.pending.delete(uid);
    (r.userSockets.get(uid) || new Set()).forEach(sid => {
      io.to(sid).emit(evt);
      const s = r.sockets.get(sid);
      if (s) s.approved = false;
    });
    broadcastMemberList(socket.data.room);
  }

  socket.on('admin-block', (msg = {}) => {
    const r = rooms.get(socket.data.room);
    if (!r || !isAdminHere()) return;
    const uid = msg.userId;
    r.blocked.add(uid);
    (r.userSockets.get(uid) || new Set()).forEach(sid => io.to(sid).emit('you-blocked'));
    broadcastMemberList(socket.data.room);
  });

  // Pending user asks admin to be notified (sound + notification)
  socket.on('alert-admin', () => {
    const r = rooms.get(socket.data.room);
    if (!r) return;
    r.sockets.forEach(s => {
      if (s.is_admin) io.to(s.socketId).emit('join-alert', { userId: socket.data.userId, name: socket.data.name || '' });
    });
  });

  socket.on('admin-disable', (msg = {}) => {
    const r = rooms.get(socket.data.room);
    if (!r || !isAdminHere()) return;
    const uid = msg.userId;
    const kind = msg.kind; // 'audio' | 'video' | 'screen'
    const off = !!msg.off;
    (r.userSockets.get(uid) || new Set()).forEach(sid => {
      io.to(sid).emit('force-disable', { kind, off });
    });
    (r.userSockets.get(uid) || new Set()).forEach(sid => {
      const s = r.sockets.get(sid); if (!s) return;
      if (kind === 'audio') s.muted = off;
      else if (kind === 'video') s.cam = !off;
      else if (kind === 'screen') s.sharing = !off;
    });
    const st = {};
    if (kind === 'audio') st.muted = off;
    else if (kind === 'video') st.cam = !off;
    else if (kind === 'screen') st.sharing = !off;
    socket.to(socket.data.room).emit('member-status', Object.assign({ userId: uid }, st));
    broadcastMemberList(socket.data.room);
  });

  function cleanup() {
    const r = rooms.get(socket.data.room);
    if (!r) return;
    const uid = socket.data.userId;
    r.sockets.delete(socket.id);
    const set = r.userSockets.get(uid);
    if (set) { set.delete(socket.id); if (set.size === 0) { r.userSockets.delete(uid); r.pending.delete(uid); } }
    if (r.sockets.size === 0) {
      const roomId = socket.data.room;
      rooms.delete(roomId);
      scheduleCloseMeeting(roomId);
      return;
    }
    if (set && set.size === 0) {
      socket.to(socket.data.room).emit('peer-left', { userId: uid });
    }
    broadcastMemberList(socket.data.room);
  }
  socket.on('leave', cleanup);
  socket.on('disconnect', cleanup);
  socket.on('sync', () => { if (socket.data.room) broadcastMemberList(socket.data.room); });
});

server.listen(PORT, () => {
  console.log('Black Meet signaling server listening on http://localhost:' + PORT);
});
