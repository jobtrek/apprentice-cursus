import { usePage } from '@inertiajs/vue3';
import type { Permissions } from '@/types';

/**
 * Vue counterpart of Blade's `@can`: reads the abilities shared by
 * HandleInertiaRequests. Only hides UI, the server still authorizes.
 *
 * `v-if="can('createGrade')"`
 */
export const useCan = () => {
    const page = usePage();

    const can = (ability: keyof Permissions): boolean =>
        page.props.auth?.can?.[ability] ?? false;

    return { can };
};
