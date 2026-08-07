export default function StatWidget({ label, value, icon: Icon, tone = 'navy' }) {
    const tones = {
        navy: 'bg-brand-navy/5 text-brand-navy',
        green: 'bg-brand-green-light text-brand-green-dark',
        amber: 'bg-amber-50 text-amber-700',
    };

    return (
        <div className="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <div className={`w-11 h-11 rounded-xl flex items-center justify-center mb-3 ${tones[tone]}`}>
                <Icon size={22} />
            </div>
            <p className="text-sm text-slate-500">{label}</p>
            <p className="text-2xl font-bold text-slate-800 mt-1">{value}</p>
        </div>
    );
}