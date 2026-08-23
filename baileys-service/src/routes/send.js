import express from 'express';
import { getOrCreateSession, getSession, getAllSessions } from '../baileys/client.js';

const router = express.Router();

router.post('/', async (req, res) => {
  const { to, message, sessionId = 'main' } = req.body;
  const logger = req.logger;

  if (!to || !message) {
    return res.status(400).json({ error: 'Missing required fields: to, message' });
  }

  try {
    const session = await getOrCreateSession(sessionId, logger);
    
    if (!session.sock?.user) {
      logger.warn({ sessionId }, 'WhatsApp not connected');
      return res.status(503).json({ error: 'WhatsApp not connected', sessionId });
    }

    const result = await session.sendMessage(to, message);
    
    if (result.success) {
      logger.info({ to, sessionId }, 'Message sent successfully');
      return res.json({ success: true, message: 'Message sent', sessionId });
    } else {
      logger.error({ to, sessionId, error: result.error }, 'Failed to send message');
      return res.status(500).json({ error: result.error || 'Failed to send message', sessionId });
    }
  } catch (err) {
    logger.error({ err, to, sessionId }, 'Error sending message');
    return res.status(500).json({ error: err.message, sessionId });
  }
});

export default router;