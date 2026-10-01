<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { UserPlusIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import SearchInput from '@/components/SearchInput.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { getInitials } from '@/composables/useInitials';
import apprentices from '@/routes/apprentices';
import type { AssignableApprentice, AssignSelfAs } from '@/types/apprentice';

const props = defineProps<{
    /** Coach : sans coach. Formateur : sans formateur, de sa filière. */
    assignable: AssignableApprentice[];
    /** Rôle que l'utilisateur prend auprès de l'apprenti·e ajouté·e. */
    as: AssignSelfAs;
}>();

const TEXTS = {
    coach: {
        description:
            "Apprentis qui n'ont pas encore de coach. En l'ajoutant, vous devenez son coach.",
        empty: 'Tous les apprentis ont déjà un coach.',
    },
    trainer: {
        description:
            "Apprentis de votre filière qui n'ont pas encore de formateur. En l'ajoutant, vous devenez son formateur.",
        empty: 'Tous les apprentis de votre filière ont déjà un formateur.',
    },
} as const;

const open = defineModel<boolean>('open', { required: true });

const search = ref('');
const assigningId = ref<number | null>(null);

const results = computed(() => {
    const query = search.value.trim().toLocaleLowerCase('fr');

    return query
        ? props.assignable.filter((apprentice) =>
              apprentice.name.toLocaleLowerCase('fr').includes(query),
          )
        : props.assignable;
});

function assign(apprentice: AssignableApprentice): void {
    router.post(
        apprentices.assign.url(apprentice.id),
        {},
        {
            preserveScroll: true,
            onStart: () => (assigningId.value = apprentice.id),
            onFinish: () => (assigningId.value = null),
        },
    );
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="flex max-h-[80svh] flex-col gap-4 sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Ajouter un apprenti</DialogTitle>
                <DialogDescription>
                    {{ TEXTS[as].description }}
                </DialogDescription>
            </DialogHeader>

            <SearchInput
                v-model="search"
                placeholder="Rechercher un apprenti"
            />

            <ul
                v-if="results.length > 0"
                class="-mx-2 flex flex-col overflow-y-auto"
            >
                <li
                    v-for="apprentice in results"
                    :key="apprentice.id"
                    class="hover:bg-muted/50 flex items-center gap-3 rounded-md px-2 py-2"
                >
                    <Avatar class="size-8">
                        <AvatarFallback class="text-xs">
                            {{ getInitials(apprentice.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <span class="min-w-0 flex-1 truncate text-sm font-medium">
                        {{ apprentice.name }}
                    </span>
                    <Badge v-if="apprentice.track" variant="outline">
                        {{ apprentice.track }}
                    </Badge>
                    <Button
                        size="sm"
                        :disabled="assigningId !== null"
                        :data-test="`assign-apprentice-${apprentice.id}`"
                        @click="assign(apprentice)"
                    >
                        <UserPlusIcon aria-hidden="true" />
                        Ajouter
                    </Button>
                </li>
            </ul>

            <p v-else class="text-muted-foreground py-6 text-center text-sm">
                {{
                    assignable.length === 0
                        ? TEXTS[as].empty
                        : 'Aucun apprenti ne correspond à la recherche.'
                }}
            </p>
        </DialogContent>
    </Dialog>
</template>
