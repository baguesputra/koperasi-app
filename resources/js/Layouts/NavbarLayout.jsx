import Navbar from './Partials/Navbar';
import Topbar from './Partials/Topbar';

export default function NavbarLayout({ children }) {
    return (
        <div className="min-h-screen bg-slate-50">
            <div className="sticky top-0 z-50">
                <Navbar />
                <Topbar />
            </div>
            <main className="p-6 lg:p-8 max-w-[1400px] w-full mx-auto">
                {children}
            </main>
        </div>
    );
}