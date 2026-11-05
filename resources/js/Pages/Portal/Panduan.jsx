import { useState, useEffect, useMemo } from 'react';
import {
    Plus,
    Repeat,
    TrendingUp,
    HelpCircle,
    ArrowRight,
    ChevronDown,
    AlertCircle,
    CheckCircle2,
    Info,
    Check,
    X,
    Search,
    Compass,
    BookOpen,
    ShieldCheck,
} from 'lucide-react';

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2';

function MiniButton({ label, tone = 'primary', icon: Icon }) {
    const tones = {
        primary: 'bg-emerald-600 text-white border-emerald-600 shadow-xs',
        outline: 'border border-emerald-600/40 text-emerald-800 bg-emerald-50/80',
        ghost: 'bg-slate-50 border border-slate-200 text-slate-700',
        navy: 'bg-slate-900 text-white border-slate-900',
    };
    return (
        <div className="inline-flex max-w-full items-center p-1.5 rounded-xl bg-slate-50 border border-slate-200/80">
            <span
                className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border ${tones[tone]}`}
            >
                {Icon && <Icon size={13} strokeWidth={2.2} />}
                {label}
            </span>
        </div>
    );
}

function MiniInput({ prefix, placeholder }) {
    return (
        <span className="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs bg-white border border-slate-200 shadow-2xs">
            {prefix && <span className="text-slate-400 font-semibold">{prefix}</span>}
            <span className="text-slate-500 font-medium tabular-nums">{placeholder}</span>
            <span className="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full bg-emerald-50 text-emerald-600">
                <Check size={11} strokeWidth={2.5} />
            </span>
        </span>
    );
}

function MiniPills({ items, active = 0 }) {
    return (
        <span className="inline-flex flex-wrap items-center gap-1.5">
            {items.map((it, i) => (
                <span
                    key={i}
                    className={`inline-flex items-center justify-center min-w-[32px] px-2 py-1 rounded-lg text-xs font-semibold border ${
                        i === active
                            ? 'border-emerald-600 bg-emerald-50 text-emerald-800'
                            : 'border-slate-200 bg-white text-slate-500'
                    }`}
                >
                    {it} bln
                </span>
            ))}
        </span>
    );
}

function MiniTimeline({ steps }) {
    return (
        <span className="inline-flex flex-wrap items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-50 border border-slate-200/90">
            {steps.map((s, i) => (
                <span key={i} className="inline-flex items-center gap-1.5">
                    <span
                        className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-semibold ${
                            i === steps.length - 1
                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/80'
                                : 'bg-white text-slate-700 border border-slate-200/80'
                        }`}
                    >
                        <span className="text-[10px] text-slate-400 font-bold">{i + 1}.</span>
                        {s}
                    </span>
                    {i < steps.length - 1 && (
                        <ArrowRight size={11} className="text-slate-400 shrink-0" />
                    )}
                </span>
            ))}
        </span>
    );
}

function MiniList({ items }) {
    return (
        <div className="inline-block w-full sm:w-auto sm:min-w-[250px] px-3.5 py-2 rounded-xl bg-slate-50/80 border border-slate-200/90">
            {items.map((it, i) => (
                <div
                    key={i}
                    className="flex items-center justify-between gap-4 text-xs py-1.5 border-b border-slate-200/60 last:border-0"
                >
                    <span className="text-slate-500">{it.label}</span>
                    <span className="font-semibold text-slate-800 tabular-nums text-right">
                        {it.value}
                    </span>
                </div>
            ))}
        </div>
    );
}

function Langkah({ nomor, judul, deskripsi, mockup, last = false }) {
    return (
        <li className="relative flex gap-3.5">
            <div className="flex flex-col items-center shrink-0">
                <span className="w-7 h-7 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-bold shadow-2xs relative z-10">
                    {nomor}
                </span>

                {!last && (
                    <span
                        aria-hidden="true"
                        className="w-px flex-1 my-1 bg-slate-200"
                    />
                )}
            </div>

            <div className={`flex-1 min-w-0 pt-0.5 ${last ? 'pb-1' : 'pb-5'}`}>
                <p className="text-sm font-semibold text-slate-900 leading-snug">{judul}</p>
                <p className="text-xs sm:text-[13px] text-slate-600 mt-1 leading-relaxed">
                    {deskripsi}
                </p>
                {mockup && <div className="mt-2.5">{mockup}</div>}
            </div>
        </li>
    );
}

function Catatan({ items }) {
    return (
        <div className="bg-amber-50/70 border border-amber-200/80 rounded-xl p-3.5 sm:p-4 flex items-start gap-3">
            <span className="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                <AlertCircle size={15} strokeWidth={2.2} />
            </span>
            <div className="min-w-0 flex-1">
                <p className="text-xs font-bold uppercase tracking-wider text-amber-900 mb-1.5">
                    Ketentuan Penting
                </p>
                <ul className="space-y-1.5">
                    {items.map((it, i) => (
                        <li
                            key={i}
                            className="text-xs sm:text-[13px] text-amber-950/90 leading-relaxed flex gap-2"
                        >
                            <span className="text-amber-600 font-bold shrink-0">&bull;</span>
                            <span>{it}</span>
                        </li>
                    ))}
                </ul>
            </div>
        </div>
    );
}

const TATA_CARA = [
    {
        key: 'pinjaman',
        label: 'Ajukan Pinjaman',
        shortDesc: '9 langkah pengajuan',
        icon: Plus,
        tone: 'emerald',
        intro: 'Pinjaman diajukan langsung dari menu Aksi Cepat di Beranda. Sistem menghitung simulasi angsuran secara otomatis sesuai ketentuan tenor koperasi.',
        langkah: [
            {
                judul: 'Pilih menu "Ajukan pinjaman" di Beranda',
                deskripsi:
                    'Di bawah Kartu Anggota Digital, klik kartu menu "Ajukan pinjaman" (bertanda Tersedia) atau tombol pintasan pada kartu pinjaman.',
                mockup: <MiniButton label="Ajukan pinjaman" icon={Plus} />,
            },
            {
                judul: 'Masukkan nominal pinjaman',
                deskripsi:
                    'Ketik nominal atau pilih chip nominal cepat. Sistem langsung memvalidasi terhadap Limit Tersedia Anda.',
                mockup: <MiniInput prefix="Rp" placeholder="1.500.000" />,
            },
            {
                judul: 'Pilih tenor cicilan',
                deskripsi:
                    'Pilih durasi cicilan (bulan) yang tersedia sesuai batas maksimal nominal pinjaman Anda.',
                mockup: <MiniPills items={[1, 2, 3, 6]} active={3} />,
            },
            {
                judul: 'Tinjau simulasi rincian angsuran',
                deskripsi:
                    'Periksa rincian pokok, jasa/bunga menurun, estimasi cicilan bulanan, dan total pembayaran hingga lunas.',
                mockup: (
                    <MiniList
                        items={[
                            { label: 'Nominal pinjaman', value: 'Rp 1.500.000' },
                            { label: 'Tenor cicilan', value: '6 bulan' },
                            { label: 'Estimasi / bulan', value: 'Rp 265.000' },
                        ]}
                    />
                ),
            },
            {
                judul: 'Isi keperluan & rekening pencairan',
                deskripsi:
                    'Tuliskan tujuan peminjaman (minimal 5 karakter) dan pastikan nomor rekening bank pencairan sudah sesuai.',
                mockup: null,
            },
            {
                judul: 'Setujui Syarat & Ketentuan serta konfirmasi PIN',
                deskripsi:
                    'Baca dokumen Syarat & Ketentuan, centang persetujuan, lalu masukkan 6 digit PIN Keamanan akun Anda untuk otorisasi pengajuan.',
                mockup: (
                    <MiniButton
                        label="Otorisasi PIN & Kirim Pengajuan"
                        tone="outline"
                        icon={ShieldCheck}
                    />
                ),
            },
            {
                judul: 'Pantau alur persetujuan 3 tahap di Beranda',
                deskripsi:
                    'Status pengajuan tampil langsung di Beranda (Admin → Bendahara → Ketua Koperasi) dan notifikasi pencairan dikirimkan melalui WhatsApp.',
                mockup: <MiniTimeline steps={['Admin', 'Bendahara', 'Ketua', 'Dana Cair']} />,
            },
        ],
        catatan: [
            'Jika masih memiliki pinjaman berjalan, pengajuan baru dibuka saat sisa angsuran maksimal 2 bulan (selama sisa limit mencukupi).',
            'Anggota dengan masa keanggotaan di bawah 1 tahun wajib melunasi pinjaman sebelumnya terlebih dahulu.',
            'Pengajuan yang sudah dikirim tidak dapat dibatalkan sendiri; keputusan berada pada alur verifikasi pengurus.',
        ],
    },
    {
        key: 'limit',
        label: 'Tambah Limit',
        shortDesc: 'Kenaikan batas limit',
        icon: TrendingUp,
        tone: 'rose',
        intro: 'Apabila batas limit pinjaman Anda saat ini belum mencukupi kebutuhan, Anda dapat mengajukan penambahan limit untuk ditinjau Bendahara dan Ketua Koperasi.',
        langkah: [
            {
                judul: 'Pilih menu "Tambah limit" di Beranda',
                deskripsi:
                    'Klik kartu "Tambah limit" pada baris Menu Aksi Cepat di bawah Kartu Anggota Digital.',
                mockup: <MiniButton label="Tambah limit" tone="outline" icon={TrendingUp} />,
            },
            {
                judul: 'Isi nominal limit baru yang diminta',
                deskripsi:
                    'Halaman menampilkan limit aktif Anda saat ini sebagai pembanding. Masukkan nominal limit baru yang Anda ajukan.',
                mockup: <MiniInput prefix="Rp" placeholder="5.000.000" />,
            },
            {
                judul: 'Tuliskan alasan pengajuan kenaikan limit',
                deskripsi:
                    'Jelaskan alasan atau kebutuhan penambahan limit (minimal 10 karakter) sebagai bahan pertimbangan pengurus.',
                mockup: null,
            },
            {
                judul: 'Kirim & pantau hasil verifikasi 2 tahap',
                deskripsi:
                    'Bendahara memverifikasi dan memberikan usulan nominal, lalu Ketua Koperasi menetapkan limit final. Setelah disetujui, limit baru langsung aktif.',
                mockup: <MiniTimeline steps={['Bendahara', 'Ketua Koperasi', 'Limit Aktif']} />,
            },
        ],
        catatan: [
            'Dalam satu waktu hanya dapat memiliki 1 pengajuan penambahan limit yang sedang berjalan.',
            'Penambahan limit tidak dapat diajukan apabila Anda sedang memiliki pengajuan pinjaman yang masih diproses.',
            'Nominal limit yang disetujui dapat disesuaikan oleh Bendahara maupun Ketua Koperasi berdasarkan evaluasi kelayakan.',
        ],
    },
    {
        key: 'tenor',
        label: 'Ubah Tenor',
        shortDesc: 'Percepat / perpanjang',
        icon: Repeat,
        tone: 'teal',
        intro: 'Pinjaman aktif dapat diajukan percepatan cicilan, perpanjangan tenor, atau pelunasan sekaligus. Fasilitas ini berlaku 1 kali untuk setiap pinjaman aktif.',
        langkah: [
            {
                judul: 'Pilih menu "Ubah tenor" di Beranda',
                deskripsi:
                    'Klik kartu "Ubah tenor" pada Menu Aksi Cepat. Menu ini aktif otomatis saat Anda memiliki pinjaman berjalan yang belum pernah diubah tenornya.',
                mockup: <MiniButton label="Ubah tenor" tone="ghost" icon={Repeat} />,
            },
            {
                judul: 'Pilih jenis perubahan yang diinginkan',
                deskripsi:
                    'Pilih salah satu dari tiga opsi penyesuaian jadwal angsuran sesuai kondisi keuangan Anda.',
                mockup: (
                    <MiniList
                        items={[
                            { label: 'Percepat Cicilan', value: 'Kurangi bulan sisa' },
                            { label: 'Perpanjang Tenor', value: 'Ringankan cicilan/bln' },
                            { label: 'Pelunasan Dipercepat', value: 'Sisa pokok + bunga 1 bln' },
                        ]}
                    />
                ),
            },
            {
                judul: 'Tentukan tenor baru & tinjau simulasi ulang',
                deskripsi:
                    'Untuk percepat atau perpanjang, pilih jumlah bulan baru dan periksa perbandingan cicilan bulanan sebelum dan sesudah perubahan.',
                mockup: <MiniPills items={[4, 6, 8, 10]} active={1} />,
            },
            {
                judul: 'Isi alasan perubahan & kirim pengajuan',
                deskripsi:
                    'Tuliskan alasan pengajuan (minimal 10 karakter) lalu kirim. Perubahan jadwal akan berlaku setelah disetujui Bendahara dan Ketua Koperasi.',
                mockup: <MiniTimeline steps={['Admin / Bendahara', 'Ketua', 'Jadwal Baru']} />,
            },
        ],
        catatan: [
            'Hak perubahan tenor / pelunasan dipercepat hanya berlaku 1 kali per pinjaman aktif.',
            'Untuk opsi Pelunasan Dipercepat (Lunas Total), Anda cukup melunasi sisa pokok ditambah bunga 1 bulan berjalan.',
        ],
    },
];

const FAQ = [
    {
        kategori: 'Pengajuan Pinjaman',
        short: 'Pinjaman',
        items: [
            {
                q: 'Berapa lama proses persetujuan pengajuan pinjaman saya?',
                a: 'Pengajuan melalui 3 tahap berjenjang: Admin (verifikasi kelengkapan), Bendahara (pemeriksaan limit & kas), dan Ketua Koperasi (persetujuan & pencairan). Rata-rata memerlukan waktu 1–3 hari kerja per tahap tergantung antrean.',
            },
            {
                q: 'Kenapa pengajuan pinjaman saya ditolak?',
                a: 'Penyebab umum meliputi: nominal melebihi limit tersedia, persyaratan masa keanggotaan atau sisa angsuran belum terpenuhi, atau penyesuaian ketersediaan kas operasional. Alasan penolakan dari pengurus dapat dilihat langsung pada pemberitahuan di Beranda dan halaman Riwayat.',
            },
            {
                q: 'Berapa limit pinjaman maksimum yang bisa saya ajukan?',
                a: 'Batas limit mengikuti profil keanggotaan Anda dan tertera pada bagian "Limit Tersedia" di Kartu Anggota Digital. Anda juga dapat melihat tabel batas tenor maksimal pada kartu "Ketentuan pinjaman" (baris yang bertanda "Limit Anda").',
            },
            {
                q: 'Apakah saya boleh memiliki lebih dari satu pinjaman aktif sekaligus?',
                a: 'Bisa, apabila masa keanggotaan sudah memenuhi syarat reloan, sisa angsuran pinjaman berjalan tersisa maksimal 2 bulan, dan Anda masih memiliki sisa limit yang mencukupi.',
            },
            {
                q: 'Apakah pengajuan bisa dibatalkan setelah dikirim?',
                a: 'Tidak bisa. Pengajuan yang sudah diotorisasi dengan PIN dan terkirim akan langsung masuk ke antrean pemeriksaan pengurus.',
            },
            {
                q: 'Bagaimana jika saya resign sementara pinjaman masih berjalan?',
                a: 'Sisa kewajiban pinjaman akan diperhitungkan dengan saldo simpanan Anda. Apabila masih terdapat kekurangan pelunasan (shortfall), penyelesaian dilakukan melalui Bendahara sebelum proses bebas tanggungan selesai.',
            },
        ],
    },
    {
        kategori: 'Perubahan Tenor & Pelunasan',
        short: 'Tenor',
        items: [
            {
                q: 'Berapa kali saya bisa mengajukan perubahan tenor?',
                a: 'Satu kali untuk setiap pinjaman aktif. Setelah digunakan, fasilitas perubahan tenor untuk pinjaman tersebut terkunci dan baru tersedia kembali pada pinjaman baru berikutnya.',
            },
            {
                q: 'Apa bedanya "Percepat" dengan "Pelunasan Dipercepat"?',
                a: 'Percepat: memperpendek sisa bulan angsuran sehingga cicilan bulanan berikutnya menjadi lebih besar. Pelunasan Dipercepat (Lunas Total): melunasi seluruh sisa pokok sekaligus dalam satu kali pembayaran ditambah bunga 1 bulan saja.',
            },
            {
                q: 'Kenapa menu "Ubah tenor" saya bertanda Nonaktif / Terkunci?',
                a: 'Menu Ubah Tenor hanya aktif jika Anda memiliki pinjaman yang sedang berjalan, tidak sedang dalam proses pengajuan pinjaman/tenor lain, dan hak perubahan pada pinjaman tersebut belum pernah dipakai. Ketuk kartu yang terkunci untuk melihat alasan spesifiknya.',
            },
        ],
    },
    {
        kategori: 'Pengajuan Tambah Limit',
        short: 'Limit',
        items: [
            {
                q: 'Kapan saya bisa mengajukan kenaikan limit?',
                a: 'Setiap saat selama Anda sudah memenuhi syarat eligibilitas pinjaman, tidak sedang memiliki pengajuan pinjaman yang diproses, dan tidak ada pengajuan kenaikan limit lain yang masih diverifikasi.',
            },
            {
                q: 'Kenapa nominal limit yang disetujui berbeda dari yang saya minta?',
                a: 'Bendahara melakukan evaluasi awal dan mengajukan usulan nominal, kemudian Ketua Koperasi menetapkan nominal akhir sesuai kebijakan plafon koperasi serta riwayat keanggotaan Anda.',
            },
            {
                q: 'Apakah limit baru langsung aktif setelah disetujui?',
                a: 'Ya, begitu disetujui oleh Ketua Koperasi, angka Limit Tersedia di Kartu Anggota Anda langsung bertambah dan siap digunakan untuk pengajuan pinjaman berikutnya.',
            },
        ],
    },
];

function FAQItem({ q, a, defaultOpen = false }) {
    const [open, setOpen] = useState(defaultOpen);
    return (
        <div
            className={`border rounded-xl transition-all duration-150 ${
                open
                    ? 'border-emerald-600/30 bg-emerald-50/35 shadow-2xs'
                    : 'border-slate-200/90 bg-white hover:border-slate-300'
            }`}
        >
            <button
                type="button"
                onClick={() => setOpen(!open)}
                aria-expanded={open}
                className={`w-full flex items-start justify-between gap-3 p-3.5 text-left rounded-xl ${focusRing}`}
            >
                <span className="text-xs sm:text-sm font-semibold text-slate-800 leading-snug">
                    {q}
                </span>
                <span
                    className={`w-6 h-6 rounded-full flex items-center justify-center shrink-0 transition-transform duration-200 ${
                        open
                            ? 'rotate-180 bg-emerald-100 text-emerald-700'
                            : 'bg-slate-100 text-slate-500'
                    }`}
                >
                    <ChevronDown size={14} strokeWidth={2.4} />
                </span>
            </button>
            {open && (
                <div className="px-3.5 pb-3.5 pt-0">
                    <div className="pt-2 border-t border-emerald-600/10">
                        <p className="text-xs sm:text-[13px] text-slate-600 leading-relaxed">
                            {a}
                        </p>
                    </div>
                </div>
            )}
        </div>
    );
}

function TabPanduan() {
    const [tab, setTab] = useState('pinjaman');
    const section = TATA_CARA.find((t) => t.key === tab) || TATA_CARA[0];

    const iconToneClasses = {
        emerald: {
            activeCard: 'border-emerald-600 bg-emerald-50/50 text-emerald-950 shadow-2xs',
            iconWrap: 'bg-emerald-100 text-emerald-700',
        },
        rose: {
            activeCard: 'border-rose-500 bg-rose-50/40 text-rose-950 shadow-2xs',
            iconWrap: 'bg-rose-100 text-rose-600',
        },
        teal: {
            activeCard: 'border-teal-600 bg-teal-50/45 text-teal-950 shadow-2xs',
            iconWrap: 'bg-teal-100 text-teal-700',
        },
    };

    return (
        <div className="space-y-5">
            {/* Topic Selector Cards */}
            <div className="grid grid-cols-3 gap-2 sm:gap-2.5" role="tablist" aria-label="Pilih topik tata cara">
                {TATA_CARA.map((t) => {
                    const Icon = t.icon;
                    const aktif = tab === t.key;
                    const style = iconToneClasses[t.tone] || iconToneClasses.emerald;
                    return (
                        <button
                            key={t.key}
                            type="button"
                            role="tab"
                            aria-selected={aktif}
                            onClick={() => setTab(t.key)}
                            className={`flex flex-col sm:flex-row items-start sm:items-center gap-2 p-2.5 sm:px-3 sm:py-2.5 rounded-xl border text-left transition-all ${focusRing} ${
                                aktif
                                    ? style.activeCard
                                    : 'border-slate-200/90 bg-white text-slate-600 hover:bg-slate-50/80 hover:border-slate-300'
                            }`}
                        >
                            <span
                                className={`w-7 h-7 rounded-lg flex items-center justify-center shrink-0 ${
                                    aktif ? style.iconWrap : 'bg-slate-100 text-slate-500'
                                }`}
                            >
                                <Icon size={15} strokeWidth={2.3} />
                            </span>
                            <span className="min-w-0">
                                <span className="block text-xs sm:text-[13px] font-bold leading-tight truncate">
                                    {t.label}
                                </span>
                                <span className="hidden sm:block text-[11px] text-slate-500 mt-0.5 truncate">
                                    {t.shortDesc}
                                </span>
                            </span>
                        </button>
                    );
                })}
            </div>

            {/* Topic Intro Banner */}
            <div className="flex items-start gap-3 bg-slate-50 border border-slate-200/80 rounded-xl p-3.5">
                <span className="w-7 h-7 rounded-lg bg-white border border-slate-200/80 text-slate-700 flex items-center justify-center shrink-0 mt-0.5 shadow-2xs">
                    <Info size={15} strokeWidth={2.2} />
                </span>
                <p className="text-xs sm:text-[13px] text-slate-600 leading-relaxed">
                    {section.intro}
                </p>
            </div>

            {/* Steps Timeline */}
            <ol className="pl-0.5">
                {section.langkah.map((l, i) => (
                    <Langkah
                        key={`${section.key}-${i}`}
                        nomor={i + 1}
                        judul={l.judul}
                        deskripsi={l.deskripsi}
                        mockup={l.mockup}
                        last={i === section.langkah.length - 1}
                    />
                ))}
            </ol>

            <Catatan items={section.catatan} />
        </div>
    );
}

function TabFAQ() {
    const [cari, setCari] = useState('');
    const [kategoriAktif, setKategoriAktif] = useState('semua');

    const daftarFilter = useMemo(() => {
        const qLower = cari.trim().toLowerCase();
        return FAQ.filter(
            (kat) => kategoriAktif === 'semua' || kat.short === kategoriAktif
        )
            .map((kat) => ({
                ...kat,
                items: kat.items.filter(
                    (it) =>
                        !qLower ||
                        it.q.toLowerCase().includes(qLower) ||
                        it.a.toLowerCase().includes(qLower)
                ),
            }))
            .filter((kat) => kat.items.length > 0);
    }, [cari, kategoriAktif]);

    return (
        <div className="space-y-4">
            {/* Search & Category Filter */}
            <div className="space-y-2.5">
                <div className="relative">
                    <Search
                        size={15}
                        className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"
                    />
                    <input
                        type="text"
                        value={cari}
                        onChange={(e) => setCari(e.target.value)}
                        placeholder="Cari pertanyaan (misal: limit, tenor, resign)..."
                        aria-label="Cari pertanyaan FAQ"
                        className="w-full pl-9 pr-8 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15 focus:outline-none transition-all"
                    />
                    {cari && (
                        <button
                            type="button"
                            onClick={() => setCari('')}
                            aria-label="Hapus pencarian"
                            className="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 rounded-md"
                        >
                            <X size={14} />
                        </button>
                    )}
                </div>

                <div className="flex items-center gap-1.5 overflow-x-auto pb-0.5 scrollbar-hide">
                    {[
                        { key: 'semua', label: 'Semua Topik' },
                        ...FAQ.map((f) => ({ key: f.short, label: f.short })),
                    ].map((pil) => {
                        const aktif = kategoriAktif === pil.key;
                        return (
                            <button
                                key={pil.key}
                                type="button"
                                onClick={() => setKategoriAktif(pil.key)}
                                className={`px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap transition-colors ${focusRing} ${
                                    aktif
                                        ? 'bg-slate-900 text-white'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200/70'
                                }`}
                            >
                                {pil.label}
                            </button>
                        );
                    })}
                </div>
            </div>

            {daftarFilter.length === 0 ? (
                <div className="py-10 text-center bg-slate-50/70 rounded-xl border border-dashed border-slate-200">
                    <p className="text-sm font-semibold text-slate-700">
                        Pertanyaan tidak ditemukan
                    </p>
                    <p className="text-xs text-slate-500 mt-1">
                        Coba gunakan kata kunci lain atau tampilkan semua topik.
                    </p>
                </div>
            ) : (
                <div className="space-y-5">
                    {daftarFilter.map((kat) => (
                        <div key={kat.kategori}>
                            <div className="flex items-center justify-between gap-2 mb-2">
                                <p className="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                    {kat.kategori}
                                </p>
                                <span className="text-[11px] font-medium text-slate-400">
                                    {kat.items.length} pertanyaan
                                </span>
                            </div>
                            <div className="space-y-2">
                                {kat.items.map((it, i) => (
                                    <FAQItem
                                        key={`${kat.kategori}-${i}`}
                                        q={it.q}
                                        a={it.a}
                                        defaultOpen={Boolean(cari.trim())}
                                    />
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

export default function Panduan({ onClose }) {
    const [mainTab, setMainTab] = useState('panduan');

    useEffect(() => {
        const prevOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.body.style.overflow = prevOverflow;
        };
    }, []);

    const ulangiTour = () => {
        onClose();
        window.setTimeout(
            () => window.dispatchEvent(new CustomEvent('start-dashboard-tour')),
            150
        );
    };

    const totalFaq = useMemo(
        () => FAQ.reduce((sum, k) => sum + k.items.length, 0),
        []
    );

    return (
        <div
            className="fixed inset-0 z-[400] bg-slate-950/55 backdrop-blur-[3px] flex items-end sm:items-center justify-center sm:p-4 animate-in fade-in duration-150"
            onClick={onClose}
            role="dialog"
            aria-modal="true"
            aria-labelledby="panduan-title"
        >
            <div
                className="bg-white rounded-t-2xl sm:rounded-2xl w-full max-w-2xl shadow-2xl border border-slate-200/80 max-h-[90dvh] sm:max-h-[86dvh] flex flex-col overflow-hidden"
                onClick={(e) => e.stopPropagation()}
            >
                {/* Mobile Drag Handle */}
                <div
                    className="sm:hidden pt-2.5 pb-1 flex justify-center shrink-0 bg-white"
                    aria-hidden="true"
                >
                    <span className="w-9 h-1 rounded-full bg-slate-300" />
                </div>

                {/* Modal Header */}
                <div className="px-4 sm:px-6 pt-3 sm:pt-5 pb-3.5 border-b border-slate-100 bg-white shrink-0">
                    <div className="flex items-start justify-between gap-3">
                        <div className="flex items-center gap-3 min-w-0">
                            <div className="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200/60 flex items-center justify-center shrink-0 text-emerald-700">
                                <BookOpen size={19} strokeWidth={2.2} />
                            </div>
                            <div className="min-w-0">
                                <h2
                                    id="panduan-title"
                                    className="text-base sm:text-lg font-bold text-slate-900 leading-tight"
                                >
                                    Tata Cara &amp; FAQ
                                </h2>
                                <p className="text-xs text-slate-500 mt-0.5 truncate">
                                    Panduan layanan pinjaman, kenaikan limit, dan perubahan tenor
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            onClick={onClose}
                            className={`w-8 h-8 inline-flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors shrink-0 rounded-lg ${focusRing}`}
                            aria-label="Tutup panduan"
                        >
                            <X size={18} />
                        </button>
                    </div>

                    {/* Segmented Main Switcher (Tata Cara vs FAQ) */}
                    <div className="mt-3.5 grid grid-cols-2 gap-1 p-1 bg-slate-100/90 rounded-xl border border-slate-200/60">
                        <button
                            type="button"
                            onClick={() => setMainTab('panduan')}
                            aria-pressed={mainTab === 'panduan'}
                            className={`flex items-center justify-center gap-2 py-2 px-3 rounded-lg text-xs sm:text-sm font-semibold transition-all ${focusRing} ${
                                mainTab === 'panduan'
                                    ? 'bg-white text-slate-900 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            <BookOpen size={14} className={mainTab === 'panduan' ? 'text-emerald-600' : 'text-slate-400'} />
                            <span>Tata Cara</span>
                            <span
                                className={`px-1.5 py-0.2 rounded-full text-[11px] font-bold ${
                                    mainTab === 'panduan'
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : 'bg-slate-200/70 text-slate-500'
                                }`}
                            >
                                3
                            </span>
                        </button>

                        <button
                            type="button"
                            onClick={() => setMainTab('faq')}
                            aria-pressed={mainTab === 'faq'}
                            className={`flex items-center justify-center gap-2 py-2 px-3 rounded-lg text-xs sm:text-sm font-semibold transition-all ${focusRing} ${
                                mainTab === 'faq'
                                    ? 'bg-white text-slate-900 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            <HelpCircle size={14} className={mainTab === 'faq' ? 'text-emerald-600' : 'text-slate-400'} />
                            <span>Tanya Jawab (FAQ)</span>
                            <span
                                className={`px-1.5 py-0.2 rounded-full text-[11px] font-bold ${
                                    mainTab === 'faq'
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : 'bg-slate-200/70 text-slate-500'
                                }`}
                            >
                                {totalFaq}
                            </span>
                        </button>
                    </div>
                </div>

                {/* Scrollable Body */}
                <div className="px-4 sm:px-6 py-4 sm:py-5 flex-1 overflow-y-auto overscroll-contain">
                    {mainTab === 'panduan' ? <TabPanduan /> : <TabFAQ />}
                </div>

                {/* Compact Side-by-Side Footer */}
                <div className="bg-slate-50/80 border-t border-slate-200/80 px-4 sm:px-6 py-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] sm:pb-3 flex items-center justify-between gap-3 shrink-0">
                    <button
                        type="button"
                        onClick={ulangiTour}
                        className={`inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 text-xs sm:text-sm font-semibold rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition-colors ${focusRing}`}
                    >
                        <Compass size={15} className="text-emerald-600 shrink-0" />
                        <span>Ulangi Tour Dashboard</span>
                    </button>

                    <button
                        type="button"
                        onClick={onClose}
                        className={`inline-flex items-center justify-center px-5 py-2.5 text-xs sm:text-sm font-semibold rounded-xl bg-slate-900 text-white hover:bg-slate-800 active:bg-slate-950 transition-colors ${focusRing}`}
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    );
}
