import Navbar from './Partials/Navbar';
import Topbar from './Partials/Topbar';
import MobileNav from './Partials/MobileNav';

export default function NavbarLayout({ children }) {
    return (
        <div className="min-h-screen bg-slate-50">
            <div className="sticky top-0 pt-[env(safe-area-inset-top)] z-50">
                <Navbar />
                <Topbar />
                <MobileNav />
            </div>
            <main className="p-4 sm:p-6 lg:p-8 max-w-[1400px] w-full mx-auto">
                {children}
            </main>
        </div>
    );
}