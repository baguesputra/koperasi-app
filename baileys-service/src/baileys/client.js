import mysql from 'mysql2/promise';
import { useMultiFileAuthState } from '@whiskeysockets/baileys';
import { Boom } from '@hapi/boom';
import pino from 'pino';
import { formatPhone } from '../utils/phone.js';

let pool = null;

function getPool() {
  if (!pool) {
    pool = mysql.createPool({
      host: process.env.MYSQL_HOST || 'localhost',
      user: process.env.MYSQL_USER || 'root',
      password: process.env.MYSQL_PASSWORD || '',
      database: process.env.MYSQL_DATABASE || 'koperasi_db',
      waitForConnections: true,
      connectionLimit: 10,
      queueLimit: 0,
    });
  }
  return pool;
}

async function saveAuthState(sessionId, creds, keys) {
  const db = getPool();
  const credsJson = JSON.stringify(creds);
  const keysJson = JSON.stringify(keys);
  await db.execute(
    `INSERT INTO baileys_sessions (session_id, creds, \`keys\`, updated_at)
     VALUES (?, ?, ?, NOW())
     ON DUPLICATE KEY UPDATE creds = ?, \`keys\` = ?, updated_at = NOW()`,
    [sessionId, credsJson, keysJson, credsJson, keysJson]
  );
}

async function loadAuthState(sessionId) {
  const db = getPool();
  const [rows] = await db.execute(
    'SELECT creds, \`keys\` FROM baileys_sessions WHERE session_id = ?',
    [sessionId]
  );
  if (rows.length === 0) return null;
  return {
    creds: JSON.parse(rows[0].creds),
    keys: JSON.parse(rows[0].keys),
  };
}

async function deleteAuthState(sessionId) {
  const db = getPool();
  await db.execute('DELETE FROM baileys_sessions WHERE session_id = ?', [sessionId]);
}

async function listSessions() {
  const db = getPool();
  const [rows] = await db.execute('SELECT session_id, updated_at FROM baileys_sessions ORDER BY updated_at DESC');
  return rows;
}

class BaileysSession {
  constructor(sessionId, logger) {
    this.sessionId = sessionId;
    this.logger = logger.child({ sessionId });
    this.sock = null;
    this.store = null;
    this.qrCode = null;
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 10;
    this.reconnectDelay = 5000;
  }

  async init() {
    const { state, saveCreds } = await useMultiFileAuthState(`./auth_info/${this.sessionId}`);

    const savedState = await loadAuthState(this.sessionId);
    if (savedState) {
      state.creds = savedState.creds;
      state.keys = savedState.keys;
      this.logger.info('Loaded saved auth state from database');
    }

    const { default: makeWASocket, DisconnectReason } = await import('@whiskeysockets/baileys');
    
    this.sock = makeWASocket({
      auth: state,
      logger: pino({ level: 'silent' }),
      printQRInTerminal: false,
      browser: ['Koperasi Bot', 'Chrome', '1.0.0'],
    });

    this.sock.ev.on('creds.update', async (creds) => {
      await saveAuthState(this.sessionId, creds, state.keys);
      await saveCreds();
    });

    this.sock.ev.on('connection.update', (update) => {
      const { connection, lastDisconnect, qr } = update;
      
      if (qr) {
        this.logger.info({ qr: qr.substring(0, 50) + '...' }, 'QR Code received');
        this.qrCode = qr;
      }
      
      if (connection === 'close') {
        const statusCode = lastDisconnect?.error instanceof Boom ? lastDisconnect.error.output.statusCode : 500;
        const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
        this.logger.warn({ statusCode, shouldReconnect, attempts: this.reconnectAttempts }, 'Connection closed');
        
        this.qrCode = null;
        
        if (shouldReconnect && this.reconnectAttempts < this.maxReconnectAttempts) {
          this.reconnectAttempts++;
          const delay = this.reconnectDelay * Math.min(this.reconnectAttempts, 5);
          this.logger.info({ delay }, 'Scheduling reconnect');
          setTimeout(() => this.init(), delay);
        } else if (shouldReconnect) {
          this.logger.error('Max reconnect attempts reached');
        }
      } else if (connection === 'open') {
        this.logger.info('WhatsApp connected successfully');
        this.qrCode = null;
        this.reconnectAttempts = 0;
      }
    });

    this.sock.ev.on('messages.upsert', ({ messages, type }) => {
      if (type === 'notify') {
        for (const msg of messages) {
          if (!msg.key.fromMe && msg.message) {
            this.logger.debug({ from: msg.key.remoteJid }, 'Received message');
          }
        }
      }
    });

    return this;
  }

  async sendMessage(to, text) {
    if (!this.sock?.user) {
      return { success: false, error: 'Not connected' };
    }
    const jid = formatPhone(to) + '@s.whatsapp.net';
    try {
      await this.sock.sendMessage(jid, { text });
      return { success: true };
    } catch (err) {
      this.logger.error({ err, to }, 'Failed to send message');
      return { success: false, error: err.message };
    }
  }

  getQR() {
    return this.qrCode;
  }

  getStatus() {
    return {
      sessionId: this.sessionId,
      connected: !!this.sock?.user,
      user: this.sock?.user ? { id: this.sock.user.id, name: this.sock.user.name, phone: this.sock.user.id.split(':')[0] } : null,
      hasQR: !!this.qrCode,
      reconnectAttempts: this.reconnectAttempts,
    };
  }

  async disconnect() {
    if (this.sock) {
      await this.sock.end(undefined);
      this.sock = null;
      this.qrCode = null;
    }
  }
}

const sessions = new Map();

export async function getOrCreateSession(sessionId, logger) {
  if (!sessions.has(sessionId)) {
    const session = new BaileysSession(sessionId, logger);
    await session.init();
    sessions.set(sessionId, session);
  }
  return sessions.get(sessionId);
}

export function getSession(sessionId) {
  return sessions.get(sessionId);
}

export function getAllSessions() {
  return Array.from(sessions.values());
}

export async function removeSession(sessionId) {
  const session = sessions.get(sessionId);
  if (session) {
    await session.disconnect();
    await deleteAuthState(sessionId);
    sessions.delete(sessionId);
  }
}

export { listSessions };