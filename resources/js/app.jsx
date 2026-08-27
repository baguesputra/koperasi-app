import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import ErrorModalProvider from './Components/ErrorModalProvider';

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
                <App {...props} />
            </ErrorModalProvider>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

if ('serviceWorker' in navigator) {
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
}
