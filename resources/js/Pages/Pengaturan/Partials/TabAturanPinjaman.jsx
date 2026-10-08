import SectionCard from './SectionCard';
import TabBunga from './TabBunga';
import TabLimit from './TabLimit';
import TabTenor from './TabTenor';

export default function TabAturanPinjaman({ bungaSaatIni, limitPinjaman, tabelTenor, sectionAktif }) {
    const ringkasLimit = (limitPinjaman ?? []).length
        ? `${(limitPinjaman ?? []).length} kategori`
        : null;
    const ringkasTenor = (tabelTenor ?? []).length
        ? `${(tabelTenor ?? []).length} rentang`
        : null;

    return (
        <div className="space-y-4">
            <SectionCard
                id="bunga"
                judul="Bunga"
                ringkasan={bungaSaatIni?.persentase != null ? `${bungaSaatIni.persentase}% / bln` : null}
                sectionAktif={sectionAktif}
            >
                <TabBunga bungaSaatIni={bungaSaatIni} />
            </SectionCard>
            <SectionCard id="limit" judul="Limit Pinjaman" ringkasan={ringkasLimit} sectionAktif={sectionAktif}>
                <TabLimit limitPinjaman={limitPinjaman} />
            </SectionCard>
            <SectionCard id="tenor" judul="Tenor" ringkasan={ringkasTenor} sectionAktif={sectionAktif}>
                <TabTenor tabelTenor={tabelTenor} />
            </SectionCard>
        </div>
    );
}
