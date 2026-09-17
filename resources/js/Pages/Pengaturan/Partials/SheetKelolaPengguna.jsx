import KelolaPengguna from '@/Components/pengguna/KelolaPengguna';

export default function SheetKelolaPengguna({ pengguna, filterPengguna, daftarRole, tabAktif }) {
    return (
        <KelolaPengguna
            pengguna={pengguna}
            filterPengguna={filterPengguna}
            daftarRole={daftarRole}
            mode="drawer"
            ruteIndex="pengaturan.index"
            paramTambahan={{ tab: tabAktif, panel: 'kelola-pengguna' }}
        />
    );
}
