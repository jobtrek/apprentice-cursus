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
        title: 'Connexion',
        description:
            'Connectez-vous avec votre compte Microsoft pour continuer.',
    },
});

defineProps<{
    status?: string;
    error?: string;
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
    <Head title="Connexion" />

    <div
        v-if="status"
        class="text-success mb-4 text-center text-sm font-medium"
    >
        {{ status }}
    </div>

    <div
        v-if="error"
        class="text-destructive mb-4 text-center text-sm font-medium"
    >
        {{ error }}
    </div>

    <Card>
        <CardContent>
            <form :action="microsoft.redirect.url()">
                <Button type="submit" variant="outline" class="w-full">
                    <img :src="MicrosoftLogo" alt="" class="size-5" />
                    Se connecter avec Microsoft
                </Button>
            </form>
        </CardContent>
    </Card>
</template>
