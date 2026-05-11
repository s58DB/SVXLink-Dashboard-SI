const WebSocket = require('ws');
const { spawn } = require('child_process');

const wsPort = Number(process.env.SVX_AUDIO_WS_PORT || 8001);
const configuredDevice = process.env.SVX_AUDIO_DEVICE;
const audioDevices = [
  configuredDevice,
  'plughw:1,1',
  'plughw:Loop,1,0',
  'plughw:Loopback,1,0',
  'plughw:1,0'
].filter(Boolean).map((device) => device.replace(/^alsa:/, ''));

let record = null;
let activeDeviceIndex = 0;
const clients = new Set();

function currentDevice() {
  return audioDevices[activeDeviceIndex % audioDevices.length];
}

function scheduleRestart() {
  activeDeviceIndex = (activeDeviceIndex + 1) % audioDevices.length;
  setTimeout(startRecording, 1000);
}

function startRecording() {
  const device = currentDevice();
  console.log(`Starting audio capture from ${device}...`);

  record = spawn('arecord', [
    '-D', device,
    '-f', 'S16_LE',
    '-r', '48000',
    '-c', '1'
  ], {
    stdio: ['ignore', 'pipe', 'pipe']
  });

  record.stdout.on('data', (chunk) => {
    for (const ws of clients) {
      if (ws.readyState === WebSocket.OPEN) {
        ws.send(chunk);
      }
    }
  });

  record.stderr.on('data', (chunk) => {
    console.warn(chunk.toString().trim());
  });

  record.on('exit', (code, signal) => {
    console.warn(`arecord exited for ${device} (code ${code}, signal ${signal}). Trying next device...`);
    record = null;
    scheduleRestart();
  });
}

startRecording();

const wss = new WebSocket.Server({ port: wsPort });

wss.on('connection', (ws) => {
  clients.add(ws);
  console.log(`Dashboard connected (${clients.size} client/s)`);

  ws.on('close', () => {
    clients.delete(ws);
    console.log(`Dashboard disconnected (${clients.size} client/s)`);
  });
});

wss.on('listening', () => {
  console.log(`WebSocket server listening on ws://0.0.0.0:${wsPort}/`);
});

function shutdown() {
  if (record) {
    record.kill();
  }
}

process.on('exit', shutdown);
process.on('SIGINT', () => process.exit());
process.on('SIGTERM', () => process.exit());
