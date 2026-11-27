import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowUpRight,
    Check,
    Clock,
    Copy,
    CreditCard,
    Eye,
    EyeOff,
    Gauge,
    HandCoins,
    KeyRound,
    PiggyBank,
    ShieldCheck,
    Star,
    Wallet,
} from 'lucide-react';
import { useState } from 'react';
import UbahPinDrawer from '@/Components/security/UbahPinDrawer';
import Button, { buttonBase, buttonSizes, buttonVariants } from '@/Components/ui/Button';
import Card from '@/Components/ui/Card';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import PageHeader from '@/Components/ui/PageHeader';
import PrivacyWrapper from '@/Components/ui/PrivacyWrapper';
import StatusBadge from '@/Components/ui/StatusBadge';
import { UnifiedLedgerStrip } from '@/Components/ui/SummaryLayout';
import { usePrivacy } from '@/Contexts/PrivacyContext';
import { formatRupiah } from '@/Utils/formatCurrency';

const labelRole = {
    super_admin: 'Super Admin',
    sekretaris: 'Sekretaris',
    admin: 'Sekretaris',
    bendahara: 'Bendahara Koperasi',
    ketua_koperasi: 'Ketua Koperasi',
    pengawas: 'Pengawas Koperasi',
    anggota: 'Anggota Koperasi',
};

function FieldRingkas({ label, value, mono = false, colSpan = 1, copyValue = null }) {
    const [copied, setCopied] = useState(false);

    function handleCopy() {
        if (!copyValue || !navigator.clipboard) return;
        navigator.clipboard.writeText(copyValue);
        setCopied(true);
        setTimeout(() => setCopied(false), 1800);
    }

    return (
        <div className={colSpan === 2 ? 'sm:col-span-2' : ''}>
            <dt className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{label}</dt>
            <dd className={`mt-1 text-sm font-semibold text-slate-800 break-words flex items-center gap-2 ${mono ? 'font-mono' : ''}`}>
                <span>{value || '-'}</span>
                {copyValue && (
                    <button
                        type="button"
                        onClick={handleCopy}
                        title={`Salin ${label}`}
                        className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md border border-slate-200 bg-white text-[11px] font-sans font-semibold text-slate-500 hover:text-brand-navy hover:border-slate-300 transition-colors cursor-pointer"
                    >
                        {copied ? <Check size={11} className="text-brand-green-dark" /> : <Copy size={11} />}
                        <span>{copied ? 'Tersalin' : 'Salin'}</span>
                    </button>
                )}
            </dd>
        </div>
    );
}

export default function Edit({ pengguna, ringkasan }) {
    const { auth } = usePage().props;
    const { isPrivacyActive, togglePrivacy } = usePrivacy();

    const [copiedRekId, setCopiedRekId] = useState(null);
    const [pinDrawerOpen, setPinDrawerOpen] = useState(false);

    const a = pengguna?.anggota;
    const roles = pengguna?.roles ?? [];
    const permissions = auth?.user?.permissions ?? [];
    const punyaIzinPortal = permissions.includes('portal.akses');
    const isAnggotaAktif = a?.status === 'aktif';
    const isAnggotaNonaktif = a?.status === 'nonaktif';
    const canBukaPortal = Boolean(a) && isAnggotaAktif && punyaIzinPortal;
    const canAktivasiPortal = Boolean(a) && isAnggotaNonaktif && punyaIzinPortal;
    const daftarRekening = a?.rekening ?? [];

    function namaPeran(role) {
        if (role === 'pengawas' && pengguna?.label_jabatan_pengawas) {
            return pengguna.label_jabatan_pengawas;
        }
        return labelRole[role] ?? role;
    }

    function salinNomorRekening(noRekening, id) {
        if (!noRekening || !navigator.clipboard) return;
        navigator.clipboard.writeText(noRekening);
        setCopiedRekId(id);
        setTimeout(() => setCopiedRekId(null), 1800);
    }

    const ledgerItems = ringkasan && a ? [
        {
            key: 'simpanan',
            variant: 'anchor',
            label: 'Total Simpanan',
            value: formatRupiah(ringkasan.simpanan_total),
            icon: PiggyBank,
            meta: (
                <span className="inline-flex items-center gap-1.5 flex-wrap">
                    <span>Pokok <PrivacyWrapper>{formatRupiah(ringkasan.simpanan_pokok)}</PrivacyWrapper></span>
                    <span className="text-slate-400">•</span>
                    <span>Wajib <PrivacyWrapper>{formatRupiah(ringkasan.simpanan_wajib)}</PrivacyWrapper></span>
                </span>
            ),
        },
        {
            key: 'tabungan',
            label: 'Tabungan Sukarela',
            value: ringkasan.tabungan?.ada ? formatRupiah(ringkasan.tabungan.saldo) : 'Belum Buka',
            privacy: Boolean(ringkasan.tabungan?.ada),
            tone: 'sky',
            icon: Wallet,
            meta: ringkasan.tabungan?.ada
                ? `Rek. ${ringkasan.tabungan.no_rekening} • ${ringkasan.tabungan.suku_bunga}% p.a.`
                : 'Dapat dibuka melalui Portal Anggota',
        },
        {
            key: 'pinjaman',
            label: 'Tagihan Pinjaman Aktif',
            value: formatRupiah(ringkasan.sisa_tagihan_pinjaman ?? 0),
            privacy: true,
            tone: ringkasan.pinjaman_aktif > 0 ? 'amber' : 'green',
            icon: HandCoins,
            meta: ringkasan.pinjaman_aktif > 0
                ? `${ringkasan.pinjaman_aktif} pinjaman berjalan • sisa ${ringkasan.sisa_cicilan_pinjaman}x cicilan`
                : `Bebas tanggungan (${ringkasan.pinjaman_total} riwayat)`,
        },
        {
            key: 'plafon',
            label: 'Plafon Maksimal Pinjaman',
            value: formatRupiah(ringkasan.plafon_pinjaman ?? 0),
            privacy: true,
            tone: 'navy',
            icon: Gauge,
            meta: a.lama_keanggotaan_label
                ? `Masa anggota: ${a.lama_keanggotaan_label}`
                : 'Sesuai masa keanggotaan',
        },
    ] : [];

    return (
        <AppLayout>
            <Head title="Profil Saya" />

            <PageHeader
                title="Profil Saya"
                subtitle="Identitas akun, portal layanan pribadi anggota, dan pengaturan keamanan"
            />

            {/* 1. Executive Identity & Portal Anggota Gateway */}
            <Card padding="lg" className="mb-5">
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div className="flex items-start sm:items-center gap-4 sm:gap-5 min-w-0">
                        <FotoAnggota
                            nama={pengguna?.name}
                            fotoUrl={a?.foto_url}
                            ukuran="xl"
                            className="ring-2 ring-slate-100"
                        />

                        <div className="min-w-0 flex-1">
                            <div className="flex flex-wrap items-center gap-2.5">
                                <h2 className="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight truncate">
                                    {pengguna?.name}
                                </h2>
                                {a?.status ? (
                                    <StatusBadge status={a.status} />
                                ) : (
                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        Akun Sistem
                                    </span>
                                )}
                            </div>

                            <div className="flex flex-wrap items-center gap-2 mt-2.5">
                                {roles.map((r) => (
                                    <span
                                        key={r}
                                        className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-navy text-white"
                                    >
                                        {namaPeran(r)}
                                    </span>
                                ))}
                            </div>

                            <div className="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2.5 text-xs text-slate-500">
                                {pengguna?.no_karyawan && (
                                    <span>
                                        NIK <strong className="font-mono text-slate-700">{pengguna.no_karyawan}</strong>
                                    </span>
                                )}
                                {a?.no_anggota && (
                                    <>
                                        <span className="text-slate-300" aria-hidden="true">•</span>
                                        <span>
                                            No. Anggota <strong className="font-mono text-slate-700">{a.no_anggota}</strong>
                                        </span>
                                    </>
                                )}
                                {(a?.jabatan || a?.cabang) && (
                                    <>
                                        <span className="text-slate-300" aria-hidden="true">•</span>
                                        <span>
                                            {[a.jabatan, a.cabang].filter(Boolean).join(' — ')}
                                        </span>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Pintu Masuk Utama ke Portal Anggota (Menggantikan Menu Sidebar) */}
                    {(canBukaPortal || canAktivasiPortal || !a) && (
                        <div className="lg:border-l lg:border-slate-200/80 lg:pl-6 pt-4 lg:pt-0 border-t lg:border-t-0 border-slate-100 flex flex-col sm:flex-row lg:flex-col sm:items-center lg:items-end justify-between gap-3 shrink-0 lg:max-w-[270px]">
                            {canBukaPortal && (
                                <>
                                    <div className="lg:text-right">
                                        <p className="text-xs font-bold text-slate-800">Layanan Mandiri Anggota</p>
                                        <p className="text-xs text-slate-500 mt-0.5 leading-relaxed">
                                            Ajukan pinjaman, kelola tabungan, &amp; pantau angsuran pribadi Anda.
                                        </p>
                                    </div>
                                    <Link
                                        href={route('portal.dashboard')}
                                        className={`${buttonBase} ${buttonVariants.secondary} ${buttonSizes.md} shrink-0`}
                                    >
                                        <span>Buka Portal Anggota</span>
                                        <ArrowUpRight size={15} aria-hidden="true" />
                                    </Link>
                                </>
                            )}

                            {canAktivasiPortal && (
                                <>
                                    <div className="lg:text-right">
                                        <p className="text-xs font-bold text-amber-800">Keanggotaan Belum Aktif</p>
                                        <p className="text-xs text-slate-500 mt-0.5 leading-relaxed">
                                            Ajukan aktivasi agar dapat mengakses layanan simpan pinjam pribadi.
                                        </p>
                                    </div>
                                    <Link
                                        href={route('portal.aktivasi.landing')}
                                        className={`${buttonBase} ${buttonVariants.primary} ${buttonSizes.md} shrink-0`}
                                    >
                                        <span>Aktivasi Keanggotaan</span>
                                        <ArrowUpRight size={15} aria-hidden="true" />
                                    </Link>
                                </>
                            )}

                            {!a && (
                                <div className="lg:text-right">
                                    <p className="text-xs font-bold text-slate-700">Akun Administratif</p>
                                    <p className="text-xs text-slate-500 mt-0.5 leading-relaxed">
                                        Akun pengelola sistem tanpa profil keanggotaan koperasi.
                                    </p>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </Card>

            {/* 2. Posisi Finansial Pribadi (Unified Ledger Strip + Privacy Mode Aware) */}
            {ledgerItems.length > 0 && (
                <div className="mb-5 space-y-3">
                    <UnifiedLedgerStrip columns={4} items={ledgerItems} className="!mb-0" />

                    {ringkasan?.antrean_aktif?.length > 0 && (
                        <Card padding="sm" className="border-l-4 border-l-amber-500">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 px-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="inline-flex items-center gap-1.5 text-xs font-bold text-amber-800">
                                        <Clock size={14} aria-hidden="true" />
                                        Pengajuan Pribadi Diproses:
                                    </span>
                                    {ringkasan.antrean_aktif.map((item) => (
                                        <span
                                            key={item.kode}
                                            className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200/80"
                                        >
                                            {item.label} ({item.jumlah})
                                        </span>
                                    ))}
                                </div>
                                {canBukaPortal && (
                                    <Link
                                        href={route('portal.dashboard')}
                                        className="inline-flex items-center gap-1 text-xs font-semibold text-brand-navy hover:underline shrink-0"
                                    >
                                        <span>Pantau di Portal Anggota</span>
                                        <ArrowUpRight size={13} aria-hidden="true" />
                                    </Link>
                                )}
                            </div>
                        </Card>
                    )}
                </div>
            )}

            {/* 3. Detail Kepegawaian, Rekening Pencairan & Keamanan Akun */}
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-5">
                {/* Kiri: Informasi Kepegawaian & Keanggotaan */}
                <Card padding="lg" className="lg:col-span-7 flex flex-col justify-between">
                    <div>
                        <div className="flex items-center justify-between gap-2 pb-4 border-b border-slate-100">
                            <div>
                                <h3 className="text-base font-bold text-slate-900">
                                    {a ? 'Informasi Kepegawaian & Keanggotaan' : 'Informasi Akun Sistem'}
                                </h3>
                                <p className="text-xs text-slate-500 mt-0.5">
                                    {a ? 'Struktur penempatan kerja, masa keanggotaan, dan kontak terdaftar' : 'Identitas login dan hak akses sistem'}
                                </p>
                            </div>
                            <span className="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600">
                                Read-only
                            </span>
                        </div>

                        {a ? (
                            <div className="space-y-6 mt-5">
                                <div>
                                    <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3.5">
                                        Penempatan &amp; Organisasi
                                    </h4>
                                    <dl className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                                        <FieldRingkas label="Perusahaan" value={a.perusahaan} />
                                        <FieldRingkas label="Unit Bisnis" value={a.unit_bisnis} />
                                        <FieldRingkas label="Cabang" value={a.cabang} />
                                        <FieldRingkas label="Departemen" value={a.department} />
                                        <FieldRingkas label="Divisi" value={a.divisi} />
                                        <FieldRingkas label="Jabatan" value={a.jabatan} />
                                    </dl>
                                </div>

                                <div className="pt-5 border-t border-slate-100">
                                    <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3.5">
                                        Keanggotaan &amp; Kontak
                                    </h4>
                                    <dl className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                                        <FieldRingkas label="Tanggal Mulai Kerja" value={a.tanggal_mulai_kerja} />
                                        <FieldRingkas label="Tanggal Menjadi Anggota" value={a.tanggal_jadi_anggota} />
                                        <FieldRingkas label="Masa Keanggotaan" value={a.lama_keanggotaan_label} />
                                        <FieldRingkas label="Email Akun" value={pengguna?.email} copyValue={pengguna?.email} />
                                        <FieldRingkas label="No. Handphone" value={a.no_hp} mono copyValue={a.no_hp} />
                                        <FieldRingkas label="Alamat Domisili" value={a.alamat} colSpan={2} />
                                    </dl>
                                </div>
                            </div>
                        ) : (
                            <dl className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 mt-5">
                                <FieldRingkas label="Nama Pengguna" value={pengguna?.name} />
                                <FieldRingkas label="NIK / Username" value={pengguna?.no_karyawan} mono />
                                <FieldRingkas label="Email Akun" value={pengguna?.email} copyValue={pengguna?.email} />
                                <FieldRingkas label="Peran Akses" value={roles.map(namaPeran).join(', ') || '-'} />
                            </dl>
                        )}
                    </div>

                    <div className="mt-6 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                        <div className="flex items-center gap-2">
                            <ShieldCheck size={16} className="text-brand-navy shrink-0" aria-hidden="true" />
                            <span>Identitas dan mutasi kepegawaian dikelola terpusat oleh <strong>HRIS GATE SSO</strong>.</span>
                        </div>
                        {a?.gate_synced_at && (
                            <span className="text-[11px] text-slate-400">
                                Sinkron terakhir: {a.gate_synced_at}
                            </span>
                        )}
                    </div>
                </Card>

                {/* Kanan: Rekening Pencairan & Keamanan Akun */}
                <div className="lg:col-span-5 space-y-5">
                    {a && (
                        <Card padding="lg">
                            <div className="flex items-center justify-between gap-3 pb-4 border-b border-slate-100">
                                <div>
                                    <h3 className="text-base font-bold text-slate-900">Rekening Pencairan</h3>
                                    <p className="text-xs text-slate-500 mt-0.5">Rekening bank tujuan pencairan pribadi</p>
                                </div>
                                {canBukaPortal && (
                                    <Link
                                        href={route('portal.profil')}
                                        className="inline-flex items-center gap-1 text-xs font-semibold text-brand-navy hover:underline shrink-0"
                                    >
                                        <span>Kelola di Portal</span>
                                        <ArrowUpRight size={13} aria-hidden="true" />
                                    </Link>
                                )}
                            </div>

                            {daftarRekening.length === 0 ? (
                                <div className="mt-4 rounded-xl border border-dashed border-slate-200 py-6 px-4 text-center">
                                    <CreditCard size={20} className="mx-auto text-slate-400 mb-2" aria-hidden="true" />
                                    <p className="text-sm font-semibold text-slate-700">Belum Ada Rekening Terdaftar</p>
                                    <p className="text-xs text-slate-500 mt-1">
                                        Tambahkan rekening tujuan melalui menu Profil di Portal Anggota.
                                    </p>
                                </div>
                            ) : (
                                <ul className="divide-y divide-slate-100 mt-2">
                                    {daftarRekening.map((rek) => (
                                        <li key={rek.id} className="py-3.5 first:pt-2 last:pb-0">
                                            <div className="flex items-center justify-between gap-2">
                                                <div className="flex items-center gap-2 min-w-0">
                                                    <span className="text-sm font-bold text-slate-900 uppercase tracking-wide truncate">
                                                        {rek.nama_bank}
                                                    </span>
                                                    {rek.is_default && (
                                                        <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-brand-green-dark border border-emerald-200/80 shrink-0">
                                                            <Star size={10} className="fill-current" aria-hidden="true" />
                                                            Utama
                                                        </span>
                                                    )}
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => salinNomorRekening(rek.no_rekening, rek.id)}
                                                    className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 bg-white text-[11px] font-semibold text-slate-600 hover:text-brand-navy hover:border-slate-300 transition-colors cursor-pointer shrink-0"
                                                >
                                                    {copiedRekId === rek.id ? (
                                                        <>
                                                            <Check size={12} className="text-brand-green-dark" />
                                                            <span className="text-brand-green-dark">Tersalin</span>
                                                        </>
                                                    ) : (
                                                        <>
                                                            <Copy size={12} />
                                                            <span>Salin</span>
                                                        </>
                                                    )}
                                                </button>
                                            </div>
                                            <p className="font-mono text-sm font-semibold text-slate-800 mt-1">
                                                {rek.no_rekening}
                                            </p>
                                            <p className="text-xs text-slate-500 mt-0.5">a.n. {rek.atas_nama}</p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Card>
                    )}

                    <Card padding="lg">
                        <div className="pb-4 border-b border-slate-100">
                            <h3 className="text-base font-bold text-slate-900">Keamanan &amp; Privasi</h3>
                            <p className="text-xs text-slate-500 mt-0.5">Kontrol PIN 6-digit dan penyamaran nominal layar</p>
                        </div>

                        <div className="divide-y divide-slate-100 mt-2">
                            <div className="py-3.5 first:pt-2 flex items-center justify-between gap-3">
                                <div className="min-w-0">
                                    <div className="flex items-center gap-2">
                                        <p className="text-sm font-semibold text-slate-900">PIN Keamanan 6-Digit</p>
                                        <span
                                            className={`px-2 py-0.5 rounded-full text-[10px] font-bold border ${
                                                pengguna?.has_pin
                                                    ? 'bg-emerald-50 text-brand-green-dark border-emerald-200/80'
                                                    : 'bg-amber-50 text-amber-800 border-amber-200/80'
                                            }`}
                                        >
                                            {pengguna?.has_pin ? 'Aktif' : 'Belum Diatur'}
                                        </span>
                                    </div>
                                    <p className="text-xs text-slate-500 mt-0.5">
                                        Dipakai untuk Screen Lock &amp; tanda tangan pengajuan mandiri.
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    variant="neutral"
                                    size="sm"
                                    onClick={() => setPinDrawerOpen(true)}
                                    className="shrink-0"
                                >
                                    <KeyRound size={14} aria-hidden="true" />
                                    {pengguna?.has_pin ? 'Ubah PIN' : 'Atur PIN'}
                                </Button>
                            </div>

                            <div className="py-3.5 flex items-center justify-between gap-3">
                                <div className="min-w-0">
                                    <div className="flex items-center gap-2">
                                        <p className="text-sm font-semibold text-slate-900">Mode Privasi Nominal</p>
                                        <span
                                            className={`px-2 py-0.5 rounded-full text-[10px] font-bold border ${
                                                isPrivacyActive
                                                    ? 'bg-emerald-50 text-brand-green-dark border-emerald-200/80'
                                                    : 'bg-slate-100 text-slate-600 border-slate-200'
                                            }`}
                                        >
                                            {isPrivacyActive ? 'Aktif' : 'Nonaktif'}
                                        </span>
                                    </div>
                                    <p className="text-xs text-slate-500 mt-0.5">
                                        Samarkan nominal Rupiah di layar (pintasan <kbd className="font-mono text-[11px]">Ctrl+Shift+P</kbd>).
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    variant="neutral"
                                    size="sm"
                                    onClick={togglePrivacy}
                                    className="shrink-0"
                                >
                                    {isPrivacyActive ? <Eye size={14} aria-hidden="true" /> : <EyeOff size={14} aria-hidden="true" />}
                                    {isPrivacyActive ? 'Tampilkan' : 'Samarkan'}
                                </Button>
                            </div>

                            <div className="py-3.5 last:pb-0">
                                <p className="text-sm font-semibold text-slate-900">Autentikasi Terpusat GATE</p>
                                <p className="text-xs text-slate-500 mt-0.5 leading-relaxed">
                                    Kata sandi akun tidak disimpan secara lokal dan dikelola langsung melalui sistem Single Sign-On GATE.
                                </p>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <UbahPinDrawer
                show={pinDrawerOpen}
                onClose={() => setPinDrawerOpen(false)}
                hasPin={Boolean(pengguna?.has_pin)}
            />
        </AppLayout>
    );
}
