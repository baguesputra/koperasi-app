import express from 'express';
import { getOrCreateSession, getSession, getAllSessions } from '../baileys/client.js';

const router = express.Router();

router.get('/', async (req, res) => {
  const { sessionId } = req.query;
  const logger = req.logger;

  try {
    if (sessionId) {
      const session = await getOrCreateSession(sessionId, logger);
      const status = session.getStatus();
      res.json({
        status: status.connected ? 'healthy' : 'connecting',
        ...status,
        uptime: process.uptime(),
        memory: process.memoryUsage(),
      });
    } else {
      const sessions = getAllSessions();
      const sessionList = sessions.map(s => s.getStatus());
      const anyConnected = sessionList.some(s => s.connected);
      
      res.json({
        status: anyConnected ? 'healthy' : 'connecting',
        sessions: sessionList,
        uptime: process.uptime(),
        memory: process.memoryUsage(),
      });
    }
  } catch (err) {
    logger.error({ err, sessionId }, 'Health check error');
    res.status(503).json({ 
      status: 'error',
      error: err.message 
    });
  }
});

router.post('/disconnect', async (req, res) => {
  const { sessionId } = req.body;
  const logger = req.logger;

  if (!sessionId) {
    return res.status(400).json({ error: 'sessionId required' });
  }

  try {
    const { removeSession } = await import('../baileys/client.js');
    await removeSession(sessionId);
    logger.info({ sessionId }, 'Session disconnected and removed');
    res.json({ success: true, message: 'Session disconnected' });
  } catch (err) {
    logger.error({ err, sessionId }, 'Error disconnecting session');
    res.status(500).json({ error: err.message });
  }
});

export default router;