<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
} from '@/components/ui/card';

defineProps<{
    title: string;
    grade: number;
    weight: string;
}>();
</script>

<template>
    <Card class="gap-2">
        <CardHeader>
            <CardDescription class="line-clamp-2 min-h-10">{{
                title
            }}</CardDescription>
            <CardAction>
                <Badge variant="secondary">{{ weight }}</Badge>
            </CardAction>
        </CardHeader>
        <CardContent>
            <p class="text-3xl font-semibold tabular-nums">
                {{ grade.toFixed(1) }}
            </p>
        </CardContent>
    </Card>
</template>
