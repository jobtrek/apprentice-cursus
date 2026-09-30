<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
    FieldSeparator,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import login from '@/routes/login';
import microsoft from '@/routes/microsoft';
import { Head, useForm } from '@inertiajs/vue3';
import MicrosoftLogo from '../../../assets/microsoft (2).svg';

defineOptions({
    layout: {
        title: 'Connexion',
        description:
            'Connectez-vous avec votre compte Microsoft ou votre adresse e-mail.',
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
        <CardContent class="flex flex-col gap-6">
            <form :action="microsoft.redirect.url()">
                <Button type="submit" variant="outline" class="w-full">
                    <img :src="MicrosoftLogo" alt="" class="size-5" />
                    Se connecter avec Microsoft
                </Button>
            </form>

            <FieldSeparator>ou</FieldSeparator>

            <form @submit.prevent="submit">
                <FieldGroup>
                    <Field :data-invalid="!!form.errors.email">
                        <FieldLabel for="email">Adresse e-mail</FieldLabel>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            name="email"
                            autocomplete="email"
                            required
                        />
                        <FieldError :errors="[form.errors.email]" />
                    </Field>

                    <Field :data-invalid="!!form.errors.password">
                        <FieldLabel for="password">Mot de passe</FieldLabel>
                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            required
                        />
                        <FieldError :errors="[form.errors.password]" />
                    </Field>

                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="form.processing"
                    >
                        Se connecter
                    </Button>
                </FieldGroup>
            </form>
        </CardContent>
    </Card>
</template>
