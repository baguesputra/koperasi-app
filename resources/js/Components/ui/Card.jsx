export default function Card({ children, className = '', padding = 'normal', tone = 'white' }) {
    const paddings = {
        none: '',
        sm: 'p-3 sm:p-3.5',
        normal: 'p-4 sm:p-5',
        lg: 'p-5 sm:p-6',
    };

    const tones = {
        white: 'bg-white border-slate-100',
        danger: 'bg-red-50 border-red-100',
        warning: 'bg-amber-50 border-amber-100',
        muted: 'bg-slate-50 border-slate-100',
    };

    return (
        <div className={`rounded-2xl sm:rounded-xl border shadow-sm ${paddings[padding]} ${tones[tone]} ${className}`}>
            {children}
        </div>
    );
}