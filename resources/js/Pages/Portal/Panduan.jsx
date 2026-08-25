import { useState } from 'react';
import {
    Wallet, Repeat, Gauge, HelpCircle,
    ArrowRight, ChevronDown, AlertCircle, CheckCircle2, Info, Check, X,
} from 'lucide-react';

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2';

function MiniButton({ label, tone = 'primary', icon: Icon }) {
    const tones = {
        primary: 'bg-brand-green text-white border-brand-green',
        outline: 'border-2 border-brand-green text-brand-green-dark bg-brand-green-light',
        ghost: 'bg-white/10 border border-slate-500/40 text-slate-700',
        navy: 'bg-brand-navy text-white border-brand-navy',
    };
    return (
        <span className="inline-flex max-w-full items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold shadow-sm bg-[repeating-linear-gradient(45deg,transparent,transparent_4px,rgba(0,0,0,0.02)_4px,rgba(0,0,0,0.02)_8px)]">
            <span className={`inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border ${tones[tone]}`}>
                {Icon && <Icon size={13} />}
                {label}
            </span>
        </span>
    );
}

function MiniInput({ prefix, placeholder }) {
    return (
        <span className="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs bg-white border border-slate-300 shadow-sm">
            {prefix && <span className="text-slate-400 font-semibold">{prefix}</span>}
            <span className="text-slate-300 italic">{placeholder}</span>
            <Check size={14} className="text-brand-green" />
        </span>
    );
}

function MiniPills({ items, active = 0 }) {
    return (
        <span className="inline-flex items-center gap-1.5">
            {items.map((it, i) => (
                <span
                    key={i}
                    className={`inline-block min-w-[30px] text-center px-2 py-1 rounded-lg text-xs font-bold border-2 ${
                        i === active
                            ? 'border-brand-green bg-brand-green-light text-brand-green-dark'
                            : 'border-slate-200 text-slate-500'
                    }`}
                >
                    {it}
                </span>
            ))}
        </span>
    );
}

function MiniTimeline({ steps }) {
    return (
        <span className="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-slate-200 shadow-sm">
            {steps.map((s, i) => (
                <span key={i} className="flex items-center gap-1.5">
                    <span className="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                        {s}
                    </span>
                    {i < steps.length - 1 && <ArrowRight size={11} className="text-slate-400" />}
                </span>
            ))}
        </span>
    );
}

function MiniList({ items }) {
    return (
        <span className="inline-block min-w-[220px] align-top px-3 py-2 rounded-xl bg-white border border-slate-200 shadow-sm">
            {items.map((it, i) => (
                <div key={i} className="flex items-center justify-between gap-3 text-xs py-1.5 border-b border-slate-100 last:border-0">
                    <span className="text-slate-500">{it.label}</span>
                    <span className="font-bold text-slate-700 text-right">{it.value}</span>
                </div>
            ))}
        </span>
    );
}

function Langkah({ nomor, judul, deskripsi, mockup, last = false }) {
    return (
        <li className="relative flex gap-4">
            <div className="flex flex-col items-center shrink-0">
                <span className="w-9 h-9 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold relative z-10">
                    {nomor}
                </span>

                {!last && (
                    <span
                        aria-hidden="true"
                        className="w-0 flex-1 my-1.5 border-l-2 border-dashed border-brand-green/30"
                    />
                )}
            </div>

            <div className={`flex-1 min-w-0 pt-1.5 ${last ? '' : 'pb-6'}`}>
                <p className="text-sm font-bold text-slate-800">{judul}</p>
                <p className="text-sm text-slate-600 mt-1 leading-relaxed">{deskripsi}</p>
                {mockup && <div className="mt-3">{mockup}</div>}
            </div>
        </li>
    );
}

function Catatan({ items }) {
    return (
        <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
            <AlertCircle size={18} className="text-amber-600 shrink-0 mt-0.5" />
            <div className="min-w-0">
                <p className="text-sm font-bold text-amber-800 mb-1.5">Catatan Penting</p>
                <ul className="space-y-1.5">
                    {items.map((it, i) => (
                        <li key={i} className="text-sm text-amber-900 leading-relaxed flex gap-2">
                            <span className="text-amber-600 shrink-0">•</span>
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
        icon: Wallet,
        intro: 'Pinjaman baru diajukan dari Beranda ke halaman formulir. Sistem memandu langkah demi langkah.',
        langkah: [
            {
                judul: 'Buka formulir pengajuan',
                deskripsi: 'Di Beranda, bagian bawah kartu biru tua, klik tombol hijau "Ajukan Pinjaman". Belum punya pinjaman aktif? Tombol yang sama ada di kartu "Belum ada pinjaman aktif".',
                mockup: <MiniButton label="Ajukan Pinjaman" icon={Wallet} />,
            },
            {
                judul: 'Isi nominal',
                deskripsi: 'Ketik nominal yang ingin dipinjam. Setelah Anda berhenti mengetik, sistem memeriksanya terhadap limit tersedia. Centang hijau berarti nominal lolos.',
                mockup: <MiniInput prefix="Rp" placeholder="0" />,
            },
            {
                judul: 'Pilih lama cicilan',
                deskripsi: 'Tombol angka 1\u201312 bulan muncul sesuai ketentuan nominal Anda. Klik satu angka.',
                mockup: <MiniPills items={[1, 2, 3, 4, 5, 6]} active={2} />,
            },
            {
                judul: 'Periksa simulasi cicilan',
                deskripsi: 'Formulir menampilkan nominal, tenor, cicilan tiap bulan, dan total pembayaran. Pastikan angkanya sesuai kemampuan Anda sebelum lanjut.',
                mockup: <MiniList items={[
                    { label: 'Nominal', value: 'Rp 1.000.000' },
                    { label: 'Tenor', value: '6 bulan' },
                    { label: 'Total dibayar', value: 'Rp 1.050.000' },
                ]} />,
            },
            {
                judul: 'Tulis keperluan',
                deskripsi: 'Isi kolom "Keperluan Peminjaman", minimal 5 karakter. Contoh: Biaya pendidikan anak.',
                mockup: null,
            },
            {
                judul: 'Pilih rekening pencairan',
                deskripsi: 'Gunakan rekening tersimpan, atau pilih rekening baru dan isi datanya. Rekening baru otomatis tersimpan untuk pengajuan berikutnya.',
                mockup: null,
            },
            {
                judul: 'Baca dan setujui Syarat & Ketentuan',
                deskripsi: 'Klik "Ajukan Sekarang". Modal Syarat & Ketentuan terbuka. Baca poin-poinnya, lalu centang pernyataan persetujuan di bagian bawah.',
                mockup: <MiniButton label="Saya telah membaca…" tone="outline" icon={CheckCircle2} />,
            },
            {
                judul: 'Kirim pengajuan',
                deskripsi: 'Klik "Konfirmasi & Kirim Pengajuan". Setelah terkirim, pengajuan tidak bisa dibatalkan; statusnya hanya bisa disetujui atau ditolak.',
                mockup: <MiniButton label="Konfirmasi & Kirim Pengajuan" icon={ArrowRight} />,
            },
            {
                judul: 'Pantau statusnya',
                deskripsi: 'Kartu pinjaman di Beranda menampilkan tahap yang sedang berjalan. Alur lengkapnya:',
                mockup: <MiniTimeline steps={['Bendahara', 'Ketua', 'Dana Cair']} />,
            },
        ],
        catatan: [
            'Sedang menjalankan pinjaman lain? Limit tersedia otomatis dikurangi sisa cicilan pokok yang berjalan (pengajuan lebih awal).',
            'Anggota baru di bawah 1 tahun: pengajuan baru diproses setelah pinjaman sebelumnya lunas.',
            'Estimasi tinjauan 1\u20133 hari kerja per tahap, tergantung antrean.',
        ],
    },
    {
        key: 'tenor',
        label: 'Ubah Tenor',
        icon: Repeat,
        intro: 'Pinjaman aktif bisa dipendekkan, diperpanjang, atau dilunasi sekaligus. Hak ini bisa dipakai satu kali per pinjaman.',
        langkah: [
            {
                judul: 'Buka formulir perubahan',
                deskripsi: 'Di Beranda, tepat di bawah tombol Ajukan Pinjaman pada kartu biru tua, klik "Ubah Tenor / Lunas Dipercepat". Link ini muncul saat hak perubahan Anda masih tersedia.',
                mockup: <MiniButton label="Ubah Tenor / Lunas Dipercepat" tone="ghost" icon={Repeat} />,
            },
            {
                judul: 'Pilih jenis perubahan',
                deskripsi: 'Tiga kartu pilihan, klik salah satu sesuai kebutuhan Anda.',
                mockup: <MiniList items={[
                    { label: 'Percepat Pelunasan', value: 'tenor pendek' },
                    { label: 'Perpanjang Tenor', value: 'cicilan meringan' },
                    { label: 'Lunas Sekarang', value: 'bunga 1 bln saja' },
                ]} />,
            },
            {
                judul: 'Pilih tenor baru',
                deskripsi: 'Untuk Percepat, pilih angka lebih kecil dari tenor sekarang. Untuk Perpanjang, lebih besar. Lunas Sekarang tidak membutuhkan langkah ini.',
                mockup: <MiniPills items={[7, 8, 9, 10, 11, 12]} active={1} />,
            },
            {
                judul: 'Periksa hitungan ulang',
                deskripsi: 'Simulasi jadwal baru muncul otomatis setelah pilihan Anda lengkap. Bandingkan dengan cicilan sekarang sebelum lanjut.',
                mockup: <MiniList items={[
                    { label: 'Sisa pokok', value: 'Rp 700.000' },
                    { label: 'Cicilan/bln', value: 'Rp 175.000' },
                    { label: 'Total bayar', value: 'Rp 875.000' },
                ]} />,
            },
            {
                judul: 'Tulis alasan',
                deskripsi: 'Isi kolom "Alasan Pengajuan", minimal 10 karakter. Contoh: Meringankan cicilan karena anak mulai sekolah.',
                mockup: null,
            },
            {
                judul: 'Kirim dan tunggu keputusan',
                deskripsi: 'Klik "Kirim Pengajuan". Bendahara meninjau lalu Ketua Koperasi memutuskan, termasuk apakah perubahan berlaku bulan ini atau bulan depan.',
                mockup: <MiniTimeline steps={['Bendahara', 'Ketua']} />,
            },
        ],
        catatan: [
            'Hak perubahan hanya bisa dipakai satu kali per pinjaman. Hak yang sama tersedia lagi pada pinjaman berikutnya.',
            'Masih ada pengajuan perubahan yang sedang diproses? Tunggu keputusannya sebelum mengajukan lagi.',
            'Lunas Sekarang hanya dikenakan bunga 1 bulan dari sisa pokok.',
        ],
    },
    {
        key: 'limit',
        label: 'Tambah Limit',
        icon: Gauge,
        intro: 'Limit kategori belum cukup? Ajukan penambahan limit langsung ke Ketua Koperasi.',
        langkah: [
            {
                judul: 'Temukan tombolnya di Beranda',
                deskripsi: 'Tombol "Ajukan Penambahan Limit" ada di kartu biru tua, di samping tombol Ajukan Pinjaman. Ia muncul saat Anda boleh mengajukan pinjaman, tidak ada pengajuan limit berjalan, dan limit tersedia masih ada.',
                mockup: <MiniButton label="Ajukan Penambahan Limit" tone="outline" icon={Gauge} />,
            },
            {
                judul: 'Isi nominal limit',
                deskripsi: 'Halaman menampilkan limit Anda saat ini sebagai pembanding. Ketik besar limit yang diminta pada kolom "Limit yang Diminta".',
                mockup: <MiniInput prefix="Rp" placeholder="cth: 5.000.000" />,
            },
            {
                judul: 'Tulis alasannya',
                deskripsi: 'Jelaskan kebutuhan Anda minimal 10 karakter. Contoh: Cadangan dana biaya kesehatan keluarga.',
                mockup: null,
            },
            {
                judul: 'Kirim pengajuan',
                deskripsi: 'Klik "Kirim Pengajuan". Tahapannya lebih pendek daripada pinjaman: hanya Ketua Koperasi yang meninjau, tanpa tahap Bendahara.',
                mockup: <MiniButton label="Kirim Pengajuan" icon={ArrowRight} />,
            },
            {
                judul: 'Cek hasilnya',
                deskripsi: 'Selama diproses, kartu "Limit Tersedia" di Beranda bertanda "sedang diproses". Hasil akhir tampil di riwayat halaman Ajukan Tambah Limit. Disetujui? Limit baru langsung aktif untuk pinjaman berikutnya.',
                mockup: <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-brand-green-light text-brand-green-dark border border-brand-green/20">
                    <CheckCircle2 size={13} /> Disetujui
                </span>,
            },
        ],
        catatan: [
            'Satu waktu hanya boleh ada satu pengajuan limit aktif.',
            'Keputusan sepenuhnya wewenang Ketua Koperasi, dengan mempertimbangkan kemampuan keuangan koperasi dan riwayat pembayaran Anda.',
            'Limit baru berlaku untuk pinjaman berikutnya dan tidak mengubah pinjaman yang sedang berjalan.',
        ],
    },
];

const FAQ = [
    {
        kategori: 'Pengajuan Pinjaman',
        items: [
            {
                q: 'Berapa lama proses persetujuan pengajuan pinjaman saya?',
                a: 'Ada 2 tahap. Tahap 1 (Bendahara): 1\u20133 hari kerja. Tahap 2 (Ketua Koperasi): 1\u20133 hari kerja. Total estimasi 2\u20136 hari kerja, tergantung antrean pengajuan yang masuk.',
            },
            {
                q: 'Kenapa pengajuan saya ditolak?',
                a: 'Penyebab yang umum: (1) Nominal melebihi limit tersedia, (2) Ada angsuran sebelumnya yang belum dibayar (untuk anggota baru di bawah 1 tahun), (3) Data pengajuan tidak lengkap. Alasan spesifik dari pengurus tampil di halaman Riwayat pada pinjaman yang bersangkutan.',
            },
            {
                q: 'Berapa limit pinjaman maksimum yang bisa saya pinjam?',
                a: 'Limit dihitung otomatis dari kategori keanggotaan Anda. Cek angkanya di kartu "Limit Tersedia" pada Beranda. Jika kurang, ajukan penambahan limit ke Ketua Koperasi.',
            },
            {
                q: 'Apakah saya boleh memiliki lebih dari satu pinjaman aktif sekaligus?',
                a: 'Bisa, selama masih dalam batas limit dan hak pengajuan lebih awal (reloan) masih tersedia. Setelah hak reloan terpakai, lunasi pinjaman sebelumnya dulu sebelum mengajukan lagi.',
            },
            {
                q: 'Apakah pengajuan bisa dibatalkan setelah dikirim?',
                a: 'Tidak. Setelah terkirim, status hanya bisa disetujui atau ditolak oleh pengurus. Pastikan data sudah benar sebelum mengklik "Konfirmasi & Kirim Pengajuan".',
            },
            {
                q: 'Bagaimana jika saya resign dari perusahaan sementara pinjaman masih berjalan?',
                a: 'Lunasi seluruh sisa pinjaman terlebih dahulu; ini salah satu syarat diprosesnya pengunduran diri. Hubungi Bendahara untuk proses pelunasannya.',
            },
        ],
    },
    {
        kategori: 'Perubahan Tenor',
        items: [
            {
                q: 'Berapa kali saya bisa mengajukan perubahan tenor?',
                a: 'Satu kali per pinjaman. Setelah dipakai, hak ini hangus untuk pinjaman tersebut dan tersedia lagi pada pinjaman berikutnya setelah yang sekarang lunas.',
            },
            {
                q: 'Apa bedanya "Percepat Pelunasan" dengan "Lunas Sekarang"?',
                a: 'Percepat: cicilan tetap berjalan per bulan, tapi tenor dipendekkan sehingga cicilan per bulan lebih besar. Lunas Sekarang: seluruh sisa pokok dibayar sekali dalam 1 pembayaran, bunga hanya 1 bulan. Total bunga Lunas Sekarang biasanya lebih murah.',
            },
            {
                q: 'Apakah perubahan tenor langsung berlaku setelah disetujui?',
                a: 'Tergantung keputusan Ketua Koperasi: bisa mulai bulan ini atau bulan depan. Jadwal yang sudah digantikan tampil di halaman Riwayat setelah persetujuan final.',
            },
            {
                q: 'Kenapa link "Ubah Tenor" tidak muncul di Beranda saya?',
                a: 'Link itu hanya muncul jika semua ini benar: Anda punya pinjaman aktif, tidak ada pengajuan pinjaman atau perubahan tenor yang sedang diproses, dan hak perubahan pada pinjaman aktif Anda belum terpakai.',
            },
        ],
    },
    {
        kategori: 'Pengajuan Tambah Limit',
        items: [
            {
                q: 'Berapa kali saya bisa mengajukan tambah limit?',
                a: 'Tidak ada batas jumlah pengajuan, tapi satu waktu hanya boleh ada satu pengajuan aktif. Selesaikan pengajuan sebelumnya (disetujui atau ditolak) sebelum mengajukan lagi.',
            },
            {
                q: 'Apakah limit baru langsung berlaku setelah disetujui?',
                a: 'Ya, langsung berlaku untuk pinjaman berikutnya. Limit baru tidak mengubah pinjaman yang sedang berjalan.',
            },
            {
                q: 'Kenapa pengajuan tambah limit saya ditolak?',
                a: 'Alasannya dicatat Ketua Koperasi dan tampil di riwayat halaman Ajukan Tambah Limit. Yang umum: pertimbangan kemampuan keuangan koperasi, riwayat pembayaran Anda, atau limit dinilai sudah cukup.',
            },
        ],
    },
];

function FAQItem({ q, a }) {
    const [open, setOpen] = useState(false);
    return (
        <div className={`border rounded-xl transition-colors ${open ? 'border-brand-green/30 bg-brand-green-light/40' : 'border-slate-200 bg-white'}`}>
            <button
                type="button"
                onClick={() => setOpen(!open)}
                aria-expanded={open}
                className={`w-full flex items-center justify-between gap-3 p-3.5 text-left rounded-xl ${focusRing}`}
            >
                <span className="text-sm font-semibold text-slate-800">{q}</span>
                <ChevronDown
                    size={18}
                    className={`text-slate-400 shrink-0 transition-transform motion-reduce:transition-none ${open ? 'rotate-180 text-brand-green' : ''}`}
                />
            </button>
            {open && (
                <div className="px-3.5 pb-3.5 pt-0">
                    <p className="text-sm text-slate-600 leading-relaxed">{a}</p>
                </div>
            )}
        </div>
    );
}

function TabPanduan() {
    const [tab, setTab] = useState('pinjaman');
    const section = TATA_CARA.find((t) => t.key === tab);

    return (
        <div>
            <div className="flex items-center gap-1 p-1 bg-slate-100 rounded-xl mb-5 overflow-x-auto">
                {TATA_CARA.map((t) => {
                    const Icon = t.icon;
                    const aktif = tab === t.key;
                    return (
                        <button
                            key={t.key}
                            type="button"
                            onClick={() => setTab(t.key)}
                            aria-pressed={aktif}
                            className={`flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold whitespace-nowrap transition-colors ${focusRing} ${
                                aktif ? 'bg-white text-brand-green-dark shadow-sm' : 'text-slate-500 hover:text-slate-700'
                            }`}
                        >
                            <Icon size={14} />
                            {t.label}
                        </button>
                    );
                })}
            </div>

            <div className="space-y-5">
                <div className="flex items-start gap-3 bg-brand-navy/5 border border-brand-navy/10 rounded-xl p-3.5">
                    <Info size={18} className="text-brand-navy shrink-0 mt-0.5" />
                    <p className="text-sm text-slate-700 leading-relaxed">{section.intro}</p>
                </div>

                <ol className="space-y-0">
                    {section.langkah.map((l, i) => (
                        <Langkah
                            key={i}
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
        </div>
    );
}

function TabFAQ() {
    return (
        <div className="space-y-5">
            <div className="flex items-start gap-3 bg-brand-green-light border border-brand-green/20 rounded-xl p-3.5">
                <HelpCircle size={18} className="text-brand-green-dark shrink-0 mt-0.5" />
                <p className="text-sm text-slate-700 leading-relaxed">
                    Pertanyaan yang sering diajukan anggota. Klik pertanyaan untuk membuka jawabannya.
                </p>
            </div>

            {FAQ.map((kat) => (
                <div key={kat.kategori}>
                    <p className="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2.5">{kat.kategori}</p>
                    <div className="space-y-2">
                        {kat.items.map((it, i) => (
                            <FAQItem key={i} q={it.q} a={it.a} />
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );
}

export default function Panduan({ onClose }) {
    const [mainTab, setMainTab] = useState('panduan');

    return (
        <div
            className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm overflow-y-auto animate-in fade-in duration-200"
            onClick={onClose}
            role="dialog"
            aria-modal="true"
            aria-labelledby="panduan-title"
        >
            <div className="min-h-full flex items-start sm:items-center justify-center p-4">
                <div className="bg-white rounded-2xl w-full max-w-3xl my-8 shadow-2xl" onClick={(e) => e.stopPropagation()}>
                    <div className="sticky top-0 bg-white border-b border-slate-100 px-6 py-5 flex items-start justify-between gap-4 rounded-t-2xl z-10">
                        <div className="flex items-start gap-3 min-w-0">
                            <div className="w-10 h-10 rounded-xl bg-brand-green-light flex items-center justify-center shrink-0">
                                <HelpCircle size={20} className="text-brand-green" />
                            </div>
                            <div className="min-w-0">
                                <h2 id="panduan-title" className="text-lg font-bold text-slate-800">Tata Cara &amp; FAQ</h2>
                                <p className="text-xs text-slate-500 mt-0.5">Langkah mengajukan pinjaman, mengubah tenor, dan menambah limit.</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            onClick={onClose}
                            className={`p-2 -m-2 text-slate-400 hover:text-slate-600 transition-colors shrink-0 rounded-lg ${focusRing}`}
                            aria-label="Tutup panduan"
                        >
                            <X size={20} />
                        </button>
                    </div>

                    <div className="px-6 pt-4">
                        <div className="flex items-center gap-1 border-b border-slate-200">
                            <button
                                type="button"
                                onClick={() => setMainTab('panduan')}
                                aria-pressed={mainTab === 'panduan'}
                                className={`px-4 py-2.5 text-sm font-bold border-b-2 -mb-px transition-colors rounded-t ${focusRing} ${
                                    mainTab === 'panduan'
                                        ? 'border-brand-green text-brand-green-dark'
                                        : 'border-transparent text-slate-500 hover:text-slate-700'
                                }`}
                            >
                                Tata Cara
                            </button>
                            <button
                                type="button"
                                onClick={() => setMainTab('faq')}
                                aria-pressed={mainTab === 'faq'}
                                className={`px-4 py-2.5 text-sm font-bold border-b-2 -mb-px transition-colors flex items-center gap-1.5 rounded-t ${focusRing} ${
                                    mainTab === 'faq'
                                        ? 'border-brand-green text-brand-green-dark'
                                        : 'border-transparent text-slate-500 hover:text-slate-700'
                                }`}
                            >
                                <HelpCircle size={14} />
                                FAQ
                            </button>
                        </div>
                    </div>

                    <div className="px-6 py-5 max-h-[65vh] overflow-y-auto">
                        {mainTab === 'panduan' ? <TabPanduan /> : <TabFAQ />}
                    </div>

                    <div className="sticky bottom-0 bg-white border-t border-slate-100 px-6 py-4 rounded-b-2xl">
                        <button
                            type="button"
                            onClick={onClose}
                            className={`w-full py-3 text-sm font-bold rounded-xl border-2 border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors ${focusRing}`}
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
