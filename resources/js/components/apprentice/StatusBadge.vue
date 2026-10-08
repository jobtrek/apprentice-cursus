<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { TONES, type Tone } from '@/lib/apprentice';
import { cn } from '@/lib/utils';

const props = defineProps<{
    tone: Tone;
    label: string;
    class?: string;
}>();
</script>

<template>
    <!-- Badge teinté avec pastille, comme les statuts des tableaux de données. -->
    <Badge
        :class="
            cn(
                'gap-1.5 border-transparent font-medium',
                TONES[tone].soft,
                props.class,
            )
        "
    >
        <span
            class="size-1.5 shrink-0 rounded-full"
            :class="TONES[tone].dot"
            aria-hidden="true"
        />
        {{ label }}
    </Badge>
</template>
