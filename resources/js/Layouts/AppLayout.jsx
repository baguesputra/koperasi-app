import SidebarLayout from './SidebarLayout';
import NavbarLayout from './NavbarLayout';

// Navbar dipakai untuk sisi Koperasi (Admin/Bendahara/Ketua) - menu tidak banyak
const LAYOUT_MODE = 'navbar';

export default function AppLayout({ children }) {
    if (LAYOUT_MODE === 'navbar') {
        return <NavbarLayout>{children}</NavbarLayout>;
    }

    return <SidebarLayout>{children}</SidebarLayout>;
}