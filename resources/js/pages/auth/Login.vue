<script setup lang="ts">
import microsoftIcon from '@/components/assets/microsoft.svg';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { Form, Head } from '@inertiajs/vue3';

defineOptions({
    layout: {
        title: 'Connectez-vous à votre compte avec Microsoft',
        description: 'Appuyez sur le bouton pour vous connecter avec votre compte Microsoft',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Connexion" />

    <div
        v-if="status"
        class= "text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <Button
                type="submit"
                class="mt-4 w-full"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                <img
                    v-else
                    :src="microsoftIcon"
                    alt=""
                    class="size-4"
                />
                Se connecter avec Microsoft
            </Button>
        </div>
    </Form>
</template>