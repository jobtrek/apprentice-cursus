/** `Marc Dubois` -> `MD`. Utilisé par les pastilles d'avatar. */
export const getInitials = (name?: string): string =>
    (name ?? '')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('');

export const useInitials = () => ({ getInitials });
