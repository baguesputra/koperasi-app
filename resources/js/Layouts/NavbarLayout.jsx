import Navbar from './Partials/Navbar';
import Topbar from './Partials/Topbar';

export default function NavbarLayout({ children }) {
    return (
        <div className="min-h-screen bg-gray-50">
            <Navbar />
            <Topbar />
            <main className="p-6">{children}</main>
        </div>
    );
}