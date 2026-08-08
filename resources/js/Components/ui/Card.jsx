export default function Card({ children, className = '', padding = 'normal' }) {
    const paddings = {
        none: '',
        normal: 'p-5',
        lg: 'p-6 sm:p-8',
    };

    return (
        <div className={`bg-white rounded-2xl border border-slate-100 shadow-sm ${paddings[padding]} ${className}`}>
            {children}
        </div>
    );
}