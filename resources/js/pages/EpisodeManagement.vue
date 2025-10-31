<script setup lang="ts">
import EpisodeManagement from '@/components/EpisodeManagement.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { episodesDashboard } from '@/routes';
import { type BreadcrumbItem, type Episode } from '@/types';
import { Head } from '@inertiajs/vue3';

interface Props {
    episodes: Episode[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Episode Management',
        href: episodesDashboard().url,
    },
];
</script>

<template>
    <Head title="Episode Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Episode Management Section -->
            <div
                class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
            >
                <EpisodeManagement :episodes="props.episodes" />
            </div>
        </div>
    </AppLayout>
</template>
