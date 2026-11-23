import { useForm } from '@inertiajs/react';
import { ShieldAlert, History } from 'lucide-react';
import Button from '@/Components/ui/Button';
import FormField from '@/Components/ui/FormField';
import Select from '@/Components/ui/Select';
import CurrencyField from '@/Components/ui/CurrencyField';
import TextField from '@/Components/ui/TextField';
import SectionCard from './SectionCard';

export default function TabAturanTabungan({
    bungaSaatIni,
    riwayatBunga = [],
    settingTabungan,
    metodeBungaOptions = {},
    sectionAktif,
    readOnly = false,
}) {
    const hariIni = new Date().toISOString().slice(0, 10);

    const bungaForm = useForm({
        persentase: '',
        berlaku_dari_tanggal: hariIni,
        keterangan: '',
    });

    const ketentuanForm = useForm({
        metode_bunga: settingTabungan?.metode_bunga ?? 'saldo_harian',
        min_setor: settingTabungan?.min_setor ?? 50000,
        saldo_minimal: settingTabungan?.saldo_minimal ?? 50000,
        min_tarik: settingTabungan?.min_tarik ?? 50000,
        bank_tujuan_nama: settingTabungan?.bank_tujuan_nama ?? '',
        bank_tujuan_rekening: settingTabungan?.bank_tujuan_rekening ?? '',
        bank_tujuan_atas_nama: settingTabungan?.bank_tujuan_atas_nama ?? '',
        buka_rekening_aktif: settingTabungan?.buka_rekening_aktif ?? true,
    });

    function submitBunga(e) {
        e.preventDefault();
        bungaForm.post(route('pengaturan.tabungan.bunga.update'), {
            preserveScroll: true,
            onSuccess: () => bungaForm.reset('persentase', 'keterangan'),
        });
    }

    function submitKetentuan(e) {
        e.preventDefault();
        ketentuanForm.post(route('pengaturan.tabungan.ketentuan.update'), {
            preserveScroll: true,
        });
    }

    return (
        <div className="space-y-4">
            {readOnly && (
                <div className="flex items-center gap-2 p-3 bg-amber-50/70 border border-amber-200/80 text-amber-800 rounded-xl text-xs font-medium">
                    <ShieldAlert size={16} className="text-amber-600 shrink-0" />
                    <span>Mode Baca Saja — Anda hanya dapat melihat aturan tabungan. Perubahan kebijakan wewenang Ketua Koperasi atau Super Admin.</span>
                </div>
            )}

            <SectionCard
                id="bunga-tabungan"
                judul="Suku Bunga Tabungan & Snapshot"
                ringkasan={bungaSaatIni?.persentase != null ? `${bungaSaatIni.persentase}% / thn` : null}
                sectionAktif={sectionAktif}
            >
                <p className="text-sm text-slate-500 mb-4">
                    Perubahan suku bunga disimpan sebagai versi baru dengan tanggal mulai berlaku. Perhitungan bunga yang sudah
                    dibukukan di periode sebelumnya tetap terkunci pada snapshot tarif saat itu dan tidak berubah secara retroaktif.
                </p>

                <div className="flex flex-wrap items-center gap-3 mb-5 p-4 bg-gradient-to-r from-brand-navy/5 to-brand-green-light/60 rounded-2xl w-fit border border-slate-100">
                    <span className="text-sm text-slate-600">Tarif berlaku saat ini:</span>
                    <span className="text-lg font-bold text-brand-navy tabular-nums">
                        {bungaSaatIni?.persentase ?? '2.00'}% / tahun
                    </span>
                </div>

                {!readOnly && (
                    <form onSubmit={submitBunga} className="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end mb-6">
                        <div className="sm:col-span-3">
                            <FormField label="Suku Bunga (% / tahun)" error={bungaForm.errors.persentase} required>
                                <TextField
                                    size="sm"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="50"
                                    value={bungaForm.data.persentase}
                                    onChange={(e) => bungaForm.setData('persentase', e.target.value)}
                                    placeholder="Contoh: 2.5"
                                    required
                                    className="tabular-nums"
                                />
                            </FormField>
                        </div>
                        <div className="sm:col-span-3">
                            <FormField label="Berlaku Mulai Tanggal" error={bungaForm.errors.berlaku_dari_tanggal} required>
                                <TextField
                                    size="sm"
                                    type="date"
                                    value={bungaForm.data.berlaku_dari_tanggal}
                                    onChange={(e) => bungaForm.setData('berlaku_dari_tanggal', e.target.value)}
                                    required
                                />
                            </FormField>
                        </div>
                        <div className="sm:col-span-4">
                            <FormField label="Keterangan Perubahan" error={bungaForm.errors.keterangan}>
                                <TextField
                                    size="sm"
                                    type="text"
                                    value={bungaForm.data.keterangan}
                                    onChange={(e) => bungaForm.setData('keterangan', e.target.value)}
                                    placeholder="Mis. Penyesuaian RAT 2027"
                                />
                            </FormField>
                        </div>
                        <div className="sm:col-span-2 pb-3.5">
                            <Button
                                type="submit"
                                size="sm"
                                disabled={bungaForm.processing}
                                className="w-full rounded-full shadow-md shadow-brand-green/25"
                            >
                                {bungaForm.processing ? 'Menyimpan...' : 'Simpan'}
                            </Button>
                        </div>
                    </form>
                )}

                <div className="border-t border-slate-100 pt-4">
                    <div className="flex items-center gap-2 mb-2.5 text-xs font-bold uppercase tracking-wider text-slate-500">
                        <History size={14} />
                        <span>Riwayat Versi Tarif (Snapshot)</span>
                    </div>
                    {riwayatBunga.length === 0 ? (
                        <p className="text-xs text-slate-400">Belum ada riwayat perubahan suku bunga.</p>
                    ) : (
                        <div className="overflow-x-auto border border-slate-200/80 rounded-xl">
                            <table className="w-full text-left border-collapse text-xs sm:text-sm">
                                <thead>
                                    <tr className="bg-[#F8FAFC] border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                        <th className="px-3.5 py-2.5">Berlaku Dari</th>
                                        <th className="px-3.5 py-2.5">Suku Bunga</th>
                                        <th className="px-3.5 py-2.5">Keterangan</th>
                                        <th className="px-3.5 py-2.5">Diubah Oleh</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {riwayatBunga.map((row) => (
                                        <tr key={row.id} className="hover:bg-[#F2FBF5] transition-colors">
                                            <td className="px-3.5 py-2.5 font-medium text-slate-700 whitespace-nowrap">{row.berlaku_dari_label}</td>
                                            <td className="px-3.5 py-2.5 font-bold text-brand-navy tabular-nums whitespace-nowrap">{row.persentase}% / thn</td>
                                            <td className="px-3.5 py-2.5 text-slate-600">{row.keterangan || '-'}</td>
                                            <td className="px-3.5 py-2.5 text-slate-500 whitespace-nowrap">{row.created_by_name}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </SectionCard>

            <form onSubmit={submitKetentuan} className="space-y-4">
                <SectionCard
                    id="rekening-tujuan"
                    judul="Rekening Bank Tujuan Setoran"
                    ringkasan={settingTabungan?.bank_tujuan_nama || null}
                    sectionAktif={sectionAktif}
                >
                    <p className="text-sm text-slate-500 mb-4">
                        Ditampilkan kepada anggota di Portal Anggota saat mengunggah bukti transfer setoran tabungan.
                    </p>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <FormField label="Nama Bank" error={ketentuanForm.errors.bank_tujuan_nama} required>
                            <TextField
                                size="sm"
                                value={ketentuanForm.data.bank_tujuan_nama}
                                onChange={(e) => ketentuanForm.setData('bank_tujuan_nama', e.target.value)}
                                disabled={readOnly}
                                required
                            />
                        </FormField>
                        <FormField label="Nomor Rekening" error={ketentuanForm.errors.bank_tujuan_rekening} required>
                            <TextField
                                size="sm"
                                value={ketentuanForm.data.bank_tujuan_rekening}
                                onChange={(e) => ketentuanForm.setData('bank_tujuan_rekening', e.target.value)}
                                disabled={readOnly}
                                className="font-mono"
                                required
                            />
                        </FormField>
                        <FormField label="Atas Nama" error={ketentuanForm.errors.bank_tujuan_atas_nama} required>
                            <TextField
                                size="sm"
                                value={ketentuanForm.data.bank_tujuan_atas_nama}
                                onChange={(e) => ketentuanForm.setData('bank_tujuan_atas_nama', e.target.value)}
                                disabled={readOnly}
                                required
                            />
                        </FormField>
                    </div>
                </SectionCard>

                <SectionCard
                    id="ketentuan-tabungan"
                    judul="Ketentuan Saldo & Transaksi"
                    ringkasan={metodeBungaOptions[ketentuanForm.data.metode_bunga] ? 'Aktif' : null}
                    sectionAktif={sectionAktif}
                >
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <FormField label="Metode Perhitungan Bunga" error={ketentuanForm.errors.metode_bunga} required>
                            <Select
                                size="sm"
                                value={ketentuanForm.data.metode_bunga}
                                onChange={(e) => ketentuanForm.setData('metode_bunga', e.target.value)}
                                disabled={readOnly}
                            >
                                {Object.entries(metodeBungaOptions).map(([val, label]) => (
                                    <option key={val} value={val}>{label}</option>
                                ))}
                            </Select>
                        </FormField>

                        <FormField label="Status Pembukaan Rekening Baru" error={ketentuanForm.errors.buka_rekening_aktif}>
                            <Select
                                size="sm"
                                value={ketentuanForm.data.buka_rekening_aktif ? '1' : '0'}
                                onChange={(e) => ketentuanForm.setData('buka_rekening_aktif', e.target.value === '1')}
                                disabled={readOnly}
                            >
                                <option value="1">Dibuka (Anggota dapat mengajukan)</option>
                                <option value="0">Ditutup sementara</option>
                            </Select>
                        </FormField>

                        <FormField label="Minimal Setoran (Rp)" error={ketentuanForm.errors.min_setor} required>
                            <CurrencyField
                                size="sm"
                                value={ketentuanForm.data.min_setor}
                                onChange={(e) => ketentuanForm.setData('min_setor', e.target.value)}
                                disabled={readOnly}
                                required
                            />
                        </FormField>

                        <FormField label="Saldo Minimal Mengendap (Rp)" error={ketentuanForm.errors.saldo_minimal} required>
                            <CurrencyField
                                size="sm"
                                value={ketentuanForm.data.saldo_minimal}
                                onChange={(e) => ketentuanForm.setData('saldo_minimal', e.target.value)}
                                disabled={readOnly}
                                required
                            />
                        </FormField>

                        <FormField label="Minimal Penarikan (Rp)" error={ketentuanForm.errors.min_tarik} required>
                            <CurrencyField
                                size="sm"
                                value={ketentuanForm.data.min_tarik}
                                onChange={(e) => ketentuanForm.setData('min_tarik', e.target.value)}
                                disabled={readOnly}
                                required
                            />
                        </FormField>
                    </div>

                    {!readOnly && (
                        <div className="pt-2 flex justify-end">
                            <Button
                                type="submit"
                                size="sm"
                                disabled={ketentuanForm.processing}
                                className="rounded-full shadow-md shadow-brand-green/25"
                            >
                                {ketentuanForm.processing ? 'Menyimpan...' : 'Simpan Ketentuan'}
                            </Button>
                        </div>
                    )}
                </SectionCard>
            </form>
        </div>
    );
}
