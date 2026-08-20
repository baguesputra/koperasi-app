import { useState } from 'react';
import {
    Wallet, Repeat, TrendingUp, HelpCircle,
    ArrowRight, ChevronDown, AlertCircle, CheckCircle2, Info,
} from 'lucide-react';

function MiniButton({ label, tone = 'primary', icon: Icon }) {
    const tones = {
        primary: 'bg-brand-green text-white border-brand-green',
        outline: 'border-2 border-brand-green text-brand-green-dark bg-brand-green-light',
        ghost: 'bg-white/10 border border-white/20 text-white',
    };
    return (
        <span className="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold shadow-sm bg-[repeating-linear-gradient(45deg,transparent,transparent_4px,rgba(0,0,0,0.02)_4px,rgba(0,0,0,0.02)_8px)]">
            <span className={`inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border ${tones[tone]}`}>
                {Icon && <Icon size={12} />}
                {label}
            </span>
        </span>
    );
}

function MiniInput({ prefix, placeholder }) {
    return (
        <span className="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-xs bg-white border border-slate-300 shadow-sm">
            {prefix && <span className="text-slate-400 font-semibold">{prefix}</span>}
            <span className="text-slate-300 italic">{placeholder}</span>
        </span>
    );
}

function MiniPills({ items, active = 0 }) {
    return (
        <span className="inline-flex items-center gap-1.5">
            {items.map((it, i) => (
                <span
                    key={i}
                    className={`inline-block min-w-[28px] text-center px-2 py-1 rounded-lg text-xs font-bold border-2 ${
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
                    <span className="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                        {s}
                    </span>
                    {i < steps.length - 1 && <ArrowRight size={10} className="text-slate-400" />}
                </span>
            ))}
        </span>
    );
}

function MiniList({ items }) {
    return (
        <span className="inline-block min-w-[180px] align-top px-3 py-2 rounded-xl bg-white border border-slate-200 shadow-sm">
            {items.map((it, i) => (
                <div key={i} className="flex items-center justify-between gap-3 text-[11px] py-1 border-b border-slate-100 last:border-0">
                    <span className="text-slate-500">{it.label}</span>
                    <span className="font-bold text-slate-700">{it.value}</span>
                </div>
            ))}
        </span>
    );
}

function Langkah({ nomor, judul, deskripsi, mockup }) {
    return (
        <div className="flex gap-3.5">
            <div className="shrink-0 w-9 h-9 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold">
                {nomor}
            </div>
            <div className="flex-1 min-w-0 pt-1">
                <p className="text-sm font-bold text-slate-800">{judul}</p>
                <p className="text-sm text-slate-600 mt-1.5 leading-relaxed">{deskripsi}</p>
                {mockup && <div className="mt-3">{mockup}</div>}
            </div>
        </div>
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
        label: 'Pengajuan Pinjaman',
        icon: Wallet,
        intro: 'Ajukan pinjaman baru melalui portal anggota dengan mengikuti langkah-langkah berikut.',
        langkah: [
            {
                judul: 'Cari tombol ajukan pinjaman',
                deskripsi: 'Pada halaman utama, cari tombol ajukan pinjaman di hero section.',
                mockup: <MiniButton label="Ajukan Pinjaman Baru" icon={Wallet} />,
            },
            {
                judul: 'Isi nominal pinjaman',
                deskripsi: 'Masukkan nominal yang Anda inginkan. Nominal otomatis divalidasi terhadap limit tersedia Anda.',
                mockup: <MiniInput prefix="Rp" placeholder="0" />,
            },
            {
                judul: 'Pilih tenor cicilan',
                deskripsi: 'Sistem akan menampilkan opsi tenor (1–12 bulan) sesuai nominal. Klik salah satu.',
                mockup: <MiniPills items={[1, 2, 3, 4, 5, 6]} active={2} />,
            },
            {
                judul: 'Lihat simulasi cicilan',
                deskripsi: 'Sistem menampilkan jadwal cicilan per bulan dan total pembayaran. Pastikan sesuai kemampuan Anda.',
                mockup: <MiniList items={[
                    { label: 'Cicilan ke-1', value: 'Rp 175.000' },
                    { label: 'Cicilan ke-2', value: 'Rp 175.000' },
                    { label: 'Total bayar', value: 'Rp 1.050.000' },
                ]} />,
            },
            {
                judul: 'Isi keperluan peminjaman',
                deskripsi: 'Tulis keperluan Anda (minimal 5 karakter). Contoh: Biaya pendidikan anak.',
                mockup: null,
            },
            {
                judul: 'Pilih rekening tujuan',
                deskripsi: 'Pilih rekening tersimpan atau tambah rekening baru untuk pencairan dana.',
                mockup: null,
            },
            {
                judul: 'Baca & setujui Syarat & Ketentuan',
                deskripsi: 'Akan muncul modal berisi 7 poin Syarat & Ketentuan. Baca dengan seksama, lalu centang checkbox persetujuan.',
                mockup: <MiniButton label="Saya menyetujui…" tone="outline" icon={CheckCircle2} />,
            },
            {
                judul: 'Kirim pengajuan',
                deskripsi: 'Klik tombol untuk mengirim. Pastikan semua data sudah benar karena pengajuan tidak bisa dibatalkan.',
                mockup: <MiniButton label="Konfirmasi & Kirim" icon={ArrowRight} />,
            },
            {
                judul: 'Tunggu persetujuan & pencairan',
                deskripsi: 'Pengajuan melalui 2 tahap tinjauan. Setelah disetujui Ketua, dana otomatis dicairkan ke rekening Anda.',
                mockup: <MiniTimeline steps={['Bendahara', 'Ketua', 'Cair']} />,
            },
        ],
        catatan: [
            'Limit tersedia berkurang otomatis jika Anda punya pinjaman aktif lain.',
            'Wajib menyetujui 7 poin Syarat & Ketentuan (termasuk pernyataan kebenaran data & bukan untuk pihak ketiga).',
            'Pengajuan tidak diproses jika ada pinjaman sebelumnya yang belum lunas (untuk anggota baru < 1 tahun).',
        ],
    },
    {
        key: 'tenor',
        label: 'Perubahan Tenor',
        icon: Repeat,
        intro: 'Ajukan perubahan tenor atau pelunasan dipercepat untuk pinjaman aktif Anda. Hanya bisa 1× per siklus pinjaman.',
        langkah: [
            {
                judul: 'Cari tombol perubahan tenor',
                deskripsi: 'Pada halaman utama, klik tombol "Ajukan Perubahan Tenor / Pelunasan Dipercepat" di hero section.',
                mockup: <MiniButton label="Ajukan Perubahan Tenor" tone="ghost" icon={Repeat} />,
            },
            {
                judul: 'Pilih tipe perubahan',
                deskripsi: 'Tersedia 3 pilihan. Pilih sesuai kebutuhan Anda.',
                mockup: <MiniList items={[
                    { label: '🔽 Percepat', value: 'Cicilan↑' },
                    { label: '🔼 Perpanjang', value: 'Cicilan↓' },
                    { label: '✅ Lunas', value: '1× bayar' },
                ]} />,
            },
            {
                judul: 'Pilih tenor baru (jika Percepat/Perpanjang)',
                deskripsi: 'Pilih tenor baru yang diinginkan. Untuk Percepat: harus < tenor saat ini. Untuk Perpanjang: harus > tenor saat ini.',
                mockup: <MiniPills items={[7, 8, 9, 10, 11, 12]} active={1} />,
            },
            {
                judul: 'Lihat simulasi jadwal',
                deskripsi: 'Sistem menghitung ulang jadwal cicilan berdasarkan sisa pokok + tenor baru. Untuk Lunas Sekarang, hanya bunga 1 bulan.',
                mockup: <MiniList items={[
                    { label: 'Sisa pokok', value: 'Rp 700.000' },
                    { label: 'Cicilan/bln', value: 'Rp 175.000' },
                    { label: 'Total', value: 'Rp 875.000' },
                ]} />,
            },
            {
                judul: 'Tulis alasan pengajuan',
                deskripsi: 'Jelaskan alasan Anda mengajukan perubahan (minimal 10 karakter).',
                mockup: null,
            },
            {
                judul: 'Kirim pengajuan & tunggu approval',
                deskripsi: 'Pengajuan ditinjau Bendahara lalu disetujui Ketua. Ketua menentukan apakah perubahan mulai berlaku bulan ini atau bulan depan.',
                mockup: <MiniTimeline steps={['Bendahara', 'Ketua']} />,
            },
        ],
        catatan: [
            'Hak perubahan tenor/pelunasan hanya bisa dipakai 1× per pinjaman.',
            'Lunas Sekarang hanya mengenakan bunga 1 bulan dari sisa pokok.',
            'Tidak bisa mengajukan jika masih ada pengajuan perubahan tenor sebelumnya yang diproses.',
        ],
    },
    {
        key: 'limit',
        label: 'Tambah Limit',
        icon: TrendingUp,
        intro: 'Ajukan penambahan limit pinjaman jika limit kategori yang diberikan belum mencukupi.',
        langkah: [
            {
                judul: 'Cari tombol tambah limit',
                deskripsi: 'Pada halaman utama, klik tombol "Limit Anda kurang?" di hero section (hanya muncul jika limit tersedia < limit kategori).',
                mockup: <MiniButton label="Limit Anda kurang?" tone="outline" icon={TrendingUp} />,
            },
            {
                judul: 'Isi nominal limit yang diminta',
                deskripsi: 'Nominal harus lebih besar dari limit kategori Anda saat ini. Sistem akan otomatis memvalidasi.',
                mockup: <MiniInput prefix="Rp" placeholder="cth: 5.000.000" />,
            },
            {
                judul: 'Tulis alasan/keterangan',
                deskripsi: 'Jelaskan secara singkat kebutuhan Anda atas limit tambahan (minimal 10 karakter).',
                mockup: null,
            },
            {
                judul: 'Kirim pengajuan & tunggu approval',
                deskripsi: 'Pengajuan hanya ditinjau Ketua Koperasi (tidak perlu tahap Bendahara). Estimasi 1–3 hari kerja.',
                mockup: <MiniTimeline steps={['Ketua']} />,
            },
            {
                judul: 'Cek hasilnya',
                deskripsi: 'Hasil (disetujui/ditolak) akan tampil di halaman Riwayat. Jika disetujui, limit baru langsung aktif untuk pinjaman berikutnya.',
                mockup: <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-brand-green-light text-brand-green-dark border border-brand-green/20">
                    <CheckCircle2 size={12} /> Disetujui
                </span>,
            },
        ],
        catatan: [
            'Hanya boleh ada 1 pengajuan aktif pada satu waktu.',
            'Persetujuan limit baru sepenuhnya wewenang Ketua Koperasi dengan pertimbangan kemampuan keuangan koperasi.',
            'Limit baru hanya untuk pinjaman berikutnya, tidak merestruktur pinjaman berjalan.',
        ],
    },
];

const FAQ = [
    {
        kategori: 'Pengajuan Pinjaman',
        items: [
            {
                q: 'Berapa lama proses persetujuan pengajuan pinjaman saya?',
                a: 'Total 2 tahap. Tahap 1 (Bendahara): 1–3 hari kerja. Tahap 2 (Ketua Koperasi): 1–3 hari kerja. Total estimasi 2–6 hari kerja, tergantung antrean pengajuan yang masuk.',
            },
            {
                q: 'Kenapa pengajuan saya ditolak?',
                a: 'Beberapa penyebab umum: (1) Nominal melebihi limit tersedia, (2) Ada angsuran sebelumnya yang belum dibayar (untuk anggota baru < 1 tahun), (3) Data pengajuan tidak lengkap. Alasan penolakan spesifik dari pengurus akan muncul di halaman Riwayat.',
            },
            {
                q: 'Berapa limit pinjaman maksimum yang bisa saya pinjam?',
                a: 'Limit dihitung otomatis berdasarkan kategori keanggotaan Anda. Cek limit Anda saat ini di halaman utama (bagian "Limit Tersedia"). Jika merasa kurang, ajukan Tambah Limit ke Ketua Koperasi.',
            },
            {
                q: 'Apakah saya boleh memiliki lebih dari satu pinjaman aktif sekaligus?',
                a: 'Bisa, selama masih dalam limit dan hak reloan (pengajuan lebih awal) masih tersedia. Setelah hak reloan terpakai, Anda wajib melunasi pinjaman sebelumnya dulu sebelum bisa mengajukan lagi.',
            },
            {
                q: 'Apakah pengajuan bisa dibatalkan setelah dikirim?',
                a: 'Tidak. Setelah pengajuan dikirim, status hanya bisa disetujui atau ditolak oleh pengurus. Mohon pastikan data sudah benar sebelum klik "Konfirmasi & Kirim".',
            },
            {
                q: 'Bagaimana jika saya resign dari perusahaan sementara pinjaman masih berjalan?',
                a: 'Anda wajib melunasi seluruh sisa pinjaman terlebih dahulu sebagai salah satu syarat diprosesnya pengunduran diri. Hubungi Bendahara untuk proses pelunasan.',
            },
        ],
    },
    {
        kategori: 'Perubahan Tenor',
        items: [
            {
                q: 'Berapa kali saya bisa mengajukan perubahan tenor?',
                a: 'Hanya 1 (satu) kali per pinjaman. Setelah dipakai, hak ini hangus untuk pinjaman tersebut. Anda harus melunasi dulu sebelum bisa menggunakan hak lagi di pinjaman berikutnya.',
            },
            {
                q: 'Apa bedanya "Percepat Pelunasan" dengan "Lunas Sekarang"?',
                a: 'Percepat: cicilan tetap berjalan per bulan, tapi tenor dipendekkan, sehingga cicilan per bulan lebih besar. Lunas Sekarang: bayar seluruh sisa pokok sekaligus dalam 1 pembayaran, bunga hanya 1 bulan. Lunas Sekarang biasanya lebih murah total bunganya.',
            },
            {
                q: 'Apakah perubahan tenor langsung berlaku setelah disetujui?',
                a: 'Tergantung keputusan Ketua Koperasi. Bisa berlaku mulai bulan ini atau bulan depan. Notifikasi akan muncul di Riwayat setelah persetujuan final.',
            },
            {
                q: 'Kenapa saya tidak bisa mengajukan perubahan tenor?',
                a: 'Kemungkinan penyebab: (1) Sudah pernah menggunakan hak perubahan tenor/pelunasan pada pinjaman ini, (2) Pinjaman belum aktif (masih proses persetujuan atau sudah lunas), (3) Masih ada pengajuan perubahan tenor sebelumnya yang menunggu diproses.',
            },
        ],
    },
    {
        kategori: 'Pengajuan Tambah Limit',
        items: [
            {
                q: 'Berapa kali saya bisa mengajukan tambah limit?',
                a: 'Tidak ada batasan jumlah pengajuan, tapi hanya boleh ada 1 pengajuan aktif pada satu waktu. Selesaikan pengajuan sebelumnya dulu (disetujui/ditolak) sebelum mengajukan lagi.',
            },
            {
                q: 'Apakah limit baru langsung berlaku setelah disetujui?',
                a: 'Ya, langsung berlaku untuk pinjaman berikutnya. Limit baru tidak merestruktur pinjaman yang sedang berjalan.',
            },
            {
                q: 'Kenapa pengajuan tambah limit saya ditolak?',
                a: 'Alasan penolakan akan dicatat oleh Ketua Koperasi. Cek di halaman Riwayat → bagian pengajuan limit. Umumnya karena pertimbangan kemampuan keuangan koperasi, riwayat pembayaran, atau limit sudah dinilai cukup.',
            },
        ],
    },
];

function FAQItem({ q, a, defaultOpen = false }) {
    const [open, setOpen] = useState(defaultOpen);
    return (
        <div className={`border rounded-xl transition-colors ${open ? 'border-brand-green/30 bg-brand-green-light/40' : 'border-slate-200 bg-white'}`}>
            <button
                type="button"
                onClick={() => setOpen(!open)}
                className="w-full flex items-center justify-between gap-3 p-3.5 text-left"
            >
                <span className="text-sm font-semibold text-slate-800">{q}</span>
                <ChevronDown
                    size={18}
                    className={`text-slate-400 shrink-0 transition-transform ${open ? 'rotate-180 text-brand-green' : ''}`}
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
                            className={`flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold whitespace-nowrap transition-colors ${
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

                <div className="space-y-4">
                    {section.langkah.map((l, i) => (
                        <Langkah key={i} nomor={i + 1} judul={l.judul} deskripsi={l.deskripsi} mockup={l.mockup} />
                    ))}
                </div>

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
                    Pertanyaan yang sering diajukan anggota. Klik pertanyaan untuk melihat jawabannya.
                </p>
            </div>

            {FAQ.map((kat) => (
                <div key={kat.kategori}>
                    <p className="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2.5">{kat.kategori}</p>
                    <div className="space-y-2">
                        {kat.items.map((it, i) => (
                            <FAQItem key={i} q={it.q} a={it.a} defaultOpen={false} />
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
        <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto animate-in fade-in duration-200">
            <div className="bg-white rounded-2xl w-full max-w-3xl my-8 shadow-2xl">
                <div className="sticky top-0 bg-white border-b border-slate-100 px-6 py-5 flex items-start justify-between gap-4 rounded-t-2xl">
                    <div className="flex items-start gap-3 min-w-0">
                        <div className="w-10 h-10 rounded-xl bg-brand-green-light flex items-center justify-center shrink-0">
                            <HelpCircle size={20} className="text-brand-green" />
                        </div>
                        <div className="min-w-0">
                            <h2 className="text-lg font-bold text-slate-800">Tata Cara & Panduan</h2>
                            <p className="text-xs text-slate-400 mt-0.5">Pelajari langkah-langkah penggunaan fitur koperasi</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="text-slate-400 hover:text-slate-600 transition-colors shrink-0"
                        aria-label="Tutup"
                    >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                    </button>
                </div>

                <div className="px-6 pt-4">
                    <div className="flex items-center gap-1 border-b border-slate-200">
                        <button
                            type="button"
                            onClick={() => setMainTab('panduan')}
                            className={`px-4 py-2.5 text-sm font-bold border-b-2 -mb-px transition-colors ${
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
                            className={`px-4 py-2.5 text-sm font-bold border-b-2 -mb-px transition-colors flex items-center gap-1.5 ${
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
                        className="w-full py-3 text-sm font-bold rounded-xl border-2 border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    );
}
