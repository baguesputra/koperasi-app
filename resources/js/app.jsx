import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import ErrorModalProvider from './Components/ErrorModalProvider';
import { PrivacyProvider } from './Contexts/PrivacyContext';

const appName = import.meta.env.VITE_APP_NAME || 'Koperasi App';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <ErrorModalProvider>
                <PrivacyProvider>
                    <App {...props} />
                </PrivacyProvider>
            </ErrorModalProvider>,
        );
    },
    progress: false,
});

if ('serviceWorker' in navigator) {
    if (import.meta.env.PROD) {
        import('workbox-window').then(({ Workbox }) => {
            const wb = new Workbox('/sw.js');
            wb.addEventListener('installed', (event) => {
                if (!event.isUpdate) {
                    console.log('PWA: App cached and ready for offline use');
                } else {
                    console.log('PWA: New version available, please refresh');
                }
            });
            wb.register();
        });
    } else {
        navigator.serviceWorker.getRegistrations().then((regs) => regs.forEach((r) => r.unregister()));
    }
}
