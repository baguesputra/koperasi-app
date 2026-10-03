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

export default function SectionOrganisasi({ ringkasanMaster, gateStatus }) {
    const sinkron = useForm({});
    const pratinjau = useForm({ dry_run: true });

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
                    <p className="text-sm text-slate-400">Master organisasi ditarik dulu, lalu karyawan menjadi user + anggota</p>
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

            <div className="space-y-3">
                <div className="flex flex-col sm:flex-row sm:items-center gap-3 rounded-xl border border-slate-100 p-4">
                    <div className="min-w-0 flex-1">
                        <p className="text-base font-bold text-slate-800">1. Sinkron Master</p>
                        <p className="text-sm text-slate-400">
                            Perusahaan, departemen, divisi, jabatan{gateStatus?.masterTerakhir ? ` — terakhir ${gateStatus.masterTerakhir}` : ''}
                        </p>
                    </div>
                    <div className="flex gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={pratinjau.processing}
                        onClick={() => pratinjau.post(route('pengaturan.sinkron-master-gate'))}
                    >
                        Pratinjau
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={sinkron.processing}
                        onClick={() => sinkron.post(route('pengaturan.sinkron-master-gate'))}
                    >
                        <RefreshCw size={16} className={sinkron.processing ? 'animate-spin' : ''} />
                        {sinkron.processing ? 'Menyinkron...' : 'Sinkron Master'}
                    </Button>
                    </div>
                </div>

                <div className="flex flex-col sm:flex-row sm:items-center gap-3 rounded-xl border border-slate-100 p-4">
                    <div className="min-w-0 flex-1">
                        <p className="text-base font-bold text-slate-800">2. Sinkron Karyawan</p>
                        <p className="text-sm text-slate-400">
                            User + anggota otomatis (NIK sebagai no karyawan)
                            {gateStatus?.karyawanTerakhir ? ` — terakhir ${gateStatus.karyawanTerakhir}` : ''}
                            {(ringkasanMaster?.tanpaPerusahaan ?? 0) > 0 && ` — ${ringkasanMaster.tanpaPerusahaan} tanpa perusahaan`}
                        </p>
                    </div>
                    <div className="flex gap-2">
                    <Button
                        variant="primary"
                        size="sm"
                        disabled={pratinjau.processing}
                        onClick={() => pratinjau.post(route('pengaturan.sinkron-gate'))}
                    >
                        Pratinjau
                    </Button>
                    <Button
                        variant="primary"
                        size="sm"
                        disabled={sinkron.processing}
                        onClick={() => sinkron.post(route('pengaturan.sinkron-gate'))}
                    >
                        <RefreshCw size={16} className={sinkron.processing ? 'animate-spin' : ''} />
                        {sinkron.processing ? 'Menyinkron...' : 'Sinkron Karyawan'}
                    </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}
