import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    ChevronRight,
    Clock,
    FileX2,
    Plus,
    Search,
    ShieldCheck,
    X,
    XCircle,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import Button from '@/Components/ui/Button';
import Card from '@/Components/ui/Card';
import CountBadge from '@/Components/ui/CountBadge';
import Drawer from '@/Components/ui/Drawer';
import FormField from '@/Components/ui/FormField';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import PageHeader from '@/Components/ui/PageHeader';
import StatusBadge from '@/Components/ui/StatusBadge';
import TextField from '@/Components/ui/TextField';
import { formatRupiah } from '@/Utils/formatCurrency';

const tabDasar = 'inline-flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-xl transition-all duration-150';
const tabAktif = `${tabDasar} bg-brand-green-light/45 border border-brand-green/35 text-brand-navy`;
const tabNonAktif = `${tabDasar} text-slate-600 hover:bg-slate-100/70 hover:text-brand-navy`;

export default function Index({
    menungguSaya = [],
    sedangBerjalan = [],
    riwayatSelesai = [],
    pinjamanMenunggak = [],
    bisaAjukan = false,
    peranSaya = 'Pengurus',
    statistik = {},
}) {
    const [tab, setTab] = useState(menungguSaya.length > 0 ? 'menunggu' : 'berjalan');
    const [cari, setCari] = useState('');
    const [detail, setDetail] = useState(null);
    const [drawerDetailOpen, setDrawerDetailOpen] = useState(false);

    const [targetPinjaman, setTargetPinjaman] = useState(null);
    const [drawerAjukanOpen, setDrawerAjukanOpen] = useState(false);

    const formAjukan = useForm({
        pinjaman_id: '',
        alasan: '',
    });

    const formKeputusan = useForm({
        catatan: '',
    });

    function bukaDetail(item) {
        setDetail(item);
        formKeputusan.reset();
        formKeputusan.clearErrors();
        setDrawerDetailOpen(true);
    }

    function tutupDetail() {
        setDrawerDetailOpen(false);
    }

    function bukaModalAjukan(pinjaman) {
        setTargetPinjaman(pinjaman);
        formAjukan.setData({
            pinjaman_id: pinjaman.pinjaman_id,
            alasan: '',
        });
        formAjukan.clearErrors();
        setDrawerAjukanOpen(true);
    }

    function submitAjukan(e) {
        e.preventDefault();
        formAjukan.post(route('pengurus.penghapusan-pinjaman.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setDrawerAjukanOpen(false);
                setTargetPinjaman(null);
                formAjukan.reset();
                setTab('berjalan');
            },
        });
    }

    function submitApprove(e) {
        e.preventDefault();
        if (!detail) return;
        formKeputusan.post(route('pengurus.penghapusan-pinjaman.approve', detail.id), {
            preserveScroll: true,
            onSuccess: () => {
                setDrawerDetailOpen(false);
                formKeputusan.reset();
            },
        });
    }

    function submitReject(e) {
        e.preventDefault();
        if (!detail) return;
        formKeputusan.post(route('pengurus.penghapusan-pinjaman.reject', detail.id), {
            preserveScroll: true,
            onSuccess: () => {
                setDrawerDetailOpen(false);
                formKeputusan.reset();
            },
        });
    }

    const filterData = (items) => {
        const q = cari.trim().toLowerCase();
        if (!q) return items;
        return items.filter(
            (item) =>
                item.anggota?.nama?.toLowerCase().includes(q) ||
                item.anggota?.no_karyawan?.toLowerCase().includes(q) ||
                item.nomor_dokumen?.toLowerCase().includes(q) ||
                item.pinjaman_nomor_dokumen?.toLowerCase().includes(q)
        );
    };

    const daftarTampil = useMemo(() => {
        if (tab === 'menunggu') return filterData(menungguSaya);
        if (tab === 'berjalan') return filterData(sedangBerjalan);
        if (tab === 'riwayat') return filterData(riwayatSelesai);
        return filterData(pinjamanMenunggak);
    }, [tab, cari, menungguSaya, sedangBerjalan, riwayatSelesai, pinjamanMenunggak]);

    return (
        <AppLayout>
            <Head title="Penghapusan Pinjaman" />

            <PageHeader
                title="Penghapusan Pinjaman Menunggak"
                subtitle={`Alur berjenjang Pengurus → Pengawas 3 → Pengawas 2 → Pengawas 1 (Ketua Pengawas) • Peran Anda: ${peranSaya}`}
            >
                {bisaAjukan && (
                    <Button
                        size="sm"
                        onClick={() => setTab('ajukan')}
                        className="rounded-xl shadow-sm"
                    >
                        <Plus size={16} />
                        Ajukan Penghapusan
                    </Button>
                )}
            </PageHeader>

            {/* KPI Summary Cards */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <button
                    type="button"
                    onClick={() => setTab('menunggu')}
                    className={`text-left rounded-2xl p-4 border transition-all duration-200 cursor-pointer flex flex-col justify-between ${
                        tab === 'menunggu'
                            ? 'bg-amber-50/40 border-amber-400 ring-2 ring-amber-400/20 shadow-md'
                            : 'bg-white border-slate-200/80 hover:border-slate-300 shadow-xs'
                    }`}
                >
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-500">Menunggu Approval Saya</span>
                        <span className="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
                            <Clock size={16} />
                        </span>
                    </div>
                    <div className="mt-2">
                        <p className="text-2xl font-bold text-slate-800 tabular-nums">{statistik.menunggu_saya ?? 0}</p>
                        <p className="text-xs text-slate-400 mt-1 truncate">Giliran: {peranSaya}</p>
                    </div>
                </button>

                <button
                    type="button"
                    onClick={() => setTab('berjalan')}
                    className={`text-left rounded-2xl p-4 border transition-all duration-200 cursor-pointer flex flex-col justify-between ${
                        tab === 'berjalan'
                            ? 'bg-sky-50/40 border-sky-400 ring-2 ring-sky-400/20 shadow-md'
                            : 'bg-white border-slate-200/80 hover:border-slate-300 shadow-xs'
                    }`}
                >
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-500">Sedang Berjalan</span>
                        <span className="w-8 h-8 rounded-xl bg-sky-50 text-sky-700 flex items-center justify-center shrink-0">
                            <ShieldCheck size={16} />
                        </span>
                    </div>
                    <div className="mt-2">
                        <p className="text-2xl font-bold text-slate-800 tabular-nums">{statistik.sedang_berjalan ?? 0}</p>
                        <p className="text-xs text-slate-400 mt-1 truncate">Proses Pengurus & 3 Pengawas</p>
                    </div>
                </button>

                <button
                    type="button"
                    onClick={() => setTab('riwayat')}
                    className={`text-left rounded-2xl p-4 border transition-all duration-200 cursor-pointer flex flex-col justify-between ${
                        tab === 'riwayat'
                            ? 'bg-rose-50/40 border-rose-400 ring-2 ring-rose-400/20 shadow-md'
                            : 'bg-white border-slate-200/80 hover:border-slate-300 shadow-xs'
                    }`}
                >
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-500">Pinjaman Dihapusbukukan</span>
                        <span className="w-8 h-8 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center shrink-0">
                            <FileX2 size={16} />
                        </span>
                    </div>
                    <div className="mt-2">
                        <p className="text-2xl font-bold text-slate-800 tabular-nums">{statistik.disetujui ?? 0}</p>
                        <p className="text-xs text-slate-400 mt-1 truncate">{statistik.ditolak ?? 0} pengajuan ditolak</p>
                    </div>
                </button>

                <div className="rounded-2xl p-4 border bg-white border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-500">Total Pokok Dihapus</span>
                        <span className="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                            <AlertTriangle size={16} />
                        </span>
                    </div>
                    <div className="mt-2">
                        <p className="text-lg sm:text-xl font-bold text-rose-700 tabular-nums truncate privacy-blur">
                            {formatRupiah(statistik.total_pokok_dihapus ?? 0)}
                        </p>
                        <p className="text-xs text-slate-400 mt-1 truncate">Beban penghapusan pokok pinjaman</p>
                    </div>
                </div>
            </div>

            <Card padding="none" className="overflow-hidden shadow-md border-slate-200/70">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 px-3.5 py-2.5 bg-white border-b border-slate-200/80">
                    <div className="flex flex-wrap items-center gap-1.5">
                        <button onClick={() => setTab('menunggu')} className={tab === 'menunggu' ? tabAktif : tabNonAktif}>
                            Giliran Saya
                            <CountBadge count={menungguSaya.length} context="tab" />
                        </button>
                        <button onClick={() => setTab('berjalan')} className={tab === 'berjalan' ? tabAktif : tabNonAktif}>
                            Semua Antrean Berjalan
                            <CountBadge count={sedangBerjalan.length} tone="sky" context="tab" />
                        </button>
                        <button onClick={() => setTab('riwayat')} className={tab === 'riwayat' ? tabAktif : tabNonAktif}>
                            Riwayat Selesai
                        </button>
                        {bisaAjukan && (
                            <button onClick={() => setTab('ajukan')} className={tab === 'ajukan' ? tabAktif : tabNonAktif}>
                                Pilih Pinjaman Menunggak
                                <CountBadge count={pinjamanMenunggak.length} tone="emerald" context="tab" />
                            </button>
                        )}
                    </div>

                    <div className="relative w-full sm:w-64">
                        <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                        <TextField
                            type="search"
                            size="sm"
                            value={cari}
                            onChange={(e) => setCari(e.target.value)}
                            placeholder="Cari nama / no. karyawan..."
                            className="pl-9 pr-8 text-xs rounded-full"
                        />
                        {cari && (
                            <button
                                type="button"
                                onClick={() => setCari('')}
                                className="absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 inline-flex items-center justify-center rounded-full text-slate-400 hover:text-slate-600"
                            >
                                <X size={13} />
                            </button>
                        )}
                    </div>
                </div>

                {tab === 'ajukan' && bisaAjukan ? (
                    <div className="overflow-x-auto">
                        <table className="w-full table-sticky-first text-sm">
                            <thead>
                                <tr className="text-left border-b border-slate-200/80 bg-[#F8FAFC]">
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">Anggota</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">Pinjaman Awal</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">Sisa Cicilan</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">Sisa Pokok (Dihapus)</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">Sisa Bunga</th>
                                    <th className="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-wider text-slate-500">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 bg-white">
                                {daftarTampil.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-sm text-slate-500">
                                            Tidak ada pinjaman aktif yang dapat diajukan penghapusan.
                                        </td>
                                    </tr>
                                ) : (
                                    daftarTampil.map((p) => (
                                        <tr key={p.pinjaman_id} className="hover:bg-[#F2FBF5] transition-colors">
                                            <td className="px-4 py-2.5">
                                                <div className="flex items-center gap-2.5">
                                                    <FotoAnggota nama={p.anggota.nama} fotoUrl={p.anggota.foto_url} ukuran="sm" />
                                                    <div>
                                                        <p className="text-sm font-semibold text-slate-800">{p.anggota.nama}</p>
                                                        <p className="text-xs text-slate-500">
                                                            {p.anggota.no_karyawan} • {p.nomor_dokumen}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-2.5 tabular-nums text-slate-700 privacy-blur">
                                                <p className="font-semibold">{formatRupiah(p.nominal_pinjaman)}</p>
                                                <p className="text-xs text-slate-400">Cair: {p.tanggal_cair}</p>
                                            </td>
                                            <td className="px-4 py-2.5">
                                                <span className="font-semibold text-slate-800">{p.sisa_cicilan}x</span>
                                                {p.jumlah_terlambat > 0 && (
                                                    <span className="ml-1.5 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                        {p.jumlah_terlambat}x menunggak
                                                    </span>
                                                )}
                                                {p.jatuh_tempo_tertua && (
                                                    <p className="text-xs text-slate-400 mt-0.5">Sejak {p.jatuh_tempo_tertua}</p>
                                                )}
                                            </td>
                                            <td className="px-4 py-2.5 font-bold text-rose-700 tabular-nums privacy-blur">
                                                {formatRupiah(p.sisa_pokok)}
                                            </td>
                                            <td className="px-4 py-2.5 text-slate-600 tabular-nums privacy-blur">
                                                {formatRupiah(p.sisa_bunga)}
                                            </td>
                                            <td className="px-4 py-2.5 text-right">
                                                <Button size="sm" variant="outline" onClick={() => bukaModalAjukan(p)}>
                                                    Ajukan Penghapusan
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full table-sticky-first text-sm">
                            <thead>
                                <tr className="text-left border-b border-slate-200/80 bg-[#F8FAFC]">
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">Anggota</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">Sisa Pokok Dihapus</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">Sisa Cicilan</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">Tgl Pengajuan</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">Tahap / Status</th>
                                    <th className="w-10 px-3 py-2.5" aria-label="Detail" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 bg-white">
                                {daftarTampil.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-sm text-slate-500">
                                            Tidak ada data pengajuan penghapusan pinjaman pada tab ini.
                                        </td>
                                    </tr>
                                ) : (
                                    daftarTampil.map((item) => (
                                        <tr
                                            key={item.id}
                                            onClick={() => bukaDetail(item)}
                                            className="group hover:bg-[#F2FBF5] transition-colors cursor-pointer"
                                        >
                                            <td className="px-4 py-2.5">
                                                <div className="flex items-center gap-2.5">
                                                    <FotoAnggota nama={item.anggota.nama} fotoUrl={item.anggota.foto_url} ukuran="sm" />
                                                    <div>
                                                        <p className="text-sm font-semibold text-slate-800">{item.anggota.nama}</p>
                                                        <p className="text-xs text-slate-500">
                                                            {item.anggota.no_karyawan} • {item.nomor_dokumen ?? item.pinjaman_nomor_dokumen}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-2.5 font-bold text-rose-700 tabular-nums privacy-blur">
                                                {formatRupiah(item.sisa_pokok)}
                                            </td>
                                            <td className="px-4 py-2.5 text-slate-700">
                                                {item.sisa_cicilan}x cicilan
                                            </td>
                                            <td className="px-4 py-2.5 text-slate-600">
                                                <p>{item.tanggal_pengajuan}</p>
                                                <p className="text-xs text-slate-400">Oleh: {item.pengaju_nama}</p>
                                            </td>
                                            <td className="px-4 py-2.5">
                                                <div className="flex flex-col items-start gap-1">
                                                    <StatusBadge status={item.status} />
                                                    <span className="text-[11px] font-medium text-slate-500">{item.status_label}</span>
                                                </div>
                                            </td>
                                            <td className="px-3 py-2.5 text-right">
                                                <ChevronRight size={18} className="ml-auto text-slate-300 group-hover:text-brand-green transition-all" />
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                )}
            </Card>

            {/* Drawer Pengajuan Baru */}
            <Drawer
                show={drawerAjukanOpen}
                title={targetPinjaman ? `Ajukan Penghapusan — ${targetPinjaman.anggota.nama}` : 'Ajukan Penghapusan Pinjaman'}
                description="Penghapusan khusus pinjaman anggota menunggak / menghilang (6 jenjang approval)."
                onClose={() => setDrawerAjukanOpen(false)}
                maxWidth="xl"
            >
                {targetPinjaman && (
                    <form onSubmit={submitAjukan} className="space-y-4">
                        <div className="rounded-xl bg-white border border-slate-200/80 p-4 space-y-2.5">
                            <div className="flex items-center justify-between text-xs text-slate-500">
                                <span>Anggota</span>
                                <span className="font-bold text-slate-800">{targetPinjaman.anggota.nama} ({targetPinjaman.anggota.no_karyawan})</span>
                            </div>
                            <div className="flex items-center justify-between text-xs text-slate-500">
                                <span>Nominal Pinjaman Awal</span>
                                <span className="font-semibold text-slate-700 tabular-nums">{formatRupiah(targetPinjaman.nominal_pinjaman)}</span>
                            </div>
                            <div className="flex items-center justify-between text-xs text-slate-500">
                                <span>Pokok Sudah Terbayar</span>
                                <span className="font-semibold text-emerald-700 tabular-nums">{formatRupiah(targetPinjaman.total_pokok_terbayar)}</span>
                            </div>
                            <div className="flex items-center justify-between text-xs text-slate-500">
                                <span>Sisa Cicilan Belum Bayar</span>
                                <span className="font-semibold text-slate-800">{targetPinjaman.sisa_cicilan}x cicilan</span>
                            </div>
                            <div className="pt-2 border-t border-slate-100 flex items-center justify-between text-sm">
                                <span className="font-bold text-rose-700">Sisa Pokok Dihapusbukukan</span>
                                <span className="font-bold text-rose-700 tabular-nums">{formatRupiah(targetPinjaman.sisa_pokok)}</span>
                            </div>
                            <div className="flex items-center justify-between text-xs text-slate-400">
                                <span>Sisa Bunga Dibatalkan (Non-Beban)</span>
                                <span className="tabular-nums">{formatRupiah(targetPinjaman.sisa_bunga)}</span>
                            </div>
                        </div>

                        <div className="rounded-xl bg-amber-50/70 border border-amber-200/80 p-3.5 text-xs text-amber-900 space-y-1">
                            <p className="font-bold">Perlakuan Keuangan & Status Anggota:</p>
                            <p>
                                1. Hanya <strong>Sisa Pokok Pinjaman ({formatRupiah(targetPinjaman.sisa_pokok)})</strong> yang diakui sebagai Beban Penghapusan Pinjaman pada Laporan Laba Rugi / SHU dan mengurangi Piutang di Neraca (tanpa memotong saldo bank dua kali).
                            </p>
                            <p>
                                2. Saat disetujui final oleh Pengawas 1 (Ketua Pengawas), anggota dinonaktifkan agar tidak terbit tagihan iuran baru, namun simpanan lamanya tetap mengendap utuh.
                            </p>
                        </div>

                        <FormField label="Alasan Penghapusan Pinjaman" error={formAjukan.errors.alasan} required>
                            <textarea
                                rows={4}
                                value={formAjukan.data.alasan}
                                onChange={(e) => formAjukan.setData('alasan', e.target.value)}
                                placeholder="Jelaskan kronologi tunggakan / alasan anggota tidak dapat ditagih lagi (misal: anggota menghilang, sudah tidak aktif bekerja, dan tidak dapat dihubungi)..."
                                className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/20"
                                required
                            />
                        </FormField>

                        <div className="grid grid-cols-2 gap-3 pt-2">
                            <Button type="button" variant="neutral" onClick={() => setDrawerAjukanOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={formAjukan.processing}>
                                {formAjukan.processing ? 'Mengirim...' : 'Kirim Pengajuan'}
                            </Button>
                        </div>
                    </form>
                )}
            </Drawer>

            {/* Drawer Detail & Approval Berjenjang */}
            <Drawer
                show={drawerDetailOpen}
                title={detail ? `Penghapusan Pinjaman #${detail.pinjaman_id} — ${detail.anggota.nama}` : 'Detail Penghapusan'}
                badge={detail ? <StatusBadge status={detail.status} /> : null}
                onClose={tutupDetail}
                maxWidth="2xl"
            >
                {detail && (
                    <div className="space-y-4">
                        <div className="rounded-xl bg-white border border-slate-200/80 p-4 space-y-2.5">
                            <div className="flex items-center justify-between text-xs text-slate-500">
                                <span>Nomor Dokumen Penghapusan</span>
                                <span className="font-mono font-bold text-slate-800">{detail.nomor_dokumen ?? 'Terbit saat disetujui final'}</span>
                            </div>
                            <div className="flex items-center justify-between text-xs text-slate-500">
                                <span>Anggota</span>
                                <span className="font-bold text-slate-800">{detail.anggota.nama} ({detail.anggota.no_karyawan})</span>
                            </div>
                            <div className="flex items-center justify-between text-xs text-slate-500">
                                <span>Nominal Pinjaman Awal</span>
                                <span className="font-semibold text-slate-700 tabular-nums">{formatRupiah(detail.nominal_pinjaman)}</span>
                            </div>
                            <div className="flex items-center justify-between text-xs text-slate-500">
                                <span>Pokok Sudah Terbayar</span>
                                <span className="font-semibold text-emerald-700 tabular-nums">{formatRupiah(detail.total_pokok_terbayar)}</span>
                            </div>
                            <div className="flex items-center justify-between text-xs text-slate-500">
                                <span>Sisa Cicilan</span>
                                <span className="font-semibold text-slate-800">{detail.sisa_cicilan}x angsuran</span>
                            </div>
                            <div className="pt-2 border-t border-slate-100 flex items-center justify-between text-sm">
                                <span className="font-bold text-rose-700">Sisa Pokok Dihapusbukukan</span>
                                <span className="font-bold text-rose-700 tabular-nums">{formatRupiah(detail.sisa_pokok)}</span>
                            </div>
                            <div className="flex items-center justify-between text-xs text-slate-400">
                                <span>Sisa Bunga Dibatalkan</span>
                                <span className="tabular-nums">{formatRupiah(detail.sisa_bunga)}</span>
                            </div>
                        </div>

                        {/* Timeline 6 Jenjang */}
                        <div className="rounded-xl bg-white border border-slate-200/80 p-4">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">
                                Alur Persetujuan Berjenjang (Pengurus → Pengawas 3 → 2 → 1)
                            </h3>
                            <ol className="space-y-3">
                                {(detail.timeline ?? []).map((step) => {
                                    const isDone = step.status === 'done';
                                    const isCurrent = step.status === 'current';
                                    const isRejected = step.status === 'rejected';
                                    return (
                                        <li
                                            key={step.tahap}
                                            className={`flex items-start gap-3 rounded-xl p-2.5 border ${
                                                isRejected
                                                    ? 'bg-red-50/60 border-red-200'
                                                    : isCurrent
                                                        ? 'bg-amber-50/60 border-amber-300'
                                                        : isDone
                                                            ? 'bg-emerald-50/40 border-emerald-200/70'
                                                            : 'bg-slate-50/60 border-slate-200/60'
                                            }`}
                                        >
                                            <div className="mt-0.5 shrink-0">
                                                {isRejected ? (
                                                    <XCircle size={18} className="text-red-600" />
                                                ) : isDone ? (
                                                    <CheckCircle2 size={18} className="text-emerald-600" />
                                                ) : isCurrent ? (
                                                    <Clock size={18} className="text-amber-600" />
                                                ) : (
                                                    <Clock size={18} className="text-slate-300" />
                                                )}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center justify-between gap-2">
                                                    <p className="text-xs font-bold text-slate-800">{step.label}</p>
                                                    <span className="text-[11px] text-slate-400">{step.tanggal}</span>
                                                </div>
                                                <p className="text-xs text-slate-600 mt-0.5">Pejabat: {step.user}</p>
                                                {step.catatan && (
                                                    <p className="text-xs text-slate-700 mt-1 italic bg-white/80 rounded-lg px-2.5 py-1.5 border border-slate-200/60">
                                                        &ldquo;{step.catatan}&rdquo;
                                                    </p>
                                                )}
                                            </div>
                                        </li>
                                    );
                                })}
                            </ol>
                        </div>

                        {detail.bisa_diproses && (
                            <div className="rounded-xl bg-white border border-brand-green/40 p-4 space-y-3 shadow-sm">
                                <h3 className="text-sm font-bold text-brand-navy">
                                    Keputusan Anda ({peranSaya})
                                </h3>
                                <FormField label="Catatan Keputusan" error={formKeputusan.errors.catatan} required>
                                    <textarea
                                        rows={3}
                                        value={formKeputusan.data.catatan}
                                        onChange={(e) => formKeputusan.setData('catatan', e.target.value)}
                                        placeholder="Tulis catatan verifikasi / alasan persetujuan atau penolakan..."
                                        className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/20"
                                        required
                                    />
                                </FormField>
                                <div className="grid grid-cols-2 gap-3">
                                    <Button
                                        type="button"
                                        variant="danger"
                                        disabled={formKeputusan.processing}
                                        onClick={submitReject}
                                    >
                                        Tolak Pengajuan
                                    </Button>
                                    <Button
                                        type="button"
                                        disabled={formKeputusan.processing}
                                        onClick={submitApprove}
                                    >
                                        {detail.status === 'approved_pengawas_2'
                                            ? 'Setujui Final & Hapusbukukan'
                                            : 'Setujui & Teruskan'}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </Drawer>
        </AppLayout>
    );
}
