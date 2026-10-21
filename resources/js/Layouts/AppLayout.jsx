import SidebarLayout from './SidebarLayout';
import NavbarLayout from './NavbarLayout';

const LAYOUT_MODE = 'sidebar';

export default function AppLayout({ children }) {
    return LAYOUT_MODE === 'navbar'
        ? <NavbarLayout>{children}</NavbarLayout>
        : <SidebarLayout>{children}</SidebarLayout>;
}