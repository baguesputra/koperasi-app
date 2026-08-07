import Sidebar from './Partials/Sidebar';
import Topbar from './Partials/Topbar';

export default function SidebarLayout({ children }) {
    return (
        <div className="flex min-h-screen bg-gray-50">
            <Sidebar />
            <div className="flex-1 flex flex-col min-w-0">
                <Topbar />
                <main className="flex-1 p-6 lg:p-8 max-w-[1400px] w-full mx-auto">
                    {children}
                </main>
            </div>
        </div>
    );
}