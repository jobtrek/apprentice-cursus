<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

defineProps<{
    track: string | null;
    isMp: boolean;
}>();

/** Une teinte par filière, pour les repérer d'un coup d'œil dans la liste. */
const TRACK_CLASSES: Record<string, string> = {
    IT: 'border-chart-4/30 bg-chart-4/10 text-chart-4',
    EC: 'border-chart-2/40 bg-chart-2/10 text-foreground',
};
</script>

<template>
    <div class="flex flex-wrap items-center gap-1.5">
        <Badge
            v-if="track"
            variant="outline"
            class="font-semibold"
            :class="TRACK_CLASSES[track]"
        >
            {{ track }}
        </Badge>
        <span v-else class="text-muted-foreground">—</span>
        <Badge v-if="isMp" variant="secondary" title="Maturité professionnelle">
            MP
        </Badge>
    </div>
</template>
