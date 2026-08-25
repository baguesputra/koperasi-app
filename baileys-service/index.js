// ponytail: single-session WA gateway; multi-device/queueing add when needed
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
const TOKEN = process.env.TOKEN || 'change-me-in-production'
const SESSION_DIR = path.join(process.cwd(), 'session')

let sock = null
let latestQr = null
let connected = false

function json(res, status, body) {
    res.writeHead(status, { 'Content-Type': 'application/json' })
    res.end(JSON.stringify(body))
}

function bacaBody(req) {
    return new Promise((resolve, reject) => {
        let data = ''
        req.on('data', (c) => (data += c))
        req.on('end', () => {
            try {
                resolve(data ? JSON.parse(data) : {})
            } catch {
                reject(new Error('JSON tidak valid'))
            }
        })
        req.on('error', reject)
    })
}

async function start() {
    const { state, saveCreds } = await useMultiFileAuthState(SESSION_DIR)
    const { version } = await fetchLatestBaileysVersion()

    sock = makeWASocket({ version, auth: state })

    sock.ev.on('creds.update', saveCreds)

    sock.ev.on('connection.update', ({ connection, lastDisconnect, qr }) => {
        if (qr) latestQr = qr
        if (connection === 'open') {
            connected = true
            latestQr = null
            console.log('[wa] terhubung')
        }
        if (connection === 'close') {
            connected = false
            const code = lastDisconnect?.error?.output?.statusCode
            if (code === DisconnectReason.loggedOut) {
                fs.rmSync(SESSION_DIR, { recursive: true, force: true })
                console.log('[wa] logged out, sesi dihapus')
            }
            console.log(`[wa] terputus (${code}), reconnect 3s`)
            setTimeout(() => start().catch((e) => console.error('[wa]', e)), 3000)
        }
    })
}

start().catch((e) => console.error('[wa] gagal start:', e))

const server = http.createServer(async (req, res) => {
    const url = new URL(req.url, 'http://localhost')
    const auth = req.headers['x-token'] || String(req.headers['authorization'] || '').replace(/^Bearer\s+/i, '')

    if (auth !== TOKEN) {
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
            const jid = String(body.to).includes('@') ? body.to : `${body.to}@s.whatsapp.net`
            await sock.sendMessage(jid, { text: body.message })
            return json(res, 200, { ok: true })
        }

        if (req.method === 'POST' && url.pathname === '/logout') {
            try {
                await sock?.logout()
            } catch {}
            fs.rmSync(SESSION_DIR, { recursive: true, force: true })
            connected = false
            latestQr = null
            try {
                sock?.end()
            } catch {}
            return json(res, 200, { ok: true })
        }

        return json(res, 404, { error: 'not found' })
    } catch (e) {
        console.error('[http]', e.message)
        return json(res, 502, { error: e.message })
    }
})

server.listen(PORT, () => console.log(`[baileys-service] listening on :${PORT}`))
