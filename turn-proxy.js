// turn-proxy.js - TCP<->UDP framer for node-turn (TURN over TCP through the tunnel)
// Listens on TCP 3478, forwards framed STUN/TURN messages to node-turn on UDP 3478.
const net = require('net');
const dgram = require('dgram');

const TCP_PORT = 3478;
const UDP_PORT = 3478;
const UDP_HOST = '127.0.0.1';

const server = net.createServer((tcp) => {
  const udp = dgram.createSocket('udp4');
  let buf = Buffer.alloc(0);

  udp.on('message', (msg) => {
    if (tcp.writable) tcp.write(msg);
  });
  udp.on('error', () => {});
  tcp.on('error', () => { try { udp.close(); } catch (e) {} });

  tcp.on('data', (chunk) => {
    buf = Buffer.concat([buf, chunk]);
    // STUN/TURN message = 20-byte header + attributes. Length field (offset 2)
    // is the size of the attributes only, so total = 20 + length.
    while (buf.length >= 20) {
      const len = buf.readUInt16BE(2);
      const total = 20 + len;
      if (buf.length < total) break; // wait for full message
      const msg = buf.slice(0, total);
      buf = buf.slice(total);
      udp.send(msg, UDP_PORT, UDP_HOST, (e) => { if (e) {} });
    }
  });

  tcp.on('close', () => { try { udp.close(); } catch (e) {} });
  tcp.on('end', () => { try { udp.close(); } catch (e) {} });
});

server.listen(TCP_PORT, '0.0.0.0', () => {
  console.log('TURN TCP proxy listening on port ' + TCP_PORT);
});
server.on('error', (e) => console.error('proxy error', e.message));
