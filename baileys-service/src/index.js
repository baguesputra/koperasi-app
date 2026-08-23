import express from 'express';
import pino from 'pino';
import { getAllSessions, removeSession } from './baileys/client.js';
import sendRoute from './routes/send.js';
import qrRoute from './routes/qr.js';
import healthRoute from './routes/health.js';

const app = express();
const logger = pino({ level: process.env.LOG_LEVEL || 'info' });

app.use(express.json({ limit: '10mb' }));
app.use((req, res, next) => {
  req.logger = logger;
  next();
});

app.use('/api/send', sendRoute);
app.use('/api/qr', qrRoute);
app.use('/api/health', healthRoute);

app.use((err, req, res, next) => {
  logger.error({ err }, 'Unhandled error');
  res.status(500).json({ error: 'Internal server error' });
});

const PORT = process.env.BAILEYS_PORT || 3000;

async function start() {
  try {
    // Verify database connection by listing sessions
    const { listSessions } = await import('./baileys/client.js');
    const sessions = await listSessions();
    logger.info({ count: sessions.length }, 'Existing sessions found in database');

    app.listen(PORT, '0.0.0.0', () => {
      logger.info({ port: PORT }, 'Baileys service started');
    });
  } catch (err) {
    logger.fatal({ err }, 'Failed to start service');
    process.exit(1);
  }
}

process.on('SIGTERM', async () => {
  logger.info('SIGTERM received, shutting down all sessions...');
  const sessions = getAllSessions();
  for (const session of sessions) {
    await session.disconnect();
  }
  process.exit(0);
});

start();

export default app;