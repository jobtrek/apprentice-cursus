import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    findActiveNavItem,
    NAV_ITEMS,
    navItemsFor,
} from '@/constants/navigation';

/** Navigation principale de l'utilisateur connecté et élément actif. */
export const useNavigation = () => {
    const page = usePage();

    const role = computed(() => page.props.auth?.user?.role ?? null);

    const can = computed(() => page.props.auth?.can ?? null);

    const items = computed(() => navItemsFor(NAV_ITEMS, can.value));

    const activeItem = computed(() => findActiveNavItem(items.value, page.url));

    return { role, can, items, activeItem };
};
