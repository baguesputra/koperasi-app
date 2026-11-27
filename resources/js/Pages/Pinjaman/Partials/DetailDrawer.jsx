import { Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowRight,
    Check,
    CheckCircle2,
    ChevronDown,
    Clock,
    Copy,
    ExternalLink,
    FileText,
    FileX2,
    Landmark,
    Printer,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';
import Button, { buttonBase, buttonSizes, buttonVariants } from '@/Components/ui/Button';
import {
    DrawerBody,
    DrawerFooter,
    DrawerHero,
} from '@/Components/ui/DrawerSection';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import StatusBadge from '@/Components/ui/StatusBadge';
import JejakNominal from '@/Pages/PinjamanApproval/JejakNominal';
import { formatRupiah } from '@/Utils/formatCurrency';

const STATUS_ANTREAN = ['diajukan', 'approved_admin', 'approved_bendahara', 'approved_ketua'];

const INFO_TAHAP_PRA_CAIR = {
    diajukan: {
        accent: 'border-l-amber-500',
        badge: 'bg-amber-50 text-amber-800 border-amber-200',
        tahap: 'Tahap 1 dari 4',
        judul: 'Menunggu Verifikasi Sekretaris',
        deskripsi: 'Berkas pengajuan telah masuk dan sedang menunggu pemeriksaan kelengkapan awal oleh Sekretaris.',
    },
    approved_admin: {
        accent: 'border-l-brand-navy',
        badge: 'bg-slate-100 text-brand-navy border-slate-200',
        tahap: 'Tahap 2 dari 4',
        judul: 'Menunggu Tinjauan Keuangan Bendahara',
        deskripsi: 'Sekretaris telah memverifikasi berkas. Bendahara meninjau kecukupan kas, nominal usulan, dan tenor.',
    },
    approved_bendahara: {
        accent: 'border-l-brand-navy',
        badge: 'bg-slate-100 text-brand-navy border-slate-200',
        tahap: 'Tahap 3 dari 4',
        judul: 'Menunggu Persetujuan Final Ketua Koperasi',
        deskripsi: 'Bendahara telah menyetujui usulan keuangan. Menunggu keputusan akhir dari Ketua Koperasi.',
    },
    approved_ketua: {
        accent: 'border-l-emerald-600',
        badge: 'bg-emerald-50 text-emerald-800 border-emerald-200',
        tahap: 'Tahap 4 dari 4',
        judul: 'Disetujui Ketua — Siap Dicairkan',
        deskripsi: 'Akad telah disetujui Ketua Koperasi. Bendahara dapat mentransfer dana ke rekening tujuan dan mengunggah bukti transfer.',
    },
};

function statusBadgeAngsuran(item) {
    if (item.status === 'lunas') {
        return <StatusBadge status="lunas" />;
    }
    if (item.status === 'dihapusbukukan') {
        return <StatusBadge status="dihapusbukukan" />;
    }
    if (item.is_terlambat) {
        return (
            <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full border text-xs font-semibold bg-rose-50 text-rose-700 border-rose-200">
                <AlertTriangle size={12} className="shrink-0" aria-hidden="true" />
                <span>Terlambat</span>
            </span>
        );
    }

    return <StatusBadge status="belum_bayar" />;
}

function PanelPutih({ children, className = '', accentClass = '' }) {
    return (
        <section
            className={`rounded-2xl bg-white border border-slate-200/85 shadow-[0_1px_3px_rgba(15,30,54,0.04)] overflow-hidden ${
                accentClass ? `border-l-4 ${accentClass}` : ''
            } ${className}`}
        >
            {children}
        </section>
    );
}

function StepNode({ status }) {
    if (status === 'rejected') {
        return (
            <span className="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600 ring-4 ring-white border border-rose-200">
                <XCircle size={15} aria-hidden="true" />
            </span>
        );
    }
    if (status === 'done') {
        return (
            <span className="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-brand-green-dark ring-4 ring-white border border-emerald-200">
                <CheckCircle2 size={15} aria-hidden="true" />
            </span>
        );
    }
    if (status === 'current') {
        return (
            <span className="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-700 ring-4 ring-white border border-amber-300">
                <Clock size={14} aria-hidden="true" />
            </span>
        );
    }

    return (
        <span className="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-50 text-slate-400 ring-4 ring-white border border-slate-200">
            <span className="h-2 w-2 rounded-full bg-slate-300" />
        </span>
    );
}

function BarisInformasi({ label, children }) {
    return (
        <div className="py-3.5 sm:grid sm:grid-cols-3 sm:gap-6 first:pt-0 last:pb-0">
            <dt className="text-xs font-medium text-slate-500">{label}</dt>
            <dd className="mt-1 sm:mt-0 sm:col-span-2 text-sm text-slate-900 min-w-0">
                {children}
            </dd>
        </div>
    );
}

function catatanUntukTahap(labelTahap, { catatanSekretaris, catatanBendahara, catatanKetua, catatanPencairan, infoPenolakan }) {
    const label = (labelTahap || '').toLowerCase();

    if (infoPenolakan?.alasan && infoPenolakan.tahap && label.includes(infoPenolakan.tahap.toLowerCase())) {
        return { teks: infoPenolakan.alasan, isTolak: true };
    }
    if (label.includes('sekretaris') && catatanSekretaris) {
        return { teks: catatanSekretaris, isTolak: false };
    }
    if (label.includes('bendahara') && !label.includes('pencairan') && catatanBendahara) {
        return { teks: catatanBendahara, isTolak: false };
    }
    if (label.includes('ketua') && catatanKetua) {
        return { teks: catatanKetua, isTolak: false };
    }
    if (label.includes('pencairan') && catatanPencairan) {
        return { teks: catatanPencairan, isTolak: false };
    }

    return null;
}

function VerticalStepper({ steps = [], notesContext = null }) {
    if (!steps || steps.length === 0) return null;

    return (
        <ol className="divide-y-0">
            {steps.map((step, index) => {
                const isLast = index === steps.length - 1;
                const catatanStep = notesContext ? catatanUntukTahap(step.label, notesContext) : null;
                const catatanLangsung = step.catatan ? { teks: step.catatan, isTolak: false } : null;
                const catatan = catatanLangsung ?? catatanStep;

                return (
                    <li key={`${step.tahap ?? step.label}-${index}`} className={`relative flex gap-4 ${isLast ? '' : 'pb-6'}`}>
                        {!isLast && (
                            <span
                                className="absolute left-[13px] top-7 bottom-0 w-px bg-slate-200"
                                aria-hidden="true"
                            />
                        )}
                        <StepNode status={step.status ?? 'done'} />
                        <div className="flex-1 min-w-0 pt-0.5">
                            <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                                <p className="text-sm font-semibold text-slate-900">{step.label}</p>
                                {step.tanggal || step.date ? (
                                    <span className="text-xs text-slate-400 tabular-nums shrink-0">
                                        {step.tanggal ?? step.date}
                                    </span>
                                ) : null}
                            </div>
                            <p className="text-xs text-slate-500 mt-0.5">{step.user}</p>
                            {catatan && (
                                <blockquote
                                    className={`mt-2.5 rounded-xl px-3.5 py-2.5 text-xs leading-relaxed border ${
                                        catatan.isTolak
                                            ? 'bg-rose-50/70 border-rose-200 text-rose-900'
                                            : 'bg-slate-50 border-slate-200/80 text-slate-700'
                                    }`}
                                >
                                    &ldquo;{catatan.teks}&rdquo;
                                </blockquote>
                            )}
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}

function TabelJadwalEditorial({
    rows = [],
    isSimulasi = false,
    digantikan = [],
    tampilHistoriLama = false,
    onToggleHistoriLama,
}) {
    if (rows.length === 0) {
        return (
            <div className="px-6 py-12 text-center text-sm text-slate-500">
                Belum ada jadwal angsuran untuk pinjaman ini.
            </div>
        );
    }

    return (
        <>
            <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                    <thead>
                        <tr className="border-b border-slate-200 bg-[#F8FAFC] text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                            <th className="py-3 pl-5 pr-3 whitespace-nowrap">Ke</th>
                            {!isSimulasi && <th className="py-3 px-3 whitespace-nowrap">Jatuh Tempo &amp; Pembayaran</th>}
                            <th className="py-3 px-3 whitespace-nowrap">Rincian Pokok &amp; Bunga</th>
                            <th className="py-3 px-3 text-right whitespace-nowrap">Total Tagihan</th>
                            {!isSimulasi && <th className="py-3 pl-3 pr-5 text-right whitespace-nowrap">Status</th>}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 text-sm">
                        {rows.map((row) => (
                            <tr
                                key={row.id ?? row.cicilan_ke}
                                className={`transition-colors ${
                                    row.is_terlambat ? 'bg-rose-50/35 hover:bg-rose-50/55' : 'hover:bg-slate-50/80'
                                }`}
                            >
                                <td className="py-3.5 pl-5 pr-3 align-top whitespace-nowrap">
                                    <div className="flex items-center gap-2">
                                        <span className="font-bold text-slate-900 tabular-nums">#{row.cicilan_ke}</span>
                                        {row.is_percepatan && (
                                            <span className="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-brand-navy border border-slate-200">
                                                Tenor Baru
                                            </span>
                                        )}
                                    </div>
                                </td>

                                {!isSimulasi && (
                                    <td className="py-3.5 px-3 align-top">
                                        <p className="font-medium text-slate-800 whitespace-nowrap">
                                            {row.tanggal_jatuh_tempo ?? '-'}
                                        </p>
                                        {row.tanggal_konfirmasi_bayar && (
                                            <p className="text-xs text-slate-500 mt-0.5">
                                                Dibayar {row.tanggal_konfirmasi_bayar}
                                                {row.confirmed_by_nama ? ` • ${row.confirmed_by_nama}` : ''}
                                            </p>
                                        )}
                                    </td>
                                )}

                                <td className="py-3.5 px-3 align-top text-xs text-slate-600 tabular-nums privacy-blur">
                                    <span>Pokok {formatRupiah(row.nominal_pokok ?? 0)}</span>
                                    <span className="text-slate-300 mx-1.5">+</span>
                                    <span>Bunga {formatRupiah(row.nominal_bunga ?? 0)}</span>
                                </td>

                                <td className="py-3.5 px-3 align-top text-right font-bold text-slate-900 tabular-nums whitespace-nowrap privacy-blur">
                                    {formatRupiah(row.total_bayar)}
                                </td>

                                {!isSimulasi && (
                                    <td className="py-3.5 pl-3 pr-5 align-top text-right whitespace-nowrap">
                                        {statusBadgeAngsuran(row)}
                                    </td>
                                )}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {digantikan.length > 0 && (
                <div className="border-t border-slate-200/80 bg-[#F8FAFC] px-5 py-3.5">
                    <button
                        type="button"
                        onClick={onToggleHistoriLama}
                        className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-brand-navy cursor-pointer"
                    >
                        <ChevronDown size={14} className={`transition-transform ${tampilHistoriLama ? 'rotate-180' : ''}`} />
                        {tampilHistoriLama ? 'Sembunyikan' : 'Lihat'} {digantikan.length} cicilan lama yang digantikan perubahan tenor
                    </button>
                    {tampilHistoriLama && (
                        <div className="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <table className="w-full text-left border-collapse text-xs">
                                <tbody className="divide-y divide-slate-100">
                                    {digantikan.map((d) => (
                                        <tr key={`old-${d.id}`} className="text-slate-500">
                                            <td className="px-4 py-2.5 font-medium">Cicilan #{d.cicilan_ke}</td>
                                            <td className="px-3 py-2.5">Tempo {d.tanggal_jatuh_tempo ?? '-'}</td>
                                            <td className="px-4 py-2.5 text-right tabular-nums line-through privacy-blur">
                                                {formatRupiah(d.total_bayar)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            )}
        </>
    );
}

export default function DetailDrawer({
    pinjaman,
    angsuran = [],
    pelunasan_resign = { total: 0, tanggal: null },
    jurnal_pelunasan = [],
    onAjukanPenghapusan,
    inDrawer = false,
    onClose,
}) {
    const { auth } = usePage().props;
    const roles = auth?.user?.roles ?? [];
    const isPengawasSaja = roles.includes('pengawas') && !roles.some((r) => ['super_admin', 'sekretaris', 'bendahara', 'ketua_koperasi'].includes(r));
    const bisaCetakBukti = !isPengawasSaja || Boolean(pinjaman.is_milik_sendiri);
    const isResign = pinjaman.anggota_status === 'resign';

    const jadwalAktif = angsuran.length > 0 ? angsuran : (pinjaman.angsuran ?? []);
    const digantikan = pinjaman.angsuran_digantikan ?? [];
    const pelunasanResign = pelunasan_resign ?? pinjaman.pelunasan_resign ?? { total: 0, tanggal: null };
    const jurnalPelunasan = jurnal_pelunasan.length > 0 ? jurnal_pelunasan : (pinjaman.jurnal_pelunasan ?? []);
    const dilunasiViaResign = (pelunasanResign?.total ?? 0) > 0;

    const totalCicilan = pinjaman.total_cicilan_aktif ?? jadwalAktif.length;
    const lunasCount = pinjaman.lunas_cicilan_aktif ?? jadwalAktif.filter((a) => a.status === 'lunas').length;
    const persenLunas = totalCicilan > 0 ? Math.round((lunasCount / totalCicilan) * 100) : 0;

    const isPraCair = STATUS_ANTREAN.includes(pinjaman.status);
    const isDitolak = pinjaman.status === 'ditolak';
    const isBerjalan = pinjaman.status === 'aktif';
    const isLunas = pinjaman.status === 'lunas';
    const isHapusBuku = pinjaman.status === 'dihapusbukukan';

    const [activeTab, setActiveTab] = useState('ringkasan');
    const [tampilHistoriLama, setTampilHistoriLama] = useState(false);
    const [copiedRek, setCopiedRek] = useState(false);

    const catatanBendaharaValid = pinjaman.catatan_bendahara && !pinjaman.mandiri_bendahara ? pinjaman.catatan_bendahara : null;
    const catatanSekretarisValid = pinjaman.catatan_admin && !pinjaman.mandiri_sekretaris ? pinjaman.catatan_admin : null;
    const praCairInfo = INFO_TAHAP_PRA_CAIR[pinjaman.status];
    const tunggakan = pinjaman.tunggakan ?? {};
    const hapusFinal = pinjaman.penghapusan_disetujui;
    const hapusDitolak = pinjaman.penghapusan_ditolak_terakhir;
    const percepatan = pinjaman.percepatan_menunggu ?? pinjaman.percepatan_aktif;

    const angsuranBulananTampil = jadwalAktif[0]?.total_bayar ?? pinjaman.estimasi_cicilan_pertama ?? null;
    const adaTabJadwal = !isDitolak && ((isPraCair && (pinjaman.simulasi_angsuran?.length ?? 0) > 0) || !isPraCair);

    function handleSalinRekening() {
        const norek = pinjaman.rekening?.no_rekening;
        if (!norek || !navigator.clipboard) return;
        navigator.clipboard.writeText(norek);
        setCopiedRek(true);
        setTimeout(() => setCopiedRek(false), 1800);
    }

    function bukaCetak() {
        window.open(route('pinjaman.cetak-bukti', pinjaman.id), '_blank');
    }

    const tagsProfil = [
        isResign && { label: 'Anggota Resign', cls: 'bg-rose-500/20 text-rose-100 border-rose-300/35' },
        pinjaman.sudah_pakai_privilege_reloan && { label: 'Hak Reloan', cls: 'bg-white/10 text-emerald-200 border-white/20' },
        (pinjaman.mandiri_bendahara || pinjaman.mandiri_sekretaris) && { label: 'Pengajuan Mandiri', cls: 'bg-sky-500/20 text-sky-100 border-sky-300/35' },
    ].filter(Boolean);

    const tabs = [
        { id: 'ringkasan', label: 'Ringkasan & Akad' },
        adaTabJadwal && {
            id: 'jadwal',
            label: isPraCair ? 'Simulasi Angsuran' : 'Jadwal Angsuran',
            badge: isPraCair
                ? `${pinjaman.simulasi_angsuran?.length ?? 0} bln`
                : totalCicilan > 0
                    ? `${lunasCount}/${totalCicilan}`
                    : null,
        },
        { id: 'riwayat', label: 'Riwayat Persetujuan' },
    ].filter(Boolean);

    const hasDokumenAksi = Boolean(pinjaman.bukti_pencairan_url || (bisaCetakBukti && isBerjalan));
    const ukuranTombol = inDrawer ? 'drawer' : 'sm';

    const aksiUtama = (
        <div className="flex flex-wrap items-center justify-end gap-2.5">
            {bisaCetakBukti && isBerjalan && (
                <Button type="button" variant="neutral" size={ukuranTombol} onClick={bukaCetak}>
                    <Printer size={15} aria-hidden="true" />
                    Cetak Bukti
                </Button>
            )}
            {pinjaman.bisa_ajukan_penghapusan && onAjukanPenghapusan && (
                <Button
                    type="button"
                    size={ukuranTombol}
                    variant="dangerSoft"
                    onClick={() => onAjukanPenghapusan(pinjaman)}
                >
                    <FileX2 size={15} aria-hidden="true" />
                    Ajukan Penghapusan
                </Button>
            )}
            {pinjaman.aksi_persetujuan && (
                <Link
                    href={pinjaman.aksi_persetujuan.href}
                    className={`${buttonBase} ${buttonVariants.secondary} ${buttonSizes[ukuranTombol]}`}
                >
                    <span>{pinjaman.aksi_persetujuan.label}</span>
                    <ArrowRight size={15} aria-hidden="true" />
                </Link>
            )}
        </div>
    );

    const kontenUtama = (
        <>
            {/* Executive Hero — Profil Peminjam & Kontrak Utama */}
            <DrawerHero className="p-5 sm:p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="flex items-center gap-3.5 min-w-0">
                        <FotoAnggota
                            nama={pinjaman.nama}
                            fotoUrl={pinjaman.foto_url}
                            ukuran="md"
                            className="bg-white/15 ring-white/25 shrink-0"
                        />
                        <div className="min-w-0">
                            <p className="text-base sm:text-lg font-bold text-white tracking-tight truncate">
                                {pinjaman.nama}
                            </p>
                            <p className="text-xs text-slate-300 truncate mt-0.5">
                                <span className="font-mono">{pinjaman.no_karyawan}</span>
                                {pinjaman.cabang ? ` • ${pinjaman.cabang}` : ''}
                                {pinjaman.anggota_info?.lama_keanggotaan ? ` • Anggota ${pinjaman.anggota_info.lama_keanggotaan}` : ''}
                            </p>
                        </div>
                    </div>

                    {tagsProfil.length > 0 && (
                        <div className="flex flex-wrap gap-1.5">
                            {tagsProfil.map((t) => (
                                <span
                                    key={t.label}
                                    className={`px-2.5 py-0.5 rounded-full text-[11px] font-semibold border ${t.cls}`}
                                >
                                    {t.label}
                                </span>
                            ))}
                        </div>
                    )}
                </div>

                <div className="mt-5 pt-5 border-t border-white/12">
                    <p className="text-[11px] font-semibold uppercase tracking-wider text-slate-300/90">
                        {isPraCair
                            ? 'Nominal Pengajuan'
                            : isDitolak
                                ? 'Nominal yang Diajukan'
                                : 'Nominal Pinjaman Disetujui'}
                    </p>
                    <p className="text-2xl sm:text-[32px] leading-tight font-extrabold tracking-tight tabular-nums text-white mt-1 privacy-blur">
                        {formatRupiah(pinjaman.nominal)}
                    </p>
                    {pinjaman.terbilang && (
                        <p className="text-xs text-slate-300/90 italic mt-1 privacy-blur">{pinjaman.terbilang}</p>
                    )}

                    <dl className="grid grid-cols-2 sm:grid-cols-4 gap-x-5 gap-y-4 pt-5 mt-5 border-t border-white/12">
                        <div>
                            <dt className="text-[11px] text-slate-300/85">Tenor</dt>
                            <dd className="text-sm font-bold text-white mt-1 tabular-nums">{pinjaman.tenor_bulan} bulan</dd>
                        </div>
                        <div>
                            <dt className="text-[11px] text-slate-300/85">Suku Bunga</dt>
                            <dd className="text-sm font-bold text-white mt-1 tabular-nums">{pinjaman.persentase_bunga ?? 0}% / bln</dd>
                        </div>
                        <div>
                            <dt className="text-[11px] text-slate-300/85">
                                {isPraCair ? 'Estimasi Angsuran' : 'Angsuran / Bln'}
                            </dt>
                            <dd className="text-sm font-bold text-white mt-1 tabular-nums privacy-blur">
                                {angsuranBulananTampil ? formatRupiah(angsuranBulananTampil) : '-'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-[11px] text-slate-300/85">
                                {pinjaman.tanggal_cair ? 'Tanggal Cair' : 'Tgl Pengajuan'}
                            </dt>
                            <dd className="text-sm font-bold text-white mt-1">
                                {pinjaman.tanggal_cair || pinjaman.tanggal_pengajuan}
                            </dd>
                        </div>
                    </dl>
                </div>
            </DrawerHero>

            {/* Navigasi Segmented Tab */}
            <nav
                aria-label="Navigasi rincian pinjaman"
                className="flex items-center gap-1 rounded-xl bg-slate-200/75 p-1"
            >
                {tabs.map((tab) => {
                    const aktif = activeTab === tab.id;
                    return (
                        <button
                            key={tab.id}
                            type="button"
                            onClick={() => setActiveTab(tab.id)}
                            className={`flex-1 inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs sm:text-sm font-semibold transition-all cursor-pointer ${
                                aktif
                                    ? 'bg-white text-brand-navy shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            <span className="truncate">{tab.label}</span>
                            {tab.badge && (
                                <span
                                    className={`px-2 py-0.5 rounded-full text-[11px] font-bold tabular-nums ${
                                        aktif
                                            ? 'bg-brand-navy/10 text-brand-navy'
                                            : 'bg-slate-300/60 text-slate-700'
                                    }`}
                                >
                                    {tab.badge}
                                </span>
                            )}
                        </button>
                    );
                })}
            </nav>

            {/* ==================== TAB 1: RINGKASAN & AKAD ==================== */}
            {activeTab === 'ringkasan' && (
                <div className="space-y-5">
                    {/* A. Status Ditolak */}
                    {isDitolak && (
                        <PanelPutih accentClass="border-l-rose-500" className="p-5 sm:p-6">
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <span className="text-[11px] font-semibold uppercase tracking-wider text-rose-600">
                                        Pengajuan Ditolak
                                    </span>
                                    <h3 className="text-base font-bold text-slate-900 mt-1">
                                        Ditolak pada Tahap {pinjaman.info_penolakan?.tahap ?? 'Verifikasi'}
                                    </h3>
                                </div>
                                {pinjaman.info_penolakan?.tanggal && (
                                    <span className="text-xs text-slate-500 tabular-nums">
                                        {pinjaman.info_penolakan.tanggal}
                                    </span>
                                )}
                            </div>
                            <blockquote className="mt-4 rounded-xl bg-rose-50/60 border border-rose-200/80 px-4 py-3 text-sm text-rose-950 leading-relaxed">
                                &ldquo;{pinjaman.info_penolakan?.alasan || 'Tidak ada catatan alasan penolakan.'}&rdquo;
                            </blockquote>
                        </PanelPutih>
                    )}

                    {/* B. Status Pra-Cair (Antrean Persetujuan & Pencairan) */}
                    {isPraCair && praCairInfo && (
                        <PanelPutih accentClass={praCairInfo.accent} className="p-5 sm:p-6">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border ${praCairInfo.badge}`}>
                                    {praCairInfo.tahap}
                                </span>
                                {pinjaman.status === 'approved_ketua' && pinjaman.disetujui_ketua_pada && (
                                    <span className="text-xs font-medium text-emerald-700">
                                        Disetujui {pinjaman.disetujui_ketua_pada}
                                    </span>
                                )}
                            </div>
                            <h3 className="text-base font-bold text-slate-900 mt-2.5">{praCairInfo.judul}</h3>
                            <p className="text-sm text-slate-600 leading-relaxed mt-1">{praCairInfo.deskripsi}</p>

                            {pinjaman.estimasi_total_bayar > 0 && (
                                <dl className="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-5 pt-4 border-t border-slate-100">
                                    <div>
                                        <dt className="text-[11px] font-medium text-slate-500">Estimasi Cicilan / Bln</dt>
                                        <dd className="text-sm font-bold text-slate-900 mt-1 tabular-nums privacy-blur">
                                            {formatRupiah(pinjaman.estimasi_cicilan_pertama)}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-[11px] font-medium text-slate-500">Estimasi Total Bunga</dt>
                                        <dd className="text-sm font-bold text-slate-900 mt-1 tabular-nums privacy-blur">
                                            {formatRupiah(pinjaman.estimasi_total_bunga)}
                                        </dd>
                                    </div>
                                    <div className="col-span-2 sm:col-span-1">
                                        <dt className="text-[11px] font-medium text-slate-500">Estimasi Total Bayar</dt>
                                        <dd className="text-sm font-bold text-brand-navy mt-1 tabular-nums privacy-blur">
                                            {formatRupiah(pinjaman.estimasi_total_bayar)}
                                        </dd>
                                    </div>
                                </dl>
                            )}
                        </PanelPutih>
                    )}

                    {/* C. Status Aktif / Menunggak */}
                    {isBerjalan && (
                        <>
                            {((tunggakan.jumlah_terlambat ?? 0) > 0 || pinjaman.penghapusan_aktif || hapusDitolak) && (
                                <PanelPutih
                                    accentClass={(tunggakan.jumlah_terlambat ?? 0) > 0 ? 'border-l-rose-600' : 'border-l-amber-500'}
                                    className="p-5 sm:p-6 space-y-4"
                                >
                                    {(tunggakan.jumlah_terlambat ?? 0) > 0 && (
                                        <div>
                                            <div className="flex items-center gap-2 text-rose-700">
                                                <AlertTriangle size={16} aria-hidden="true" />
                                                <h3 className="text-sm font-bold">Peringatan Tunggakan Angsuran</h3>
                                            </div>
                                            <dl className="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4 pt-4 border-t border-slate-100">
                                                <div>
                                                    <dt className="text-[11px] font-medium text-slate-500">Cicilan Terlambat</dt>
                                                    <dd className="text-sm font-bold text-slate-900 mt-1">
                                                        {tunggakan.jumlah_terlambat}x ({(tunggakan.cicilan_list ?? []).map((n) => `ke-${n}`).join(', ')})
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt className="text-[11px] font-medium text-slate-500">Nominal Tunggakan</dt>
                                                    <dd className="text-sm font-bold text-rose-700 mt-1 tabular-nums privacy-blur">
                                                        {formatRupiah(tunggakan.nominal_tunggakan)}
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt className="text-[11px] font-medium text-slate-500">Menunggak Sejak</dt>
                                                    <dd className="text-sm font-bold text-slate-900 mt-1">
                                                        {tunggakan.jatuh_tempo_tertua}
                                                    </dd>
                                                </div>
                                            </dl>
                                        </div>
                                    )}

                                    {pinjaman.penghapusan_aktif && (
                                        <div className="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                                            <div>
                                                <p className="text-[11px] font-semibold uppercase tracking-wider text-amber-700">
                                                    Pengajuan Penghapusan Diproses
                                                </p>
                                                <p className="text-sm font-medium text-slate-800 mt-0.5">
                                                    Status saat ini: <strong>{pinjaman.penghapusan_aktif.status_label}</strong>
                                                </p>
                                            </div>
                                            <Link
                                                href={route('persetujuan.index', { view: 'berjalan', kategori: 'penghapusan' })}
                                                className="inline-flex items-center gap-1 text-xs font-semibold text-brand-navy hover:underline shrink-0"
                                            >
                                                Pantau di Pusat Persetujuan
                                                <ArrowRight size={13} aria-hidden="true" />
                                            </Link>
                                        </div>
                                    )}

                                    {!pinjaman.penghapusan_aktif && hapusDitolak && (
                                        <div className="pt-4 border-t border-slate-100">
                                            <p className="text-xs font-semibold text-rose-700">
                                                Penghapusan Sebelumnya Ditolak oleh {hapusDitolak.tahap_ditolak_label ?? 'Pengawas'}
                                                {hapusDitolak.penolak_nama ? ` (${hapusDitolak.penolak_nama})` : ''}
                                                {hapusDitolak.ditolak_pada ? ` • ${hapusDitolak.ditolak_pada}` : ''}
                                            </p>
                                            {hapusDitolak.catatan_tolak && (
                                                <p className="mt-1.5 text-sm text-slate-700 italic">
                                                    &ldquo;{hapusDitolak.catatan_tolak}&rdquo;
                                                </p>
                                            )}
                                        </div>
                                    )}
                                </PanelPutih>
                            )}

                            <PanelPutih>
                                <div className="px-5 py-4 sm:px-6 border-b border-slate-100">
                                    <div className="flex items-center justify-between gap-3">
                                        <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                            Posisi Piutang &amp; Progres Pembayaran
                                        </h3>
                                        <span className="text-xs font-bold text-slate-800 tabular-nums">
                                            {lunasCount} dari {totalCicilan} lunas ({persenLunas}%)
                                        </span>
                                    </div>
                                    {totalCicilan > 0 && (
                                        <div className="h-2 w-full rounded-full bg-slate-100 overflow-hidden mt-3">
                                            <div
                                                className="h-full rounded-full bg-gradient-to-r from-brand-navy to-brand-green transition-all duration-300"
                                                style={{ width: `${persenLunas}%` }}
                                            />
                                        </div>
                                    )}
                                </div>

                                <dl className="grid grid-cols-2 sm:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
                                    <div className="px-5 py-4">
                                        <dt className="text-[11px] font-medium text-slate-500">Pokok Terbayar</dt>
                                        <dd className="text-base font-bold text-brand-green-dark tabular-nums mt-1 privacy-blur">
                                            {formatRupiah(pinjaman.total_pokok_terbayar)}
                                        </dd>
                                    </div>
                                    <div className="px-5 py-4">
                                        <dt className="text-[11px] font-medium text-slate-500">Sisa Pokok</dt>
                                        <dd className="text-base font-bold text-slate-900 tabular-nums mt-1 privacy-blur">
                                            {formatRupiah(pinjaman.sisa_pokok)}
                                        </dd>
                                    </div>
                                    <div className="px-5 py-4">
                                        <dt className="text-[11px] font-medium text-slate-500">Sisa Bunga</dt>
                                        <dd className="text-base font-bold text-slate-700 tabular-nums mt-1 privacy-blur">
                                            {formatRupiah(pinjaman.sisa_bunga)}
                                        </dd>
                                    </div>
                                    <div className="px-5 py-4">
                                        <dt className="text-[11px] font-medium text-slate-500">Total Sisa Tagihan</dt>
                                        <dd className="text-base font-bold text-brand-navy tabular-nums mt-1 privacy-blur">
                                            {formatRupiah(pinjaman.sisa_total_tagihan)}
                                        </dd>
                                    </div>
                                </dl>

                                {percepatan && (
                                    <div className="px-5 py-3.5 sm:px-6 bg-[#F8FAFC] border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs">
                                        <span className="font-semibold text-brand-navy">
                                            {pinjaman.percepatan_menunggu ? 'Perubahan Tenor Menunggu Persetujuan' : 'Perubahan Tenor Aktif'} ({percepatan.tipe_label})
                                        </span>
                                        <span className="text-slate-600 font-medium">
                                            {percepatan.tenor_lama} → {percepatan.tenor_baru} bulan • Berlaku {percepatan.bulan_berlaku || percepatan.tanggal_pengajuan}
                                        </span>
                                    </div>
                                )}
                            </PanelPutih>
                        </>
                    )}

                    {/* D. Status Lunas */}
                    {isLunas && (
                        <PanelPutih accentClass="border-l-emerald-600" className="p-5 sm:p-6">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <span className="text-[11px] font-semibold uppercase tracking-wider text-brand-green-dark">
                                        Kewajiban Terpenuhi
                                    </span>
                                    <h3 className="text-base font-bold text-slate-900 mt-1">Pinjaman Lunas</h3>
                                </div>
                                <span className="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-brand-green-dark border border-emerald-200">
                                    {pinjaman.penyelesaian?.metode || 'Angsuran Reguler Penuh'}
                                </span>
                            </div>

                            <dl className="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-5 pt-4 border-t border-slate-100">
                                <div>
                                    <dt className="text-[11px] font-medium text-slate-500">Total Pokok Terbayar</dt>
                                    <dd className="text-base font-bold text-slate-900 tabular-nums mt-1 privacy-blur">
                                        {formatRupiah(pinjaman.total_pokok_terbayar)}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-[11px] font-medium text-slate-500">Total Bunga Terbayar</dt>
                                    <dd className="text-base font-bold text-slate-700 tabular-nums mt-1 privacy-blur">
                                        {formatRupiah(pinjaman.total_bunga_terbayar)}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-[11px] font-medium text-slate-500">Total Pembayaran Masuk</dt>
                                    <dd className="text-base font-bold text-brand-green-dark tabular-nums mt-1 privacy-blur">
                                        {formatRupiah(pinjaman.total_terbayar)}
                                    </dd>
                                </div>
                            </dl>

                            <dl className="divide-y divide-slate-100 mt-5 pt-4 border-t border-slate-100">
                                <BarisInformasi label="Tanggal Lunas">
                                    <span className="font-medium">
                                        {pinjaman.penyelesaian?.tanggal_lunas || pinjaman.tanggal_lunas_terakhir || '-'}
                                    </span>
                                </BarisInformasi>
                                {dilunasiViaResign && (
                                    <BarisInformasi label="Pelunasan via Resign">
                                        <span className="font-bold text-rose-700 tabular-nums privacy-blur">
                                            {formatRupiah(pelunasanResign.total)}
                                        </span>
                                        {pelunasanResign.tanggal && (
                                            <span className="text-xs text-slate-500 ml-2">({pelunasanResign.tanggal})</span>
                                        )}
                                    </BarisInformasi>
                                )}
                            </dl>
                        </PanelPutih>
                    )}

                    {/* E. Status Dihapusbukukan */}
                    {isHapusBuku && hapusFinal && (
                        <PanelPutih accentClass="border-l-rose-600" className="p-5 sm:p-6">
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <span className="text-[11px] font-semibold uppercase tracking-wider text-rose-600">
                                        Write-Off / Dihapusbukukan
                                    </span>
                                    <h3 className="text-base font-bold text-slate-900 mt-1">
                                        Keputusan Penghapusan Buku Final
                                    </h3>
                                </div>
                                {hapusFinal.nomor_dokumen && (
                                    <span className="px-2.5 py-1 rounded-lg bg-slate-100 font-mono text-xs font-semibold text-slate-700 border border-slate-200">
                                        {hapusFinal.nomor_dokumen}
                                    </span>
                                )}
                            </div>

                            <dl className="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-5 pt-4 border-t border-slate-100">
                                <div>
                                    <dt className="text-[11px] font-medium text-slate-500">
                                        Sisa Pokok Dihapus ({hapusFinal.sisa_cicilan}x)
                                    </dt>
                                    <dd className="text-base font-bold text-rose-700 tabular-nums mt-1 privacy-blur">
                                        {formatRupiah(hapusFinal.sisa_pokok)}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-[11px] font-medium text-slate-500">Pokok Sudah Terbayar</dt>
                                    <dd className="text-base font-bold text-slate-900 tabular-nums mt-1 privacy-blur">
                                        {formatRupiah(hapusFinal.total_pokok_terbayar)}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-[11px] font-medium text-slate-500">Sisa Bunga Dibatalkan</dt>
                                    <dd className="text-base font-bold text-slate-700 tabular-nums mt-1 privacy-blur">
                                        {formatRupiah(hapusFinal.sisa_bunga)}
                                    </dd>
                                </div>
                            </dl>

                            <dl className="divide-y divide-slate-100 mt-5 pt-4 border-t border-slate-100">
                                <BarisInformasi label="Disetujui Final">
                                    {hapusFinal.disetujui_pada || '-'}
                                </BarisInformasi>
                                <BarisInformasi label="Diajukan Oleh">
                                    {hapusFinal.pengaju_nama || '-'}
                                    {hapusFinal.tanggal_pengajuan ? ` • ${hapusFinal.tanggal_pengajuan}` : ''}
                                </BarisInformasi>
                                <BarisInformasi label="Alasan Penghapusan">
                                    <span className="text-slate-700 leading-relaxed">{hapusFinal.alasan || '-'}</span>
                                </BarisInformasi>
                            </dl>
                        </PanelPutih>
                    )}

                    {/* F. Informasi Akad, Rekening & Lampiran Dokumen */}
                    <PanelPutih className="p-5 sm:p-6">
                        <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-4">
                            Informasi Akad, Rekening &amp; Pencairan
                        </h3>

                        <dl className="divide-y divide-slate-100">
                            <BarisInformasi label="Nomor Dokumen">
                                <span className="font-mono font-semibold text-slate-800">
                                    {pinjaman.nomor_dokumen_resmi ?? pinjaman.nomor_dokumen ?? `#${pinjaman.id}`}
                                </span>
                            </BarisInformasi>

                            <BarisInformasi label="Keperluan Pinjaman">
                                <p className="text-slate-800 leading-relaxed">{pinjaman.keperluan || '-'}</p>
                            </BarisInformasi>

                            {(pinjaman.rekening?.bank || pinjaman.rekening?.no_rekening) && (
                                <BarisInformasi label="Rekening Tujuan">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <div className="min-w-0">
                                            <p className="font-semibold text-slate-900 flex items-center gap-1.5">
                                                <Landmark size={14} className="text-slate-400 shrink-0" aria-hidden="true" />
                                                <span>{pinjaman.rekening.bank || '-'}</span>
                                            </p>
                                            <p className="font-mono font-semibold text-slate-700 mt-0.5">
                                                {pinjaman.rekening.no_rekening || '-'}
                                            </p>
                                            <p className="text-xs text-slate-500 mt-0.5">
                                                a.n. {pinjaman.rekening.atas_nama || '-'}
                                            </p>
                                        </div>

                                        {pinjaman.rekening.no_rekening && (
                                            <button
                                                type="button"
                                                onClick={handleSalinRekening}
                                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-brand-navy hover:bg-slate-50 transition-colors cursor-pointer shrink-0"
                                            >
                                                {copiedRek ? <Check size={13} className="text-brand-green-dark" /> : <Copy size={13} />}
                                                <span>{copiedRek ? 'Tersalin' : 'Salin'}</span>
                                            </button>
                                        )}
                                    </div>
                                </BarisInformasi>
                            )}

                            <BarisInformasi label="Diajukan Oleh">
                                <span>{pinjaman.pengaju_nama || pinjaman.nama}</span>
                                <span className="text-xs text-slate-500 ml-2">({pinjaman.tanggal_pengajuan})</span>
                            </BarisInformasi>

                            {pinjaman.tanggal_cair && (
                                <BarisInformasi label="Realisasi Pencairan">
                                    <p className="font-medium text-slate-900">
                                        {pinjaman.tanggal_cair}
                                        {pinjaman.dicairkan_oleh_nama ? ` • oleh ${pinjaman.dicairkan_oleh_nama}` : ''}
                                    </p>
                                    {pinjaman.catatan_pencairan && (
                                        <p className="text-xs text-slate-500 mt-1 italic">
                                            &ldquo;{pinjaman.catatan_pencairan}&rdquo;
                                        </p>
                                    )}
                                </BarisInformasi>
                            )}

                            {hasDokumenAksi && (
                                <BarisInformasi label="Dokumen Resmi">
                                    <div className="flex flex-wrap items-center gap-2.5">
                                        {pinjaman.bukti_pencairan_url && (
                                            <a
                                                href={pinjaman.bukti_pencairan_url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className={`${buttonBase} ${buttonVariants.neutral} ${buttonSizes.sm}`}
                                            >
                                                <FileText size={14} aria-hidden="true" />
                                                <span>Lihat Bukti Transfer</span>
                                                <ExternalLink size={12} aria-hidden="true" />
                                            </a>
                                        )}
                                        {bisaCetakBukti && isBerjalan && (
                                            <Button type="button" variant="neutral" size="sm" onClick={bukaCetak}>
                                                <Printer size={14} aria-hidden="true" />
                                                Cetak Bukti Pinjaman
                                            </Button>
                                        )}
                                    </div>
                                </BarisInformasi>
                            )}
                        </dl>
                    </PanelPutih>
                </div>
            )}

            {/* ==================== TAB 2: JADWAL / SIMULASI ANGSURAN ==================== */}
            {activeTab === 'jadwal' && adaTabJadwal && (
                <div className="space-y-5">
                    <PanelPutih>
                        <div className="px-5 py-4 sm:px-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h3 className="text-sm font-bold text-slate-900">
                                    {isPraCair
                                        ? 'Simulasi Jadwal Angsuran'
                                        : isHapusBuku
                                            ? 'Status Akhir Jadwal Angsuran'
                                            : 'Jadwal Angsuran'}
                                </h3>
                                <p className="text-xs text-slate-500 mt-0.5">
                                    {isPraCair ? (
                                        <>
                                            Estimasi total pembayaran{' '}
                                            <span className="font-semibold text-slate-700 privacy-blur">
                                                {formatRupiah(pinjaman.estimasi_total_bayar)}
                                            </span>{' '}
                                            (bunga{' '}
                                            <span className="font-semibold text-slate-700 privacy-blur">
                                                {formatRupiah(pinjaman.estimasi_total_bunga)}
                                            </span>
                                            )
                                        </>
                                    ) : (
                                        `${lunasCount} dari ${totalCicilan} angsuran telah lunas (${persenLunas}%)`
                                    )}
                                </p>
                            </div>
                        </div>

                        <TabelJadwalEditorial
                            rows={isPraCair ? (pinjaman.simulasi_angsuran ?? []) : jadwalAktif}
                            isSimulasi={isPraCair}
                            digantikan={isPraCair ? [] : digantikan}
                            tampilHistoriLama={tampilHistoriLama}
                            onToggleHistoriLama={() => setTampilHistoriLama((v) => !v)}
                        />
                    </PanelPutih>

                    {jurnalPelunasan.length > 0 && (
                        <PanelPutih>
                            <div className="px-5 py-4 sm:px-6 border-b border-slate-100">
                                <h3 className="text-sm font-bold text-slate-900">Riwayat Pelunasan Resign</h3>
                            </div>
                            <table className="w-full text-left border-collapse">
                                <tbody className="divide-y divide-slate-100 text-sm">
                                    {jurnalPelunasan.map((j) => (
                                        <tr key={j.id} className="hover:bg-slate-50/80">
                                            <td className="py-3.5 pl-5 pr-3">
                                                <p className="font-semibold text-slate-900">{j.keterangan}</p>
                                                <p className="text-xs text-slate-500 mt-0.5">
                                                    {j.sub_judul ? `${j.sub_judul} • ` : ''}{j.tanggal}
                                                </p>
                                            </td>
                                            <td className="py-3.5 pl-3 pr-5 text-right font-bold text-rose-600 tabular-nums whitespace-nowrap privacy-blur">
                                                -{formatRupiah(j.jumlah)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </PanelPutih>
                    )}
                </div>
            )}

            {/* ==================== TAB 3: RIWAYAT PERSETUJUAN ==================== */}
            {activeTab === 'riwayat' && (
                <div className="space-y-5">
                    <JejakNominal pinjaman={pinjaman} finalLabel="Disetujui Ketua" />

                    {pinjaman.timeline_verifikasi?.length > 0 && (
                        <PanelPutih className="p-5 sm:p-6">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-5">
                                Timeline Verifikasi &amp; Pencairan
                            </h3>
                            <VerticalStepper
                                steps={pinjaman.timeline_verifikasi}
                                notesContext={{
                                    catatanSekretaris: catatanSekretarisValid,
                                    catatanBendahara: catatanBendaharaValid,
                                    catatanKetua: pinjaman.catatan_ketua,
                                    catatanPencairan: pinjaman.catatan_pencairan,
                                    infoPenolakan: isDitolak ? pinjaman.info_penolakan : null,
                                }}
                            />
                        </PanelPutih>
                    )}

                    {hapusFinal?.timeline?.length > 0 && (
                        <PanelPutih className="p-5 sm:p-6">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-5">
                                Alur Persetujuan Penghapusan Buku (Pengawas 3 → 2 → 1)
                            </h3>
                            <VerticalStepper steps={hapusFinal.timeline} />
                        </PanelPutih>
                    )}
                </div>
            )}
        </>
    );

    if (inDrawer) {
        return (
            <>
                <DrawerBody className="px-5 py-5 sm:px-6 sm:py-6 space-y-5">
                    {kontenUtama}
                </DrawerBody>
                <DrawerFooter className="px-5 py-3.5 sm:px-6 sm:py-4">
                    <div className="flex flex-col-reverse sm:flex-row sm:items-center justify-between gap-3">
                        <Button type="button" variant="neutral" size="drawer" onClick={onClose}>
                            Tutup
                        </Button>
                        {aksiUtama}
                    </div>
                </DrawerFooter>
            </>
        );
    }

    return (
        <div className="space-y-5">
            {(pinjaman.aksi_persetujuan || pinjaman.bisa_ajukan_penghapusan || (bisaCetakBukti && isBerjalan)) && (
                <div className="flex justify-end">{aksiUtama}</div>
            )}
            {kontenUtama}
        </div>
    );
}
