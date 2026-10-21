import { ShieldAlert } from 'lucide-react';
import SectionCard from './SectionCard';
import TabBunga from './TabBunga';
import TabLimit from './TabLimit';
import TabTenor from './TabTenor';

export default function TabAturanPinjaman({ bungaSaatIni, limitPinjaman, tabelTenor, sectionAktif, readOnly = false }) {
    const ringkasLimit = (limitPinjaman ?? []).length
        ? `${(limitPinjaman ?? []).length} kategori`
        : null;
    const ringkasTenor = (tabelTenor ?? []).length
        ? `${(tabelTenor ?? []).length} rentang`
        : null;

    return (
        <div className="space-y-4">
            {readOnly && (
                <div className="flex items-center gap-2 p-3 bg-amber-50/70 border border-amber-200/80 text-amber-800 rounded-xl text-xs font-medium">
                    <ShieldAlert size={16} className="text-amber-600 shrink-0" />
                    <span>Mode Baca Saja — Anda hanya dapat melihat aturan ini. Perubahan kebijakan wewenang Ketua Koperasi atau Super Admin.</span>
                </div>
            )}
            <SectionCard
                id="bunga"
                judul="Bunga"
                ringkasan={bungaSaatIni?.persentase != null ? `${bungaSaatIni.persentase}% / bln` : null}
                sectionAktif={sectionAktif}
            >
                <TabBunga bungaSaatIni={bungaSaatIni} readOnly={readOnly} />
            </SectionCard>
            <SectionCard id="limit" judul="Limit Pinjaman" ringkasan={ringkasLimit} sectionAktif={sectionAktif}>
                <TabLimit limitPinjaman={limitPinjaman} readOnly={readOnly} />
            </SectionCard>
            <SectionCard id="tenor" judul="Tenor" ringkasan={ringkasTenor} sectionAktif={sectionAktif}>
                <TabTenor tabelTenor={tabelTenor} readOnly={readOnly} />
            </SectionCard>
        </div>
    );
}
