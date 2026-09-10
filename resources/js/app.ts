import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    // L'aperçu du portfolio se rend sans mise en page applicative : il doit
    // correspondre exactement à ce qui sera imprimé.
    layout: (name) => {
        if (name === 'PortfolioPreview') {
            return undefined;
        }

        return name.startsWith('auth/') ? AuthLayout : AppLayout;
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
