import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, BadgePercent, BellRing, Clock, HandCoins, HeartHandshake, PiggyBank, ShieldCheck, UserCheck, Wallet } from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2';

const keuntungan = [
    { icon: HandCoins, judul: 'Fasilitas Pinjaman', deskripsi: 'Pengajuan pinjaman dengan batas maksimal sesuai masa keanggotaan, dicairkan melalui rekening terdaftar.' },
    { icon: PiggyBank, judul: 'Administrasi Simpanan', deskripsi: 'Simpanan Pokok dan Simpanan Wajib tercatat secara tertib dan dapat dipantau melalui menu Beranda dan Riwayat.' },
    { icon: HeartHandshake, judul: 'Dana Sosial', deskripsi: 'Iuran dana sosial setiap bulan disalurkan sebagai santunan sosial bagi anggota.' },
    { icon: BellRing, judul: 'Pemberitahuan WhatsApp', deskripsi: 'Setiap keputusan atas pengajuan Bapak/Ibu disampaikan melalui WhatsApp.' },
];

export default function Landing({ anggota, simpananPokok, simpananWajib, danaSosial, limitAwal, pengajuanBerjalan }) {
    return (
        <AnggotaLayout>
            <Head title="Keanggotaan Koperasi" />

            <div className="max-w-3xl mx-auto">
                <div className="bg-brand-navy rounded-2xl p-6 sm:p-8 text-white text-center mb-5">
                    <div className="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center mx-auto mb-3">
                        <UserCheck size={26} />
                    </div>
                    <p className="text-sm text-slate-300">Yth. Bapak/Ibu {anggota.nama}</p>
                    <h1 className="text-xl sm:text-2xl font-bold mt-1">Status Keanggotaan Belum Aktif</h1>
                    <p className="text-sm text-slate-300 mt-2 max-w-xl mx-auto leading-relaxed">
                        Untuk dapat menggunakan layanan pinjaman, simpanan, dan seluruh layanan Koperasi Karya Mandiri,
                        Bapak/Ibu dimohon melakukan aktivasi keanggotaan terlebih dahulu.
                    </p>
                    {pengajuanBerjalan && (
                        <p className={`inline-flex items-center gap-1.5 mt-4 px-4 py-2 rounded-xl bg-amber-400/15 border border-amber-300/30 text-sm font-semibold text-amber-200`}>
                            <Clock size={15} />
                            Pengajuan Bapak/Ibu sedang dalam proses persetujuan Ketua
                        </p>
                    )}
                </div>

                <div className="bg-white rounded-2xl border border-slate-200 p-5 mb-5">
                    <p className="text-base font-bold text-slate-800 mb-3">Hak dan Layanan Anggota</p>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {keuntungan.map((k) => {
                            const Icon = k.icon;
                            return (
                                <div key={k.judul} className="flex gap-3 p-4 rounded-xl bg-slate-50 border border-slate-100">
                                    <div className="w-9 h-9 rounded-lg bg-brand-green-light text-brand-green-dark flex items-center justify-center shrink-0">
                                        <Icon size={18} />
                                    </div>
                                    <div>
                                        <p className="text-sm font-bold text-slate-800">{k.judul}</p>
                                        <p className="text-sm text-slate-500 mt-0.5 leading-relaxed">{k.deskripsi}</p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>

                <div className="bg-white rounded-2xl border border-slate-200 p-5 mb-5">
                    <div className="flex items-center gap-2 mb-3">
                        <Wallet size={18} className="text-brand-navy" />
                        <p className="text-base font-bold text-slate-800">Kewajiban Iuran Keanggotaan</p>
                    </div>
                    <div className="divide-y divide-slate-100">
                        <div className="flex items-center justify-between gap-4 py-2.5">
                            <span className="text-sm text-slate-500">Simpanan Pokok (dibayar satu kali)</span>
                            <span className="text-sm font-bold text-slate-800">{formatRupiah(simpananPokok)}</span>
                        </div>
                        <div className="flex items-center justify-between gap-4 py-2.5">
                            <span className="text-sm text-slate-500">Simpanan Wajib (setiap bulan)</span>
                            <span className="text-sm font-bold text-slate-800">{formatRupiah(simpananWajib + danaSosial)}</span>
                        </div>
                        <p className="text-xs text-slate-400 py-2">
                            Nominal tersebut sudah termasuk Dana Sosial setiap bulan. Batas maksimal pinjaman awal {formatRupiah(limitAwal)} pada tahun pertama keanggotaan.
                        </p>
                    </div>
                </div>

                <div className="bg-amber-50 rounded-2xl border border-amber-200 p-5 mb-5">
                    <div className="flex items-center gap-2 mb-2">
                        <BadgePercent size={18} className="text-amber-600" />
                        <p className="text-base font-bold text-amber-800">Ketentuan Pengunduran Diri (Resign)</p>
                    </div>
                    <p className="text-sm text-amber-800 leading-relaxed">
                        Apabila Bapak/Ibu mengundurkan diri dari keanggotaan, Simpanan Pokok dan Simpanan Wajib akan{' '}
                        <span className="font-semibold">dikembalikan</span> setelah dikurangi sisa kewajiban pinjaman yang masih berjalan,
                        apabila ada. Dana Sosial tidak dikembalikan karena telah disalurkan sebagai santunan.
                        Akses terhadap layanan koperasi berakhir terhitung sejak tanggal pengunduran diri.
                    </p>
                </div>

                <div className="bg-white rounded-2xl border border-slate-200 p-5 mb-5">
                    <div className="flex items-center gap-2 mb-3">
                        <ShieldCheck size={18} className="text-brand-navy" />
                        <p className="text-base font-bold text-slate-800">Tahapan Aktivasi</p>
                    </div>
                    <div className="space-y-2.5">
                        {['Pengisian formulir: konfirmasi kesesuaian data dan persetujuan syarat', 'Peninjauan dan keputusan oleh Ketua Koperasi', 'Apabila disetujui, Simpanan Pokok tercatat otomatis dan akun menjadi aktif'].map((t, i) => (
                            <div key={i} className="flex gap-3">
                                <div className="w-6 h-6 rounded-full bg-brand-navy/10 text-brand-navy flex items-center justify-center text-xs font-bold shrink-0">{i + 1}</div>
                                <p className="text-sm text-slate-600">{t}</p>
                            </div>
                        ))}
                    </div>
                </div>

                {!pengajuanBerjalan && (
                    <div className="bg-white rounded-2xl border border-slate-200 p-5">
                        <p className="text-sm text-slate-500 mb-3 text-center">
                            Apabila Bapak/Ibu telah memahami informasi di atas, silakan lanjutkan ke formulir pengajuan.
                        </p>
                        <Link
                            href={route('portal.aktivasi.create')}
                            className={`flex items-center justify-center gap-2 w-full py-3.5 rounded-2xl bg-brand-green text-white text-base font-bold hover:bg-brand-green-dark transition-colors ${focusRing}`}
                        >
                            Ajukan Aktivasi Keanggotaan
                            <ArrowRight size={16} />
                        </Link>
                    </div>
                )}
            </div>
        </AnggotaLayout>
    );
}
