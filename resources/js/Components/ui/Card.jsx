export default function Card({ children, className = '', padding = 'normal', tone = 'white' }) {
    const paddings = {
        none: '',
        sm: 'p-3 sm:p-3.5',
        normal: 'p-4 sm:p-5',
        lg: 'p-5 sm:p-6',
    };

    const tones = {
        white: 'bg-white border-slate-200/75',
        danger: 'bg-gradient-to-br from-red-50/90 to-white border-red-200/75',
        warning: 'bg-gradient-to-br from-amber-50/90 to-white border-amber-200/75',
        muted: 'bg-gradient-to-br from-slate-50/80 via-white to-brand-green-light/20 border-slate-200/75',
    };

    return (
        <div className={`rounded-2xl sm:rounded-xl border shadow-[0_2px_10px_-3px_rgba(15,30,54,0.06),0_1px_3px_rgba(15,30,54,0.04)] ${paddings[padding]} ${tones[tone]} ${className}`}>
            {children}
        </div>
    );
}