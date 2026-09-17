import { Printer, Wallet, CalendarDays } from 'lucide-react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import StatusBadge from '@/Components/ui/StatusBadge';
import { formatRupiah } from '@/Utils/formatCurrency';

const angsuranStatusBadgeMap = {
    belum_bayar: 'pending',
    lunas: 'lunas',
    digantikan: 'pending',
};

function Seksi({ judul, aksi, children }) {
    return (
        <section className="rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm">
            <div className="flex items-center justify-between gap-2 mb-3">
                <h3 className="text-sm font-bold text-slate-800">{judul}</h3>
                {aksi}
            </div>
            {children}
        </section>
    );
}

export default function DetailDrawer({ pinjaman, angsuran, pelunasan_resign, jurnal_pelunasan }) {
    const isResign = pinjaman.anggota_status === 'resign';
    const dilunasiViaResign = pelunasan_resign.total > 0;
    const lunasCount = angsuran.filter((a) => a.status === 'lunas').length;

    function bukaCetak() {
        window.open(route('pinjaman.cetak-bukti', pinjaman.id), '_blank');
    }

    return (
        <div className="space-y-3">
            <div className="flex items-center gap-3 rounded-2xl bg-gradient-to-r from-brand-navy to-brand-navy-light text-white px-4 py-3 shadow-md shadow-brand-navy/20">
                <div className="w-11 h-11 rounded-full bg-white/15 flex items-center justify-center text-base font-bold shrink-0" aria-hidden="true">
                    {pinjaman.nama.charAt(0).toUpperCase()}
                </div>
                <div className="min-w-0 flex-1">
                    <p className="text-sm font-bold truncate">Pinjaman #{pinjaman.id} — {pinjaman.nama}</p>
                    <p className="text-xs text-white/70 truncate">{pinjaman.no_karyawan}{pinjaman.cabang ? ` • ${pinjaman.cabang}` : ''}</p>
                </div>
                <StatusBadge status={pinjaman.status} />
            </div>

            {isResign && (
                <p className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-full bg-rose-50 text-rose-700 w-fit">
                    Anggota Resign
                </p>
            )}

            <Seksi
                judul="Ringkasan"
                aksi={pinjaman.status === 'aktif' && (
                    <Button type="button" size="sm" onClick={bukaCetak} className="rounded-full shadow-md shadow-brand-green/25">
                        <Printer size={15} />
                        Cetak Bukti
                    </Button>
                )}
            >
                <dl className="grid grid-cols-3 gap-2 text-center">
                    <div className="rounded-xl bg-slate-50/80 border border-slate-100 px-2 py-2.5">
                        <dt className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Nominal</dt>
                        <dd className="text-sm font-bold text-slate-800 tabular-nums mt-0.5">{formatRupiah(pinjaman.nominal)}</dd>
                    </div>
                    <div className="rounded-xl bg-slate-50/80 border border-slate-100 px-2 py-2.5">
                        <dt className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tenor</dt>
                        <dd className="text-sm font-bold text-slate-800 tabular-nums mt-0.5">{pinjaman.tenor_bulan} bln</dd>
                    </div>
                    <div className="rounded-xl bg-slate-50/80 border border-slate-100 px-2 py-2.5">
                        <dt className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Pengajuan</dt>
                        <dd className="text-xs font-bold text-slate-800 mt-1">{pinjaman.tanggal_pengajuan}</dd>
                    </div>
                </dl>
                {pinjaman.keperluan && (
                    <p className="text-xs text-slate-500 mt-2.5 leading-relaxed">Keperluan: {pinjaman.keperluan}</p>
                )}
                {pinjaman.tanggal_cair && (
                    <p className="text-xs text-slate-400 mt-1 inline-flex items-center gap-1">
                        <CalendarDays size={13} />
                        Cair: {pinjaman.tanggal_cair}
                    </p>
                )}
            </Seksi>

            {dilunasiViaResign && (
                <Seksi judul="Pelunasan via Resign">
                    <div className="flex items-start gap-2.5">
                        <span className="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 inline-flex items-center justify-center shrink-0">
                            <Wallet size={17} />
                        </span>
                        <div className="flex-1 grid grid-cols-2 gap-2 text-sm">
                            <div className="rounded-xl bg-slate-50 px-3 py-2">
                                <p className="text-[11px] text-slate-400">Total Dilunasi</p>
                                <p className="font-bold text-slate-800 tabular-nums">{formatRupiah(pelunasan_resign.total)}</p>
                            </div>
                            {pelunasan_resign.tanggal && (
                                <div className="rounded-xl bg-slate-50 px-3 py-2">
                                    <p className="text-[11px] text-slate-400">Tanggal</p>
                                    <p className="font-bold text-slate-800">{pelunasan_resign.tanggal}</p>
                                </div>
                            )}
                        </div>
                    </div>
                </Seksi>
            )}

            <Seksi judul={`Jadwal Angsuran${angsuran.length ? ` (${lunasCount}/${angsuran.length})` : ''}`}>
                {angsuran.length === 0 ? (
                    <p className="py-6 text-center text-sm text-slate-400">Belum ada jadwal angsuran.</p>
                ) : (
                    <ul className="divide-y divide-slate-50 -mx-1">
                        {angsuran.map((a) => (
                            <li key={a.id} className="flex items-center justify-between gap-3 px-1 py-2.5">
                                <div className="flex items-center gap-2.5 min-w-0">
                                    <span className="w-8 h-8 rounded-full bg-slate-100 text-slate-600 inline-flex items-center justify-center text-xs font-bold shrink-0 tabular-nums">
                                        {a.cicilan_ke}
                                    </span>
                                    <div className="min-w-0">
                                        <StatusBadge status={angsuranStatusBadgeMap[a.status] ?? 'pending'} />
                                        <p className="text-[11px] text-slate-400 mt-0.5 truncate">
                                            Tempo {a.tanggal_jatuh_tempo ?? '-'}{a.tanggal_konfirmasi_bayar ? ` • Bayar ${a.tanggal_konfirmasi_bayar}` : ''}
                                        </p>
                                    </div>
                                </div>
                                <p className="text-sm font-bold text-slate-800 tabular-nums whitespace-nowrap">{formatRupiah(a.total_bayar)}</p>
                            </li>
                        ))}
                    </ul>
                )}
            </Seksi>

            {jurnal_pelunasan.length > 0 && (
                <Seksi judul="Riwayat Pelunasan Resign">
                    <ul className="divide-y divide-slate-50 -mx-1">
                        {jurnal_pelunasan.map((j) => (
                            <li key={j.id} className="flex items-center justify-between gap-3 px-1 py-2.5">
                                <div className="min-w-0">
                                    <p className="text-sm font-semibold text-slate-700 truncate">{j.keterangan}</p>
                                    <p className="text-[11px] text-slate-400 mt-0.5">{j.sub_judul ? `${j.sub_judul} • ` : ''}{j.tanggal}</p>
                                </div>
                                <p className="text-sm font-bold text-rose-600 tabular-nums whitespace-nowrap">-{formatRupiah(j.jumlah)}</p>
                            </li>
                        ))}
                    </ul>
                </Seksi>
            )}
        </div>
    );
}
