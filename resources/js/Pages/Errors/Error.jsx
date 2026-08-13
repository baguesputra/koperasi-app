import { Head } from '@inertiajs/react';
import { Check, ArrowRight, RotateCcw } from 'lucide-react';
import ButtonLink from '@/Components/ui/ButtonLink';
import Button from '@/Components/ui/Button';
import { errorStatusConfig, statusAksiHref } from '@/Utils/errorStatus';

export default function Error({ status = 500 }) {
    const cfg = errorStatusConfig[status] ?? errorStatusConfig[500];
    const Icon = cfg.icon;
    const href = cfg.aksi.tipe === 'link' ? statusAksiHref(status) : null;

    return (
        <>
            <Head title={cfg.title} />

            <div className="min-h-screen bg-slate-50 flex items-center justify-center px-6 py-12 relative overflow-hidden">
                <div className="absolute -top-28 -right-28 w-96 h-96 rounded-full bg-brand-green/5" />
                <div className="absolute -bottom-32 -left-28 w-[28rem] h-[28rem] rounded-full bg-brand-navy/5" />

                <div className="relative w-full max-w-lg text-center">
                    <div className={`w-20 h-20 mx-auto mb-6 rounded-2xl bg-white border border-slate-100 shadow-sm flex items-center justify-center ${cfg.warna}`}>
                        <Icon size={36} />
                    </div>

                    <p className="text-sm font-bold tracking-widest uppercase text-slate-400 mb-2">Error {status}</p>
                    <h1 className="text-3xl font-bold text-slate-800 mb-3">{cfg.title}</h1>
                    <p className="text-lg text-slate-500 leading-relaxed mb-7">{cfg.deskripsi}</p>

                    <div className="bg-slate-100/80 rounded-2xl p-5 mb-8 text-left">
                        <p className="text-sm font-bold text-slate-700 mb-2.5">Yang bisa Anda lakukan</p>
                        <ul className="space-y-2">
                            {cfg.langkah.map((item, i) => (
                                <li key={i} className="flex items-start gap-2.5 text-sm text-slate-600">
                                    <span className="mt-0.5 w-5 h-5 rounded-full bg-brand-green-light text-brand-green-dark flex items-center justify-center shrink-0">
                                        <Check size={12} strokeWidth={3} />
                                    </span>
                                    {item}
                                </li>
                            ))}
                        </ul>
                    </div>

                    <div className="flex items-center justify-center gap-3 flex-wrap">
                        {cfg.aksi.tipe === 'link' ? (
                            <ButtonLink size="md" href={href}>
                                {cfg.aksi.label}
                                <ArrowRight size={16} />
                            </ButtonLink>
                        ) : (
                            <>
                                <Button size="md" onClick={() => window.location.reload()}>
                                    <RotateCcw size={16} />
                                    {cfg.aksi.label}
                                </Button>
                                <ButtonLink size="md" variant="outline" href={route('dashboard')}>
                                    Kembali ke Dashboard
                                </ButtonLink>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}