import { useEffect } from 'react';

export default function SectionCard({ id, judul, ringkasan, children, sectionAktif }) {
    useEffect(() => {
        if (sectionAktif === id) {
            const el = document.getElementById(id);
            if (!el) return;
            const gerakKecil = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            el.scrollIntoView({ behavior: gerakKecil ? 'auto' : 'smooth', block: 'start' });
        }
    }, [sectionAktif, id]);

    return (
        <section id={id} aria-label={judul} className="scroll-mt-4 rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <div className="flex flex-wrap items-baseline justify-between gap-2 mb-4">
                <h3 className="text-base font-bold text-slate-800">{judul}</h3>
                {ringkasan && (
                    <p className="text-sm font-bold text-brand-navy tabular-nums">{ringkasan}</p>
                )}
            </div>
            {children}
        </section>
    );
}
