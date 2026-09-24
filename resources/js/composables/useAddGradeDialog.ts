import { ref } from 'vue';

// Partagé au niveau du module : n'importe quelle page peut ouvrir la fenêtre
// d'ajout de note affichée par <AddGradeDialog>.
const isOpen = ref(false);

export const useAddGradeDialog = () => {
    const open = (): void => {
        isOpen.value = true;
    };

    const close = (): void => {
        isOpen.value = false;
    };

    return { isOpen, open, close };
};
