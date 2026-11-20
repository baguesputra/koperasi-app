// ponytail: single-session WA gateway; multi-device/queueing add when needed
const crypto = require('node:crypto')
const http = require('node:http')
const fs = require('node:fs')
const path = require('node:path')
const qrcode = require('qrcode')
const {
    default: makeWASocket,
    useMultiFileAuthState,
    fetchLatestBaileysVersion,
    DisconnectReason,
} = require('@whiskeysockets/baileys')

const PORT = Number(process.env.PORT || 3000)
// ponytail: TOKEN wajib; tanpa fallback default. Mati saat boot bila kosong.
const TOKEN = (process.env.TOKEN || process.env.BAILEYS_TOKEN || '').trim()
if (!TOKEN) {
    console.error('[baileys-service] FATAL: env TOKEN kosong. Isi TOKEN di baileys-service/.env.')
    process.exit(1)
}
const SESSION_DIR = path.join(process.cwd(), 'session')

let sock = null
let latestQr = null
let connected = false
let sedangKirim = false
let starting = false
let reconnectTimer = null
let reconnectDelayMs = 3000

const JEDA_KIRIM_MS = Number(process.env.SEND_INTERVAL_MS || 2000)
// ponytail: antrean in-memory, "terkirim" tercatat saat masuk antrean; kalau kehilangan
// pesan saat restart/offline jadi masalah, pindah ke antrian persist (redis/db)
const antrean = []

setInterval(async () => {
    if (sedangKirim || !antrean.length || !connected || !sock) return
    sedangKirim = true
    const tugas = antrean.shift()
    try {
        await tugas()
    } catch (e) {
        console.error('[wa] kirim gagal:', e?.message)
    } finally {
        sedangKirim = false
    }
}, JEDA_KIRIM_MS)

function pastikanSessionDir() {
    fs.mkdirSync(SESSION_DIR, { recursive: true })
}

// Hapus hanya isi di dalam SESSION_DIR agar Docker bind-mount /app/session tidak memicu EBUSY.
function hapusIsiSession() {
    pastikanSessionDir()
    for (const entry of fs.readdirSync(SESSION_DIR)) {
        try {
            fs.rmSync(path.join(SESSION_DIR, entry), { recursive: true, force: true })
        } catch (e) {
            console.error(`[wa] gagal hapus ${entry}:`, e?.message)
        }
    }
}

function jadwalkanReconnect(delayMs = reconnectDelayMs) {
    if (reconnectTimer) return
    reconnectTimer = setTimeout(() => {
        reconnectTimer = null
        start().catch((e) => {
            console.error('[wa] gagal start:', e?.message || e)
            reconnectDelayMs = Math.min(reconnectDelayMs * 2, 30000)
            jadwalkanReconnect(reconnectDelayMs)
        })
    }, delayMs)
}

function jid(nomor) {
    return String(nomor).includes('@') ? nomor : `${nomor}@s.whatsapp.net`
}

function json(res, status, body) {
    res.writeHead(status, { 'Content-Type': 'application/json' })
    res.end(JSON.stringify(body))
}

function bacaBody(req, maxBytes = 22 * 1024 * 1024) {
    return new Promise((resolve, reject) => {
        const chunks = []
        let totalBytes = 0
        let aborted = false
        req.on('data', (chunk) => {
            if (aborted) return
            totalBytes += chunk.length
            if (totalBytes > maxBytes) {
                aborted = true
                const err = new Error('Payload terlalu besar')
                err.statusCode = 413
                req.destroy()
                reject(err)
                return
            }
            chunks.push(chunk)
        })
        req.on('end', () => {
            if (aborted) return
            try {
                const raw = Buffer.concat(chunks).toString('utf8')
                resolve(raw ? JSON.parse(raw) : {})
            } catch {
                const err = new Error('JSON tidak valid')
                err.statusCode = 400
                reject(err)
            }
        })
        req.on('error', (err) => {
            if (!aborted) reject(err)
        })
    })
}

async function start() {
    if (starting) return
    starting = true
    try {
        pastikanSessionDir()
        const { state, saveCreds } = await useMultiFileAuthState(SESSION_DIR)
        const { version } = await fetchLatestBaileysVersion()

        const s = makeWASocket({ version, auth: state })
        sock = s

        s.ev.on('creds.update', saveCreds)

        s.ev.on('connection.update', ({ connection, lastDisconnect, qr }) => {
            if (sock !== s) return
            try {
                if (qr) latestQr = qr
                if (connection === 'open') {
                    connected = true
                    latestQr = null
                    reconnectDelayMs = 3000
                    console.log('[wa] terhubung')
                }
                if (connection === 'close') {
                    connected = false
                    const code = lastDisconnect?.error?.output?.statusCode
                    if (code === DisconnectReason.loggedOut) {
                        latestQr = null
                        hapusIsiSession()
                        console.log('[wa] logged out, isi sesi dihapus')
                    }
                    console.log(`[wa] terputus (${code}), reconnect ${reconnectDelayMs / 1000}s`)
                    jadwalkanReconnect(reconnectDelayMs)
                }
            } catch (e) {
                console.error('[wa] error connection.update:', e?.message || e)
                jadwalkanReconnect(reconnectDelayMs)
            }
        })
    } finally {
        starting = false
    }
}

start().catch((e) => {
    console.error('[wa] gagal start awal:', e?.message || e)
    jadwalkanReconnect(reconnectDelayMs)
})

process.on('unhandledRejection', (err) => {
    console.error('[wa] unhandledRejection:', err)
})

const server = http.createServer(async (req, res) => {
    const url = new URL(req.url, 'http://localhost')
    const auth = req.headers['x-token'] || String(req.headers['authorization'] || '').replace(/^Bearer\s+/i, '')

    // ponytail: timing-safe + panjang sama agar tak bocor via timing. Rate-limit penuh bila perlu gateway.
    const a = Buffer.from(String(auth))
    const b = Buffer.from(String(TOKEN))
    if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) {
        return json(res, 401, { error: 'unauthorized' })
    }

    try {
        if (req.method === 'GET' && url.pathname === '/status') {
            return json(res, 200, { connected, hasQR: !!latestQr })
        }

        if (req.method === 'GET' && url.pathname === '/qr') {
            if (connected) return json(res, 409, { error: 'sudah terhubung' })
            if (!latestQr) return json(res, 404, { error: 'QR belum tersedia' })
            return json(res, 200, { qr: await qrcode.toDataURL(latestQr) })
        }

        if (req.method === 'POST' && url.pathname === '/send') {
            const body = await bacaBody(req)
            if (!body.to || !body.message) return json(res, 422, { error: 'to & message wajib' })
            if (!connected || !sock) return json(res, 503, { error: 'WhatsApp belum terhubung' })
            antrean.push(() => sock.sendMessage(jid(body.to), { text: String(body.message) }))
            return json(res, 202, { ok: true, queued: true })
        }

        if (req.method === 'POST' && url.pathname === '/send-document') {
            const body = await bacaBody(req)
            if (!body.to || !body.filename || !body.base64) return json(res, 422, { error: 'to, filename & base64 wajib' })
            if (String(body.base64).length > 15_000_000) return json(res, 413, { error: 'dokumen terlalu besar' })
            if (!connected || !sock) return json(res, 503, { error: 'WhatsApp belum terhubung' })
            const pesan = {
                document: Buffer.from(String(body.base64), 'base64'),
                fileName: String(body.filename),
                mimetype: body.mimetype || 'application/pdf',
            }
            if (body.caption) pesan.caption = String(body.caption)
            antrean.push(() => sock.sendMessage(jid(body.to), pesan))
            return json(res, 202, { ok: true, queued: true })
        }

        if (req.method === 'POST' && url.pathname === '/logout') {
            const currentSock = sock
            sock = null
            connected = false
            latestQr = null
            try {
                await currentSock?.logout()
            } catch {}
            try {
                currentSock?.end()
            } catch {}
            hapusIsiSession()
            reconnectDelayMs = 3000
            jadwalkanReconnect(1500)
            return json(res, 200, { ok: true })
        }

        return json(res, 404, { error: 'not found' })
    } catch (e) {
        console.error('[http]', e.message)
        return json(res, e.statusCode || 502, { error: e.message })
    }
})

function shutdown(signal) {
    console.log(`[baileys-service] menerima ${signal}, menutup server...`)
    if (reconnectTimer) {
        clearTimeout(reconnectTimer)
        reconnectTimer = null
    }
    try {
        sock?.end()
    } catch {}
    server.close(() => process.exit(0))
    setTimeout(() => process.exit(0), 5000).unref()
}

process.on('SIGTERM', () => shutdown('SIGTERM'))
process.on('SIGINT', () => shutdown('SIGINT'))

server.listen(PORT, () => console.log(`[baileys-service] listening on :${PORT}`))
