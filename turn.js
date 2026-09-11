// turn.js - Local TURN relay server (node-turn) for Black Meet
const Turn = require('node-turn');
const fs = require('fs');
const path = require('path');

const cfg = JSON.parse(fs.readFileSync(path.join(__dirname, 'config.json'), 'utf8'));
const t = cfg.turn || {};

const server = new Turn({
  listeningPort: t.port || 3478,
  authMech: 'long-term',
  credentials: { [t.user]: t.pass },
  realm: 'blackmeet',
  debugLevel: 'ERROR'
});
server.start();
console.log('Black Meet TURN server listening on port ' + (t.port || 3478));
