import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'BlogVerse Pro';

// Halaman dimuat lazy per-route (code splitting) agar bundle awal halaman publik tetap kecil.
const pages = import.meta.glob('./Pages/**/*.jsx');

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: async (name) => {
        const loader = pages[`./Pages/${name}.jsx`];
        if (!loader) {
            throw new Error(`Page ${name} not found`);
        }
        return loader();
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#4338ca',
    },
});
