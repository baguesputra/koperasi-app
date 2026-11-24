import { usePage } from '@inertiajs/react';
import { Printer, Wallet, CalendarDays, Layers, Receipt, FileText, ExternalLink } from 'lucide-react';
import {
    DrawerField,
    DrawerFieldGrid,
    DrawerHero,
    DrawerSection,
} from '@/Components/ui/DrawerSection';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import Button from '@/Components/ui/Button';
import StatusBadge from '@/Components/ui/StatusBadge';
import { formatRupiah } from '@/Utils/formatCurrency';

const angsuranStatusBadgeMap = {
    belum_bayar: 'pending',
    lunas: 'lunas',
    digantikan: 'pending',
};

export default function DetailDrawer({ pinjaman, angsuran, pelunasan_resign, jurnal_pelunasan }) {
    const { auth } = usePage().props;
    const roles = auth?.user?.roles ?? [];
    const bisaCetakBukti = !(roles.includes('pengawas') && !roles.some((r) => ['super_admin', 'sekretaris', 'bendahara', 'ketua_koperasi'].includes(r)));
    const isResign = pinjaman.anggota_status === 'resign';
    const dilunasiViaResign = pelunasan_resign.total > 0;
    const lunasCount = angsuran.filter((a) => a.status === 'lunas').length;
    const persenLunas = angsuran.length > 0 ? Math.round((lunasCount / angsuran.length) * 100) : 0;

    function bukaCetak() {
        window.open(route('pinjaman.cetak-bukti', pinjaman.id), '_blank');
    }

    return (
        <div className="space-y-3.5">
            <DrawerHero>
                <div className="flex items-center gap-3">
                    <FotoAnggota nama={pinjaman.nama} fotoUrl={pinjaman.foto_url} ukuran="md" className="bg-white/15 ring-white/20 shrink-0" />
                    <div className="min-w-0 flex-1">
                        <p className="text-sm sm:text-base font-bold text-white truncate">Pinjaman #{pinjaman.id} — {pinjaman.nama}</p>
                        <p className="text-xs text-slate-300 truncate mt-0.5">
                            <span className="font-mono">{pinjaman.no_karyawan}</span>
                            {pinjaman.cabang ? ` • ${pinjaman.cabang}` : ''}
                        </p>
                    </div>
                    <StatusBadge status={pinjaman.status} />
                </div>
            </DrawerHero>

            {isResign && (
                <div className="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-full bg-rose-50 text-rose-700 border border-rose-200/80 w-fit">
                    Anggota Resign
                </div>
            )}

            <DrawerSection
                title="Ringkasan Pinjaman"
                icon={Wallet}
                padding="none"
                action={
                    <div className="flex items-center gap-2">
                        {pinjaman.bukti_pencairan_url && (
                            <a
                                href={pinjaman.bukti_pencairan_url}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-slate-200 bg-white text-xs font-semibold text-brand-navy hover:bg-slate-50 shadow-2xs"
                            >
                                <FileText size={13} aria-hidden="true" />
                                Bukti Transfer
                                <ExternalLink size={11} aria-hidden="true" />
                            </a>
                        )}
                        {bisaCetakBukti && pinjaman.status === 'aktif' && (
                            <Button type="button" size="sm" onClick={bukaCetak} className="rounded-full shadow-sm">
                                <Printer size={14} aria-hidden="true" />
                                Cetak Bukti
                            </Button>
                        )}
                    </div>
                }
            >
                <dl className="grid grid-cols-3 divide-x divide-slate-100 text-center">
                    <div className="px-3 py-3">
                        <dt className="text-[11px] font-medium text-slate-500">Nominal</dt>
                        <dd className="text-sm sm:text-base font-bold text-slate-900 tabular-nums mt-0.5 privacy-blur">{formatRupiah(pinjaman.nominal)}</dd>
                    </div>
                    <div className="px-3 py-3">
                        <dt className="text-[11px] font-medium text-slate-500">Tenor</dt>
                        <dd className="text-sm sm:text-base font-bold text-slate-800 tabular-nums mt-0.5">{pinjaman.tenor_bulan} bln</dd>
                    </div>
                    <div className="px-3 py-3">
                        <dt className="text-[11px] font-medium text-slate-500">Pengajuan</dt>
                        <dd className="text-xs sm:text-sm font-bold text-slate-800 mt-1">{pinjaman.tanggal_pengajuan}</dd>
                    </div>
                </dl>

                {(pinjaman.keperluan || pinjaman.tanggal_cair) && (
                    <div className="bg-[#F8FAFC] border-t border-slate-100 px-4 py-2.5 space-y-1">
                        {pinjaman.keperluan && (
                            <p className="text-xs text-slate-600 leading-relaxed">
                                <span className="font-semibold text-slate-700">Keperluan:</span> {pinjaman.keperluan}
                            </p>
                        )}
                        {pinjaman.tanggal_cair && (
                            <p className="text-xs text-slate-500 inline-flex items-center gap-1.5">
                                <CalendarDays size={13} aria-hidden="true" />
                                Cair: {pinjaman.tanggal_cair}
                            </p>
                        )}
                    </div>
                )}
            </DrawerSection>

            {dilunasiViaResign && (
                <DrawerSection title="Pelunasan via Resign" icon={Wallet} tone="rose">
                    <DrawerFieldGrid cols={2}>
                        <DrawerField label="Total Dilunasi" value={formatRupiah(pelunasan_resign.total)} privacy />
                        {pelunasan_resign.tanggal && (
                            <DrawerField label="Tanggal" value={pelunasan_resign.tanggal} />
                        )}
                    </DrawerFieldGrid>
                </DrawerSection>
            )}

            <DrawerSection
                title="Jadwal Angsuran"
                icon={Layers}
                padding="none"
                action={angsuran.length > 0 && (
                    <span className="text-xs font-semibold text-slate-600 tabular-nums">
                        {lunasCount}/{angsuran.length} lunas ({persenLunas}%)
                    </span>
                )}
            >
                {angsuran.length > 0 && (
                    <div className="px-4 pt-3 pb-2 border-b border-slate-100">
                        <div className="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                            <div
                                className="h-full rounded-full bg-gradient-to-r from-brand-navy to-brand-green transition-all duration-300"
                                style={{ width: `${persenLunas}%` }}
                            />
                        </div>
                    </div>
                )}

                {angsuran.length === 0 ? (
                    <p className="py-8 text-center text-sm text-slate-500">Belum ada jadwal angsuran.</p>
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {angsuran.map((a) => (
                            <li key={a.id} className="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-green-light/25 transition-colors">
                                <div className="flex items-center gap-3 min-w-0">
                                    <span className="w-7 h-7 rounded-full bg-gradient-to-br from-brand-navy/10 to-sky-100/80 text-brand-navy inline-flex items-center justify-center text-xs font-bold shrink-0 tabular-nums">
                                        {a.cicilan_ke}
                                    </span>
                                    <div className="min-w-0">
                                        <StatusBadge status={angsuranStatusBadgeMap[a.status] ?? 'pending'} />
                                        <p className="text-[11px] text-slate-500 mt-0.5 truncate">
                                            Tempo {a.tanggal_jatuh_tempo ?? '-'}{a.tanggal_konfirmasi_bayar ? ` • Bayar ${a.tanggal_konfirmasi_bayar}` : ''}
                                        </p>
                                    </div>
                                </div>
                                <p className="text-sm font-bold text-slate-800 tabular-nums whitespace-nowrap privacy-blur">{formatRupiah(a.total_bayar)}</p>
                            </li>
                        ))}
                    </ul>
                )}
            </DrawerSection>

            {jurnal_pelunasan.length > 0 && (
                <DrawerSection title="Riwayat Pelunasan Resign" icon={Receipt} padding="none">
                    <ul className="divide-y divide-slate-100">
                        {jurnal_pelunasan.map((j) => (
                            <li key={j.id} className="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-green-light/20 transition-colors">
                                <div className="min-w-0">
                                    <p className="text-sm font-semibold text-slate-800 truncate">{j.keterangan}</p>
                                    <p className="text-[11px] text-slate-500 mt-0.5">{j.sub_judul ? `${j.sub_judul} • ` : ''}{j.tanggal}</p>
                                </div>
                                <p className="text-sm font-bold text-rose-600 tabular-nums whitespace-nowrap privacy-blur">-{formatRupiah(j.jumlah)}</p>
                            </li>
                        ))}
                    </ul>
                </DrawerSection>
            )}
        </div>
    );
}
