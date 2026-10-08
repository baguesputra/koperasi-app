import { useForm } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import Button from '@/Components/ui/Button';

function BadgeStatus({ terhubung, adaToken }) {
    if (!adaToken) {
        return <span className="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">Token belum diisi</span>;
    }
    return terhubung
        ? <span className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-brand-green-light text-brand-green-dark"><span className="w-2 h-2 rounded-full bg-green-500" />Terhubung</span>
        : <span className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700"><span className="w-2 h-2 rounded-full bg-amber-500" />Tidak terjangkau</span>;
}

function ringkas(h) {
    if (h.kind === 'master') return `${h.count_departemen} dept · ${h.count_divisi} divisi · ${h.count_jabatan} jabatan`;
    if (h.kind === 'karyawan') return `${h.count_baru} baru · ${h.count_diperbarui} update · ${h.count_nonaktif} nonaktif`;
    return `${h.count_baru} baru · ${h.count_diperbarui} update · ${h.count_departemen} dept`;
}

function sumber(h) {
    if (h.source === 'jadwal') return ' · otomatis';
    if (h.source === 'login') return ' · login';
    return '';
}

export default function SectionOrganisasi({ ringkasanMaster, gateStatus, gateSetting = {}, gateHistory = [] }) {
    const semua = useForm({ dry_run: false });
    const jadwal = useForm({
        schedule_enabled: gateSetting?.schedule_enabled ?? true,
        karyawan_interval_minutes: gateSetting?.karyawan_interval_minutes ?? 15,
        master_daily_at: (gateSetting?.master_daily_at ?? '02:00').slice(0, 5),
        jit_enabled: gateSetting?.jit_enabled ?? true,
        grace_miss_count: gateSetting?.grace_miss_count ?? 2,
    });

    const kirim = (dry) => {
        semua.setData('dry_run', dry);
        semua.post(route('pengaturan.sinkron-semua-gate'));
    };

    const statistik = [
        ['Perusahaan', ringkasanMaster?.perusahaan ?? 0],
        ['Departemen', ringkasanMaster?.departemen ?? 0],
        ['Divisi', ringkasanMaster?.divisi ?? 0],
        ['Jabatan', ringkasanMaster?.jabatan ?? 0],
    ];

    return (
        <div className="space-y-5">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p className="text-base font-bold text-slate-800">Integrasi GATE</p>
                    <p className="text-sm text-slate-400">Master + karyawan sekali jalan, lalu otomatis terjadwal</p>
                </div>
                <BadgeStatus terhubung={gateStatus?.terhubung} adaToken={gateStatus?.adaToken} />
            </div>

            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                {statistik.map(([label, nilai]) => (
                    <div key={label} className="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                        <p className="text-2xl font-bold text-slate-800">{nilai}</p>
                        <p className="text-sm text-slate-400">{label}</p>
                    </div>
                ))}
            </div>

            <div className="rounded-xl border border-slate-100 p-4">
                <div className="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div className="min-w-0 flex-1">
                        <p className="text-base font-bold text-slate-800">Sinkron Sekarang</p>
                        <p className="text-sm text-slate-400">
                            Master lalu karyawan sekaligus
                            {gateStatus?.karyawanTerakhir ? ` — terakhir ${gateStatus.karyawanTerakhir}` : ''}
                            {(ringkasanMaster?.tanpaPerusahaan ?? 0) > 0 && ` — ${ringkasanMaster.tanpaPerusahaan} tanpa perusahaan`}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={semua.processing}
                            onClick={() => kirim(true)}
                        >
                            Pratinjau
                        </Button>
                        <Button
                            variant="primary"
                            size="sm"
                            disabled={semua.processing}
                            onClick={() => kirim(false)}
                        >
                            <RefreshCw size={16} className={semua.processing ? 'animate-spin' : ''} />
                            {semua.processing ? 'Menyinkron...' : 'Sinkron Sekarang'}
                        </Button>
                    </div>
                </div>
                {semua.errors.company_id && <p className="text-xs text-red-600 mt-2">{semua.errors.company_id}</p>}
            </div>

            <div className="rounded-xl border border-slate-100 p-4">
                <div className="flex items-center justify-between">
                    <p className="text-base font-bold text-slate-800">Jadwal Otomatis</p>
                    <span className={`inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full ${jadwal.data.schedule_enabled ? 'bg-brand-green-light text-brand-green-dark' : 'bg-amber-100 text-amber-700'}`}>
                        {jadwal.data.schedule_enabled ? 'Aktif' : 'Mati'}
                    </span>
                </div>
                <div className="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <label className="block">
                        <span className="block text-xs text-slate-400 mb-1">Karyawan tiap (mnt)</span>
                        <input type="number" min="5" max="1440" value={jadwal.data.karyawan_interval_minutes} onChange={(e) => jadwal.setData('karyawan_interval_minutes', e.target.value)} className="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" aria-label="Interval karyawan" />
                    </label>
                    <label className="block">
                        <span className="block text-xs text-slate-400 mb-1">Master jam</span>
                        <input type="time" value={jadwal.data.master_daily_at} onChange={(e) => jadwal.setData('master_daily_at', e.target.value)} className="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" aria-label="Jam master" />
                    </label>
                    <label className="block">
                        <span className="block text-xs text-slate-400 mb-1">Grace hilang (x)</span>
                        <input type="number" min="1" max="10" value={jadwal.data.grace_miss_count} onChange={(e) => jadwal.setData('grace_miss_count', e.target.value)} className="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" aria-label="Grace resign" />
                    </label>
                    <div className="flex flex-col gap-1.5 justify-end">
                        <label className="flex items-center gap-1.5 text-xs text-slate-600">
                            <input type="checkbox" checked={!!jadwal.data.schedule_enabled} onChange={(e) => jadwal.setData('schedule_enabled', e.target.checked)} className="rounded" /> Jadwal
                        </label>
                        <label className="flex items-center gap-1.5 text-xs text-slate-600">
                            <input type="checkbox" checked={!!jadwal.data.jit_enabled} onChange={(e) => jadwal.setData('jit_enabled', e.target.checked)} className="rounded" /> Sync saat login
                        </label>
                    </div>
                </div>
                <div className="mt-3">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={jadwal.processing}
                        onClick={() => jadwal.post(route('pengaturan.jadwal-gate'))}
                    >
                        {jadwal.processing ? 'Menyimpan...' : 'Simpan Jadwal'}
                    </Button>
                </div>
                <p className="text-[11px] text-slate-400 mt-2">Jalan otomatis dari traffic web (tanpa cron). Sepi traffic = jadwal mundur sampai ada kunjungan.</p>
            </div>

            <div className="rounded-xl border border-slate-100 divide-y divide-slate-100">
                <p className="text-base font-bold text-slate-800 px-4 pt-4 pb-2">Riwayat Sinkron{gateHistory.length ? ` (${gateHistory.length})` : ''}</p>
                {gateHistory.length === 0 && <p className="px-4 py-8 text-center text-xs text-slate-400">Belum ada riwayat sinkron.</p>}
                {gateHistory.map((h) => (
                    <div key={h.id} className="px-4 py-3 flex items-center justify-between gap-3">
                        <div className="min-w-0">
                            <p className="text-xs font-semibold text-slate-800 capitalize">
                                {h.kind === 'semua' ? 'Master + Karyawan' : h.kind}{h.is_dry_run ? ' · pratinjau' : ''}{sumber(h)}
                            </p>
                            <p className="text-xs text-slate-500 mt-0.5 tabular-nums">{ringkas(h)}</p>
                            <p className="text-[11px] text-slate-400 mt-0.5">{h.user?.name ?? '—'} · {h.created_at ?? '—'}</p>
                        </div>
                        <span className={`inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full ${h.count_gagal > 0 ? 'bg-amber-100 text-amber-700' : 'bg-brand-green-light text-brand-green-dark'}`}>
                            {h.count_gagal > 0 ? `${h.count_gagal} gagal` : 'OK'}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}
