import { useForm } from '@inertiajs/react';
import { Dialog, DialogPanel, Transition, TransitionChild } from '@headlessui/react';
import { X, RotateCcw, AlertCircle } from 'lucide-react';
import Button from '@/Components/ui/Button';
import FormField from '@/Components/ui/FormField';

export default function AktifkanKembaliDialog({ anggota, onClose }) {
    const { data, setData, post, processing, errors } = useForm({
        alasan_reaktivasi: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('anggota.aktifkan-kembali', anggota.id), {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    }

    return (
        <Transition show={true} leave="duration-200">
            <Dialog as="div" className="fixed inset-0 z-[60] flex items-center justify-center p-4 overflow-y-auto" onClose={onClose}>
                <TransitionChild
                    enter="ease-out duration-200"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="ease-in duration-150"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div className="fixed inset-0 bg-slate-900/60" />
                </TransitionChild>

                <TransitionChild
                    enter="ease-out duration-200"
                    enterFrom="opacity-0 translate-y-4 scale-95"
                    enterTo="opacity-100 translate-y-0 scale-100"
                    leave="ease-in duration-150"
                    leaveFrom="opacity-100 translate-y-0 scale-100"
                    leaveTo="opacity-0 translate-y-4 scale-95"
                >
                    <DialogPanel className="relative w-full max-w-md bg-white rounded-2xl shadow-xl">
                        <button
                            onClick={onClose}
                            className="absolute right-3 top-3 inline-flex items-center justify-center w-9 h-9 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                            title="Tutup"
                        >
                            <X size={18} />
                        </button>

                        <form onSubmit={submit} className="p-6">
                            <div className="w-14 h-14 rounded-2xl bg-brand-green-light text-brand-green-dark flex items-center justify-center mx-auto mb-4">
                                <RotateCcw size={28} />
                            </div>

                            <h2 className="text-xl font-bold text-slate-800 text-center mb-1">
                                Aktifkan Kembali
                            </h2>
                            <p className="text-sm text-slate-500 text-center mb-4">
                                {anggota.nama}
                                <br />
                                <span className="text-xs">{anggota.no_anggota}</span>
                            </p>

                            <div className="rounded-xl bg-amber-50 border border-amber-200 px-3 py-2 flex gap-2 mb-4 text-xs text-amber-800">
                                <AlertCircle size={16} className="shrink-0 mt-0.5" />
                                <span>
                                    Data historis (simpanan, pinjaman, jurnal) tetap tersimpan sebagai audit.
                                    Anggota akan memiliki simpanan & pinjaman fresh.
                                </span>
                            </div>

                            <FormField label="Alasan Reaktivasi" error={errors.alasan_reaktivasi} required>
                                <textarea
                                    value={data.alasan_reaktivasi}
                                    onChange={(e) => setData('alasan_reaktivasi', e.target.value)}
                                    rows={3}
                                    className="block w-full rounded-lg border-slate-200 text-sm focus:border-brand-green focus:ring-brand-green"
                                    placeholder="Contoh: Karyawan kembali masuk setelah cuti, diangkat jadi staff tetap, dll."
                                    required
                                />
                            </FormField>

                            <div className="flex items-center justify-end gap-2 mt-5">
                                <Button type="button" variant="outline" size="sm" onClick={onClose} disabled={processing}>
                                    Batal
                                </Button>
                                <Button type="submit" size="sm" disabled={processing || !data.alasan_reaktivasi}>
                                    {processing ? 'Memproses...' : 'Aktifkan Kembali'}
                                </Button>
                            </div>
                        </form>
                    </DialogPanel>
                </TransitionChild>
            </Dialog>
        </Transition>
    );
}
