<script setup lang="ts">
import type { LinkComponentBaseProps } from '@inertiajs/core';
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

export interface Shortcut {
    icon: Component;
    title: string;
    description: string;
    /** Page ouverte ; sans `href`, la carte est un bouton qui émet `select`. */
    href?: LinkComponentBaseProps['href'];
}

defineProps<{ items: Shortcut[] }>();

defineEmits<{ select: [item: Shortcut] }>();
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <component
            :is="item.href ? Link : 'button'"
            v-for="item in items"
            :key="item.title"
            v-bind="item.href ? { href: item.href } : { type: 'button' }"
            class="group focus-visible:ring-ring/50 rounded-xl text-left focus-visible:ring-[3px] focus-visible:outline-none"
            @click="item.href ? undefined : $emit('select', item)"
        >
            <Card
                class="group-hover:border-primary/50 group-hover:bg-accent/40 h-full transition-colors"
            >
                <CardHeader>
                    <div
                        class="bg-primary/10 text-primary mb-2 flex size-10 items-center justify-center rounded-lg"
                    >
                        <component
                            :is="item.icon"
                            class="size-5"
                            aria-hidden="true"
                        />
                    </div>
                    <CardTitle>{{ item.title }}</CardTitle>
                    <CardDescription>{{ item.description }}</CardDescription>
                </CardHeader>
            </Card>
        </component>
    </div>
</template>
