/** `Marc Dubois` -> `MD`. Utilisé par les pastilles d'avatar. */
export function getInitials(name?: string): string {
    if (!name) {
        return '';
    }

    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('');
}

export function useInitials() {
    return { getInitials };
}
