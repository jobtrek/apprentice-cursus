<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import microsoft from '@/routes/microsoft';
import { Head } from '@inertiajs/vue3';
import MicrosoftLogo from '../../../assets/microsoft (2).svg';

defineOptions({
    layout: {
        title: 'Log in to your account',
        description: 'Sign in with your Microsoft account to continue',
    },
});

defineProps<{
    status?: string;
    error?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Log in" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <div
        v-if="error"
        class="mb-4 text-center text-sm font-medium text-destructive"
    >
        {{ error }}
    </div>

    <Card class="mx-auto w-full max-w-sm">
        <CardContent>
            <form :action="microsoft.redirect.url()">
                <Button type="submit" variant="outline" class="w-full" :tabindex="6">
                    <img :src="MicrosoftLogo" alt="" class="mr-2 h-5 w-5" />
                    Log in with Microsoft
                </Button>
            </form>
        </CardContent>
    </Card>
</template>
