import { ShieldAlert } from 'lucide-react';
import SectionCard from './SectionCard';
import TabSimpanan from './TabSimpanan';
import TabChip from './TabChip';

export default function TabDanaOperasional({ settingSimpanan, chipNominal, sectionAktif, readOnly = false }) {
    const grupChip = Object.keys(chipNominal ?? {}).length;

    return (
        <div className="space-y-4">
            {readOnly && (
                <div className="flex items-center gap-2 p-3 bg-amber-50/70 border border-amber-200/80 text-amber-800 rounded-xl text-xs font-medium">
                    <ShieldAlert size={16} className="text-amber-600 shrink-0" />
                    <span>Mode Baca Saja — Anda hanya dapat melihat pengaturan ini. Perubahan kebijakan wewenang Ketua Koperasi atau Super Admin.</span>
                </div>
            )}
            <SectionCard
                id="simpanan"
                judul="Simpanan"
                ringkasan={(settingSimpanan ?? []).length ? `${settingSimpanan.length} jenis` : null}
                sectionAktif={sectionAktif}
            >
                <TabSimpanan settingSimpanan={settingSimpanan} readOnly={readOnly} />
            </SectionCard>
            <SectionCard
                id="chip"
                judul="Chip Nominal"
                ringkasan={grupChip ? `${grupChip} grup` : null}
                sectionAktif={sectionAktif}
            >
                <TabChip chipNominal={chipNominal} readOnly={readOnly} />
            </SectionCard>
        </div>
    );
}
