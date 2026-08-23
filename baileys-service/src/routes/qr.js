import express from 'express';
import QRCode from 'qrcode';
import { getOrCreateSession, getSession, getAllSessions } from '../baileys/client.js';

const router = express.Router();

router.get('/', async (req, res) => {
  const { sessionId = 'main' } = req.query;
  const logger = req.logger;

  try {
    const session = await getOrCreateSession(sessionId, logger);
    const qr = session.getQR();
    const status = session.getStatus();
    
    if (!qr) {
      if (status.connected) {
        return res.json({ connected: true, message: 'Already connected', session: status });
      }
      return res.status(404).json({ error: 'No QR code available, waiting for connection...', session: status });
    }

    try {
      const qrImage = await QRCode.toDataURL(qr);
      res.json({ qr: qrImage, qrString: qr, session: status });
    } catch (err) {
      logger.error({ err, sessionId }, 'Failed to generate QR code');
      res.status(500).json({ error: 'Failed to generate QR code' });
    }
  } catch (err) {
    logger.error({ err, sessionId }, 'Error getting QR');
    res.status(500).json({ error: err.message });
  }
});

router.get('/list', async (req, res) => {
  const logger = req.logger;
  try {
    const sessions = getAllSessions();
    const sessionList = sessions.map(s => s.getStatus());
    res.json({ sessions: sessionList });
  } catch (err) {
    logger.error({ err }, 'Error listing sessions');
    res.status(500).json({ error: err.message });
  }
});

export default router;