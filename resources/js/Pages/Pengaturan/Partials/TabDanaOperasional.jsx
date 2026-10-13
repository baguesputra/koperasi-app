import SectionCard from './SectionCard';
import TabSimpanan from './TabSimpanan';
import TabChip from './TabChip';

export default function TabDanaOperasional({ settingSimpanan, chipNominal, sectionAktif }) {
    const grupChip = Object.keys(chipNominal ?? {}).length;

    return (
        <div className="space-y-4">
            <SectionCard
                id="simpanan"
                judul="Simpanan"
                ringkasan={(settingSimpanan ?? []).length ? `${settingSimpanan.length} jenis` : null}
                sectionAktif={sectionAktif}
            >
                <TabSimpanan settingSimpanan={settingSimpanan} />
            </SectionCard>
            <SectionCard
                id="chip"
                judul="Chip Nominal"
                ringkasan={grupChip ? `${grupChip} grup` : null}
                sectionAktif={sectionAktif}
            >
                <TabChip chipNominal={chipNominal} />
            </SectionCard>
        </div>
    );
}
