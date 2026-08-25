export default function StatWidget({ label, value, icon: Icon, tone = 'navy', compact = false }) {
    const tones = {
        navy: 'bg-brand-navy/5 text-brand-navy',
        green: 'bg-brand-green-light text-brand-green-dark',
        amber: 'bg-amber-50 text-amber-700',
        rose: 'bg-rose-50 text-rose-700',
    };

    if (compact) {
        return (
            <div className="bg-white rounded-xl border border-slate-100 shadow-sm px-4 py-3 flex items-center gap-3 min-h-[48px]">
                <div className={`w-10 h-10 rounded-lg flex items-center justify-center shrink-0 ${tones[tone]}`}>
                    <Icon size={20} />
                </div>
                <div className="min-w-0">
                    <p className="text-sm text-slate-500 leading-snug">{label}</p>
                    <p className="text-xl font-bold text-slate-800 mt-0.5 leading-tight break-words">{value}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <div className={`w-10 h-10 rounded-xl flex items-center justify-center mb-3 ${tones[tone]}`}>
                <Icon size={20} />
            </div>
            <p className="text-sm text-slate-500">{label}</p>
            <p className="text-xl font-bold text-slate-800 mt-0.5">{value}</p>
        </div>
    );
}