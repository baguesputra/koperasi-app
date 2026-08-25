import { useEffect, useState } from 'react';
import { RefreshCw, LogOut, QrCode, Loader2 } from 'lucide-react';
import Button from '@/Components/ui/Button';

const STATUS_LOG = {
    terkirim: 'bg-brand-green-light text-brand-green-dark',
    gagal: 'bg-red-100 text-red-700',
    dilewati: 'bg-amber-100 text-amber-700',
};

export default function TabWa() {
    const [data, setData] = useState(null);
    const [keluar, setKeluar] = useState(false);

    async function muat() {
        try {
            const res = await fetch(route('pengaturan.wa.data'));
            if (res.ok) setData(await res.json());
        } catch {}
    }

    useEffect(() => {
        muat();
        const t = setInterval(muat, 4000);
        return () => clearInterval(t);
    }, []);

    async function logout() {
        setKeluar(true);
        try {
            await fetch(route('pengaturan.wa.logout'), {
                method: 'POST',
                headers: { 'X-XSRF-TOKEN': decodeURIComponent(
                    (document.cookie.match(/XSRF-TOKEN=([^;]*)/) || [])[1] || ''
                ) },
            });
        } catch {}
        setKeluar(false);
        muat();
    }

    if (!data) {
        return <p className="text-sm text-slate-400">Memuat status WhatsApp...</p>;
    }

    return (
        <div>
            <p className="text-sm text-slate-400 mb-4">
                Notifikasi WhatsApp dikirim otomatis saat ada pengajuan pinjaman, perubahan tenor, pengajuan limit,
                dan keputusan persetujuan. Hubungkan perangkat dengan memindai QR sekali saja.
            </p>

            {/* Status & QR */}
            <div className="flex flex-col sm:flex-row items-start gap-5 mb-6">
                <div className="w-full sm:w-auto sm:min-w-[220px] rounded-xl border border-slate-100 p-4 flex flex-col items-center justify-center gap-3">
                    {data.terhubung ? (
                        <>
                            <span className="flex items-center gap-2 text-base font-bold text-slate-800">
                                <span className="w-2.5 h-2.5 rounded-full bg-green-500" />
                                Terhubung
                            </span>
                            <Button variant="danger" size="sm" onClick={logout} disabled={keluar}>
                                {keluar ? <Loader2 size={16} className="animate-spin" /> : <LogOut size={16} />}
                                Keluarkan Perangkat
                            </Button>
                        </>
                    ) : data.qr ? (
                        <>
                            <img src={data.qr} alt="QR WhatsApp" className="w-44 h-44" />
                            <p className="text-xs text-slate-500 text-center">
                                Buka WhatsApp &gt; Perangkat Tertaut &gt; Pindai QR
                            </p>
                        </>
                    ) : (
                        <>
                            <QrCode size={40} className="text-slate-300" />
                            <p className="text-sm text-slate-400 text-center">
                                {data.layanan
                                    ? 'Menunggu QR tersedia...'
                                    : 'Layanan WhatsApp tidak dapat dihubungi. Pastikan container baileys berjalan.'}
                            </p>
                        </>
                    )}
                </div>

                <div className="text-sm text-slate-500 space-y-1.5">
                    <p className="font-bold text-slate-700">Status: {data.terhubung ? 'Terhubung' : 'Tidak terhubung'}</p>
                    <p>Halaman ini diperbarui otomatis setiap 4 detik.</p>
                    <button
                        type="button"
                        onClick={muat}
                        className="inline-flex items-center gap-1.5 text-brand-green hover:text-brand-green-dark font-semibold"
                    >
                        <RefreshCw size={14} /> Muat sekarang
                    </button>
                </div>
            </div>

            {/* Log pengiriman */}
            <p className="text-sm font-bold uppercase tracking-wider text-slate-400 mb-3">Log Pengiriman</p>
            {data.logs.length === 0 ? (
                <p className="text-sm text-slate-400">Belum ada notifikasi terkirim.</p>
            ) : (
                <div className="overflow-x-auto border border-slate-100 rounded-xl">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-slate-50 text-left text-slate-500">
                                <th className="px-3 py-2 font-semibold">Waktu</th>
                                <th className="px-3 py-2 font-semibold">Tujuan</th>
                                <th className="px-3 py-2 font-semibold">Event</th>
                                <th className="px-3 py-2 font-semibold">Pesan</th>
                                <th className="px-3 py-2 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {data.logs.map((log) => (
                                <tr key={log.id} className="align-top">
                                    <td className="px-3 py-2 whitespace-nowrap text-slate-600">{log.waktu}</td>
                                    <td className="px-3 py-2 whitespace-nowrap text-slate-600">{log.penerima}</td>
                                    <td className="px-3 py-2 whitespace-nowrap text-slate-600">{log.event}</td>
                                    <td className="px-3 py-2 max-w-md">
                                        <span title={log.pesan} className="line-clamp-2 text-slate-600">{log.pesan}</span>
                                        {log.error && <span className="block text-xs text-red-500">{log.error}</span>}
                                    </td>
                                    <td className="px-3 py-2">
                                        <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-bold capitalize ${STATUS_LOG[log.status] || 'bg-slate-100 text-slate-600'}`}>
                                            {log.status}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
