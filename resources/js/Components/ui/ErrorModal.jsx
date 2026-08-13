import {
    Dialog,
    DialogPanel,
    Transition,
    TransitionChild,
} from '@headlessui/react';
import { Check, ArrowRight, RotateCcw, X } from 'lucide-react';
import ButtonLink from '@/Components/ui/ButtonLink';
import Button from '@/Components/ui/Button';
import { errorStatusConfig, statusAksiHref } from '@/Utils/errorStatus';

export default function ErrorModal({ status, onClose }) {
    const cfg = errorStatusConfig[status] ?? errorStatusConfig[500];
    const Icon = cfg.icon;
    const href = cfg.aksi.tipe === 'link' ? statusAksiHref(status) : null;

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
                    <DialogPanel className="relative w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">
                        <button
                            onClick={onClose}
                            className="absolute right-3 top-3 inline-flex items-center justify-center w-9 h-9 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                            title="Tutup"
                        >
                            <X size={18} />
                        </button>

                        <div className="p-6">
                            <div className={`w-16 h-16 rounded-2xl bg-white border border-slate-100 shadow-sm flex items-center justify-center mx-auto mb-4 ${cfg.warna}`}>
                                <Icon size={30} />
                            </div>

                            <p className="text-xs font-bold tracking-widest uppercase text-slate-400 text-center mb-1">Error {status}</p>
                            <h2 className="text-xl font-bold text-slate-800 text-center mb-2">{cfg.title}</h2>
                            <p className="text-sm text-slate-500 text-center leading-relaxed mb-5">{cfg.deskripsi}</p>

                            <div className="bg-slate-100/80 rounded-xl p-4 mb-5 text-left">
                                <p className="text-sm font-bold text-slate-700 mb-2">Yang bisa Anda lakukan</p>
                                <ul className="space-y-1.5">
                                    {cfg.langkah.map((item, i) => (
                                        <li key={i} className="flex items-start gap-2 text-sm text-slate-600">
                                            <span className="mt-0.5 w-4 h-4 rounded-full bg-brand-green-light text-brand-green-dark flex items-center justify-center shrink-0">
                                                <Check size={10} strokeWidth={3} />
                                            </span>
                                            {item}
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            <div className="flex items-center justify-center gap-3">
                                {cfg.aksi.tipe === 'link' ? (
                                    <ButtonLink size="sm" href={href}>
                                        {cfg.aksi.label}
                                        <ArrowRight size={15} />
                                    </ButtonLink>
                                ) : (
                                    <Button size="sm" onClick={() => window.location.reload()}>
                                        <RotateCcw size={15} />
                                        {cfg.aksi.label}
                                    </Button>
                                )}
                            </div>
                        </div>
                    </DialogPanel>
                </TransitionChild>
            </Dialog>
        </Transition>
    );
}