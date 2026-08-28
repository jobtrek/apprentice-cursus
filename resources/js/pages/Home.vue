<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { edit as editProfile } from '@/routes/profile';
</script>

<template>
    <Head title="Home" />

    <div class="flex flex-col items-start gap-4">
        <h1 class="text-xl font-semibold">You're logged in</h1>

        <Button as-child data-test="profile-link">
            <Link :href="editProfile()">Go to your profile</Link>
        </Button>
    </div>
</template>
