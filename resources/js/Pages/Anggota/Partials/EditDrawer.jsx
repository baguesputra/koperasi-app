import { useForm } from '@inertiajs/react';
import { ArrowLeft, Check, Lock, CalendarDays, ToggleLeft, Wallet, User } from 'lucide-react';
import Button from '@/Components/ui/Button';
import {
    DrawerBody,
    DrawerField,
    DrawerFieldGrid,
    DrawerFooter,
    DrawerFooterActions,
    DrawerHero,
    DrawerSection,
} from '@/Components/ui/DrawerSection';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import FormField from '@/Components/ui/FormField';
import Select from '@/Components/ui/Select';
import CurrencyField from '@/Components/ui/CurrencyField';
import TextField from '@/Components/ui/TextField';

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
        <form onSubmit={submit} className="flex-1 min-h-0 flex flex-col">
            <DrawerBody>
                <DrawerHero>
                    <div className="flex items-center gap-3">
                        <FotoAnggota nama={anggota.nama} fotoUrl={anggota.foto_url} ukuran="lg" className="bg-white/15 ring-white/20 shrink-0" />
                        <div className="min-w-0">
                            <p className="text-base font-bold text-white truncate">{anggota.nama}</p>
                            <p className="text-xs text-slate-300 truncate mt-0.5">
                                <span className="font-mono">{anggota.no_karyawan}</span>
                                {anggota.user?.email ? ` • ${anggota.user.email}` : ''}
                            </p>
                        </div>
                    </div>
                </DrawerHero>

                <div className="flex items-start gap-2.5 rounded-xl bg-amber-50/55 border border-amber-200/80 px-3.5 py-2.5 text-xs text-amber-900">
                    <Lock size={15} className="text-amber-600 shrink-0 mt-0.5" aria-hidden="true" />
                    <p>Data identitas, penempatan, dan kontak <strong>terkunci</strong> — sinkron otomatis dari GATE. Hubungi admin HR bila ada selisih.</p>
                </div>

                <DrawerSection
                    title="Data Anggota"
                    description="Read-only, diperbarui via sinkronisasi"
                    icon={User}
                    action={
                        <span className="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[11px] font-semibold">
                            Sinkron GATE
                        </span>
                    }
                >
                    <DrawerFieldGrid cols={2}>
                        <DrawerField label="Nama" value={anggota.nama} />
                        <DrawerField label="No. Karyawan" value={anggota.no_karyawan} mono />
                        <DrawerField label="Cabang" value={anggota.cabang} />
                        <DrawerField label="Unit Bisnis" value={anggota.unit_bisnis} />
                        <DrawerField label="Perusahaan" value={anggota.perusahaan?.nama} />
                        <DrawerField label="Departemen" value={anggota.departemen?.nama} />
                        <DrawerField label="Divisi" value={anggota.divisiMaster?.nama} />
                        <DrawerField label="Jabatan" value={anggota.jabatanMaster?.nama ?? anggota.jabatan} />
                        <DrawerField label="Mulai Kerja" value={anggota.tanggal_mulai_kerja} />
                        <DrawerField label="No. HP" value={anggota.no_hp} />
                        <DrawerField label="Alamat" value={anggota.alamat} colSpan={2} />
                    </DrawerFieldGrid>
                </DrawerSection>

                <DrawerSection
                    title="Keanggotaan Koperasi"
                    description="Satu-satunya data tanggal yang boleh diubah manual"
                    icon={CalendarDays}
                >
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-3">
                        <FormField label="Tanggal Jadi Anggota" error={errors.tanggal_jadi_anggota} hint="Acuan lama keanggotaan & limit" required>
                            <TextField size="sm" type="date" value={data.tanggal_jadi_anggota} onChange={(e) => setData('tanggal_jadi_anggota', e.target.value)} required />
                        </FormField>
                        <FormField label="Status" error={errors.status} required>
                            <div className="relative">
                                <ToggleLeft size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none" />
                                <Select size="sm" value={data.status} onChange={(e) => setData('status', e.target.value)} className="pl-9" required>
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </Select>
                            </div>
                        </FormField>
                    </div>
                </DrawerSection>

                <DrawerSection
                    title="Limit Khusus"
                    description="Kosongkan untuk pakai aturan otomatis jabatan & masa kerja"
                    icon={Wallet}
                >
                    <FormField label="Nominal Limit Khusus" error={errors.limit_custom} hint="Kosongkan untuk hapus limit khusus">
                        <CurrencyField size="sm" value={data.limit_custom} onChange={(e) => setData('limit_custom', e.target.value)} placeholder="Contoh: 10.000.000" />
                    </FormField>
                    <FormField label="Alasan / Keterangan" error={errors.limit_custom_keterangan}>
                        <TextField size="sm" value={data.limit_custom_keterangan} onChange={(e) => setData('limit_custom_keterangan', e.target.value)} placeholder="Contoh: Kebijakan khusus ketua" />
                    </FormField>
                </DrawerSection>
            </DrawerBody>

            <DrawerFooter>
                <DrawerFooterActions>
                    <Button type="button" variant="neutral" size="drawer" onClick={onClose}>
                        <ArrowLeft size={16} />
                        Batal
                    </Button>
                    <Button type="submit" size="drawer" disabled={processing}>
                        <Check size={16} />
                        {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                    </Button>
                </DrawerFooterActions>
            </DrawerFooter>
        </form>
    );
}
