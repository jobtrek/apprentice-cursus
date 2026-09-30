import { ref } from 'vue';
import type { PortfolioProject } from '@/types/portfolio';

// Partagé au niveau du module : n'importe quelle page peut ouvrir la fenêtre
// de projet affichée par <ProjectDialog>, en création ou en modification.
const isOpen = ref(false);
const project = ref<PortfolioProject | undefined>();

export const useProjectDialog = () => {
    /** Ouvre la fenêtre vide, pour créer un projet. */
    const openCreate = (): void => {
        project.value = undefined;
        isOpen.value = true;
    };

    /** Ouvre la fenêtre pré-remplie avec `target`, pour le modifier. */
    const openEdit = (target: PortfolioProject): void => {
        project.value = target;
        isOpen.value = true;
    };

    const close = (): void => {
        isOpen.value = false;
    };

    return { isOpen, project, openCreate, openEdit, close };
};
