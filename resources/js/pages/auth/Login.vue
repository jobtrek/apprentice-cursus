<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import login from '@/routes/login';
import microsoft from '@/routes/microsoft';
import { Head, useForm } from '@inertiajs/vue3';
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

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post(login.store.url(), {
        onFinish: () => form.reset('password'),
    });
};
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
        <CardContent class="space-y-4">
            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        v-model="form.email"
                        autofocus
                        autocomplete="username"
                        :tabindex="1"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="space-y-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        v-model="form.password"
                        autocomplete="current-password"
                        :tabindex="2"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <Button type="submit" class="w-full" :disabled="form.processing" :tabindex="3">
                    Log in
                </Button>
            </form>

            <div class="flex items-center gap-2">
                <Separator class="flex-1" />
                <span class="text-xs text-muted-foreground">or</span>
                <Separator class="flex-1" />
            </div>

            <form :action="microsoft.redirect.url()">
                <Button type="submit" variant="outline" class="w-full" :tabindex="4">
                    <img :src="MicrosoftLogo" alt="" class="mr-2 h-5 w-5" />
                    Log in with Microsoft
                </Button>
            </form>
        </CardContent>
    </Card>
</template>
