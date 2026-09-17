import { useForm } from '@inertiajs/react';
import { Lock, CalendarDays, ToggleLeft, Wallet } from 'lucide-react';
import Button from '@/Components/ui/Button';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import FormField from '@/Components/ui/FormField';
import Select from '@/Components/ui/Select';
import TextField from '@/Components/ui/TextField';

function Seksi({ ikon: Ikon, judul, deskripsi, children }) {
    return (
        <section className="rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm">
            <div className="flex items-center gap-2.5 mb-1">
                <span className="w-8 h-8 rounded-xl bg-slate-100 text-slate-500 inline-flex items-center justify-center shrink-0">
                    <Ikon size={16} />
                </span>
                <h3 className="text-sm font-bold text-slate-800">{judul}</h3>
            </div>
            {deskripsi && <p className="text-xs text-slate-400 mb-3 ml-[42px]">{deskripsi}</p>}
            <div className={deskripsi ? '' : 'mt-3'}>{children}</div>
        </section>
    );
}

function Info({ label, value }) {
    return (
        <div className="rounded-xl bg-slate-50/80 border border-slate-100 px-3 py-2 min-w-0">
            <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-400 truncate">{label}</p>
            <p className="text-sm font-semibold text-slate-700 truncate mt-0.5" title={value ?? '-'}>{value ?? '-'}</p>
        </div>
    );
}

export default function EditDrawer({ anggota, onClose }) {
    const { data, setData, put, processing, errors } = useForm({
        tanggal_jadi_anggota: anggota.tanggal_jadi_anggota ?? '',
        status: anggota.status,
        limit_custom: anggota.limit_custom ?? '',
        limit_custom_keterangan: anggota.limit_custom_keterangan ?? '',
    });

    function submit(e) {
        e.preventDefault();
        put(route('anggota.update', anggota.id), {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    }

    return (
        <form onSubmit={submit} className="space-y-3">
            <div className="flex items-center gap-3 rounded-2xl bg-gradient-to-r from-brand-navy to-brand-navy-light text-white px-4 py-3 shadow-md shadow-brand-navy/20">
                <FotoAnggota nama={anggota.nama} fotoUrl={anggota.foto_url} ukuran="lg" className="bg-white/15 ring-white/20" />
                <div className="min-w-0">
                    <p className="text-sm font-bold truncate">{anggota.nama}</p>
                    <p className="text-xs text-white/70 truncate">{anggota.no_karyawan}{anggota.user?.email ? ` • ${anggota.user.email}` : ''}</p>
                </div>
            </div>

            <div className="flex items-start gap-2.5 rounded-2xl bg-amber-50 border border-amber-200 px-3.5 py-2.5 text-xs text-amber-800">
                <Lock size={15} className="shrink-0 mt-0.5" />
                <p>Data identitas, penempatan, dan kontak <strong>terkunci</strong> — sinkron otomatis dari GATE. Hubungi admin HR bila ada selisih.</p>
            </div>

            <Seksi ikon={Lock} judul="Data GATE" deskripsi="Read-only, diperbarui via sinkronisasi">
                <div className="grid grid-cols-2 gap-2">
                    <Info label="Nama" value={anggota.nama} />
                    <Info label="No. Karyawan" value={anggota.no_karyawan} />
                    <Info label="Cabang" value={anggota.cabang} />
                    <Info label="Unit Bisnis" value={anggota.unit_bisnis} />
                    <Info label="Perusahaan" value={anggota.perusahaan?.nama} />
                    <Info label="Departemen" value={anggota.departemen?.nama} />
                    <Info label="Divisi" value={anggota.divisiMaster?.nama} />
                    <Info label="Jabatan" value={anggota.jabatanMaster?.nama ?? anggota.jabatan} />
                    <Info label="Mulai Kerja" value={anggota.tanggal_mulai_kerja} />
                    <Info label="No. HP" value={anggota.no_hp} />
                    <div className="col-span-2">
                        <Info label="Alamat" value={anggota.alamat} />
                    </div>
                </div>
            </Seksi>

            <Seksi ikon={CalendarDays} judul="Keanggotaan Koperasi" deskripsi="Satu-satunya data tanggal yang boleh diubah manual">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-3">
                    <FormField label="Tanggal Jadi Anggota" error={errors.tanggal_jadi_anggota} hint="Acuan lama keanggotaan & limit" required>
                        <TextField size="sm" type="date" value={data.tanggal_jadi_anggota} onChange={(e) => setData('tanggal_jadi_anggota', e.target.value)} autoFocus required />
                    </FormField>
                    <FormField label="Status" error={errors.status} required>
                        <div className="relative">
                            <ToggleLeft size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                            <Select size="sm" value={data.status} onChange={(e) => setData('status', e.target.value)} className="pl-9" required>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </Select>
                        </div>
                    </FormField>
                </div>
            </Seksi>

            <Seksi ikon={Wallet} judul="Limit Khusus" deskripsi="Kosongkan untuk pakai aturan otomatis jabatan & masa kerja">
                <FormField label="Nominal Limit Khusus" error={errors.limit_custom} hint="Kosongkan untuk hapus limit khusus">
                    <TextField size="sm" type="number" min="0" value={data.limit_custom} onChange={(e) => setData('limit_custom', e.target.value)} placeholder="Contoh: 10000000" />
                </FormField>
                <FormField label="Alasan / Keterangan" error={errors.limit_custom_keterangan}>
                    <TextField size="sm" value={data.limit_custom_keterangan} onChange={(e) => setData('limit_custom_keterangan', e.target.value)} placeholder="Contoh: Kebijakan khusus ketua" />
                </FormField>
            </Seksi>

            <div className="flex items-center gap-2 pt-3 border-t border-slate-100 sticky bottom-0 bg-white pb-1">
                <Button type="submit" size="sm" disabled={processing} className="rounded-full shadow-md shadow-brand-green/25">
                    {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                </Button>
                <Button type="button" variant="ghost" size="sm" className="rounded-full" onClick={onClose}>
                    Batal
                </Button>
            </div>
        </form>
    );
}
