import { ref } from 'vue';

// Partagé au niveau du module : n'importe quelle page peut ouvrir la fenêtre
// de création de projet affichée par <AddProjectDialog>.
const isOpen = ref(false);

export const useAddProjectDialog = () => {
    const open = (): void => {
        isOpen.value = true;
    };

    const close = (): void => {
        isOpen.value = false;
    };

    return { isOpen, open, close };
};
