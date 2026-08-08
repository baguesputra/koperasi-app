import SidebarLayout from './SidebarLayout';
import NavbarLayout from './NavbarLayout';

const LAYOUT_MODE = 'sidebar';

export default function AppLayout({ children }) {
    if (LAYOUT_MODE === 'navbar') {
        return <NavbarLayout>{children}</NavbarLayout>;
    }

    return <SidebarLayout>{children}</SidebarLayout>;
}