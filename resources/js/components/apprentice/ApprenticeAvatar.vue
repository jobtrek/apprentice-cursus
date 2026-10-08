<script setup lang="ts">
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import { TONES, type Tone } from '@/lib/apprentice';
import { cn } from '@/lib/utils';

const props = defineProps<{
    name: string | undefined;
    /** Pastille de situation dans le coin, sans pastille si absent. */
    tone?: Tone;
    class?: string;
    fallbackClass?: string;
}>();
</script>

<template>
    <div class="relative inline-flex shrink-0">
        <Avatar :class="cn('size-9', props.class)">
            <AvatarFallback
                :class="
                    cn(
                        'bg-primary/10 text-primary text-xs font-semibold',
                        fallbackClass,
                    )
                "
            >
                {{ getInitials(name) }}
            </AvatarFallback>
        </Avatar>
        <span
            v-if="tone"
            class="ring-card absolute right-0 bottom-0 size-2.5 rounded-full ring-2"
            :class="TONES[tone].dot"
            aria-hidden="true"
        />
    </div>
</template>
