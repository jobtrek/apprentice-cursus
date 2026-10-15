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
import type { AssignableApprentice } from '@/types/apprentice';

const props = defineProps<{
    /** Apprentis sans coach. */
    assignable: AssignableApprentice[];
}>();

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
                    Apprentis qui n'ont pas encore de coach. En l'ajoutant, vous
                    devenez son coach.
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
                        ? 'Tous les apprentis ont déjà un coach.'
                        : 'Aucun apprenti ne correspond à la recherche.'
                }}
            </p>
        </DialogContent>
    </Dialog>
</template>
