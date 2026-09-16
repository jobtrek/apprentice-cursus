import { computed, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance };

const COOKIE_MAX_AGE = 365 * 24 * 60 * 60;

const mediaQuery = (): MediaQueryList | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const getStoredAppearance = (): Appearance | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    return localStorage.getItem('appearance') as Appearance | null;
};

const systemIsDark = ref(mediaQuery()?.matches ?? false);

const appearance = ref<Appearance>(getStoredAppearance() ?? 'system');

export const updateTheme = (value: Appearance): void => {
    if (typeof window === 'undefined') {
        return;
    }

    const isDark =
        value === 'system' ? mediaQuery()?.matches === true : value === 'dark';

    document.documentElement.classList.toggle('dark', isDark);
};

const handleSystemThemeChange = (): void => {
    systemIsDark.value = mediaQuery()?.matches ?? false;

    updateTheme(getStoredAppearance() ?? 'system');
};

export const initializeTheme = (): void => {
    if (typeof window === 'undefined') {
        return;
    }

    // Initialize theme from saved preference or default to system...
    const savedAppearance = getStoredAppearance();
    appearance.value = savedAppearance ?? 'system';
    updateTheme(appearance.value);

    // Set up system theme change listener...
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
};

export const useAppearance = () => {
    const resolvedAppearance = computed<ResolvedAppearance>(() =>
        appearance.value === 'system'
            ? systemIsDark.value
                ? 'dark'
                : 'light'
            : appearance.value,
    );

    const updateAppearance = (value: Appearance): void => {
        appearance.value = value;

        if (typeof window === 'undefined') {
            return;
        }

        localStorage.setItem('appearance', value);

        // `HandleAppearance` lit ce cookie pour que `app.blade.php` applique
        // la bonne classe dès le HTML : sans lui, le thème clignote à chaque
        // rechargement complet.
        document.cookie = `appearance=${value};path=/;max-age=${COOKIE_MAX_AGE};SameSite=Lax`;

        updateTheme(value);
    };

    const toggleAppearance = (): void =>
        updateAppearance(
            resolvedAppearance.value === 'dark' ? 'light' : 'dark',
        );

    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
        toggleAppearance,
    };
};
