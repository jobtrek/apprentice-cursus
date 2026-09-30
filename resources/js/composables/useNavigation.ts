import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    findActiveNavItem,
    NAV_ITEMS,
    navItemsForRole,
} from '@/constants/navigation';

/** Navigation principale de l'utilisateur connecté et élément actif. */
export const useNavigation = () => {
    const page = usePage();

    const role = computed(() => page.props.auth?.user?.role ?? null);

    const items = computed(() => navItemsForRole(NAV_ITEMS, role.value));

    const activeItem = computed(() => findActiveNavItem(items.value, page.url));

    return { role, items, activeItem };
};
