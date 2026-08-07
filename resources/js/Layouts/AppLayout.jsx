import SidebarLayout from './SidebarLayout';
import NavbarLayout from './NavbarLayout';

// switching navbar dan sidebar
const LAYOUT_MODE = 'sidebar'; //sidebar or navbar

export default function AppLayout({ children }) {
    if (LAYOUT_MODE === 'navbar') {
        return <NavbarLayout>{children}</NavbarLayout>;
    }

    return <SidebarLayout>{children}</SidebarLayout>;
}