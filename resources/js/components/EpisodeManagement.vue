<script setup lang="ts">
// #######################################
// Imports
// #######################################

// ##############################
// External Libraries
// ##############################

import { router, useForm, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

// ##############################
// UI Components
// ##############################

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';

// ##############################
// Utilities & Types
// ##############################

import { toast } from '@/composables/useToast';
import { type Episode } from '@/types';
import { Edit, Plus, Trash2, Upload } from 'lucide-vue-next';

// #######################################
// Types
// #######################################

interface Props {
    episodes: Episode[];
}

// #######################################
// Component State
// #######################################

// ##############################
// Props & Page Context
// ##############################

const props = defineProps<Props>();
const page = usePage();

// ##############################
// Episode Data
// ##############################

const episodes = ref<Episode[]>(props.episodes);
const editingEpisode = ref<Episode | null>(null);

// ##############################
// Dialog Visibility
// ##############################

const showAddDialog = ref(false);
const showEditDialog = ref(false);

// ##############################
// Form Instances
// ##############################

const createForm = useForm({
    title: '',
    description: '',
    audio_file: null as File | null,
    published_date: new Date().toISOString().split('T')[0],
});

const updateForm = useForm({
    title: '',
    description: '',
    audio_file: null as File | null,
    published_date: '',
});

// ##############################
// Messages & Errors
// ##############################

const errors = ref<Record<string, string>>({});
const successMessage = ref('');

// #######################################
// Utility Functions
// #######################################

// ##############################
// Duration Formatting
// ##############################

const formatDuration = (
    durationInMinutes: number | string | null | undefined,
): string => {
    if (!durationInMinutes) return '0:00';

    // ####################
    // Handle String Input
    // ####################

    if (typeof durationInMinutes === 'string') {
        const str = durationInMinutes.trim();

        // Already in MM:SS format
        if (str.includes(':')) {
            const [m, s] = str.split(':');
            const mm = Math.max(0, parseInt(m || '0', 10));
            const ss = Math.max(0, parseInt(s || '0', 10));
            return `${mm}:${ss.toString().padStart(2, '0')}`;
        }

        // Decimal minutes format
        const num = parseFloat(str);
        if (!isNaN(num) && num > 0) {
            const totalMinutes = Math.floor(num);
            const seconds = Math.round((num - totalMinutes) * 60);
            if (seconds === 60) return `${totalMinutes + 1}:00`;
            return `${totalMinutes}:${seconds.toString().padStart(2, '0')}`;
        }
        return '0:00';
    }

    // ####################
    // Handle Number Input
    // ####################

    const duration = durationInMinutes as number;
    if (!duration || duration <= 0 || isNaN(duration)) return '0:00';
    const totalMinutes = Math.floor(duration);
    const seconds = Math.round((duration - totalMinutes) * 60);
    if (seconds === 60) return `${totalMinutes + 1}:00`;
    return `${totalMinutes}:${seconds.toString().padStart(2, '0')}`;
};

// #######################################
// Data Management
// #######################################

// ##############################
// Loading & Refreshing
// ##############################

const loadEpisodes = async () => {
    episodes.value = props.episodes;
};

const refreshEpisodes = () => {
    router.visit('/dashboard/episodes', {
        preserveState: false,
        preserveScroll: true,
    });
};

// ##############################
// Form Helpers
// ##############################

const resetForm = () => {
    createForm.reset();
    updateForm.reset();
    errors.value = {};
    successMessage.value = '';
};

const makeFileSelectHandler = (form: { audio_file: File | null }) => (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.audio_file = target.files[0];
    }
};

// #######################################
// CRUD Operations
// #######################################

// ##############################
// Create
// ##############################

const createEpisode = async () => {
    createForm.post('/dashboard/episodes', {
        onSuccess: () => {
            toast.success('Episode created successfully!');
            showAddDialog.value = false;
            resetForm();
            refreshEpisodes();
        },
        onError: (errors) => {
            console.error('Error creating episode:', errors);
            toast.error('Failed to create episode. Please try again.');
        },
    });
};

// ##############################
// Read/Edit
// ##############################

const editEpisode = (episode: Episode) => {
    editingEpisode.value = episode;
    updateForm.title = episode.title;
    updateForm.description = episode.description || '';
    updateForm.audio_file = null;
    updateForm.published_date = episode.published_date;
    showEditDialog.value = true;
};

// ##############################
// Update
// ##############################

const updateEpisode = async () => {
    if (!editingEpisode.value) return;

    updateForm.put(`/dashboard/episodes/${editingEpisode.value.id}`, {
        onSuccess: () => {
            toast.success('Episode updated successfully!');
            showEditDialog.value = false;
            editingEpisode.value = null;
            resetForm();
            refreshEpisodes();
        },
        onError: (errors) => {
            console.error('Error updating episode:', errors);
            toast.error('Failed to update episode. Please try again.');
        },
    });
};

// ##############################
// Delete
// ##############################

const deleteEpisode = async (id: number) => {
    if (!confirm('Are you sure you want to delete this episode?')) return;

    try {
        // ####################
        // Get CSRF Token
        // ####################

        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') || '';

        // ####################
        // Send Delete Request
        // ####################

        const response = await fetch(`/dashboard/episodes/${id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
            credentials: 'same-origin',
        });

        // ####################
        // Handle Response
        // ####################

        if (response.ok) {
            toast.success('Episode deleted successfully!');
            refreshEpisodes();
        } else {
            const data = await response.json().catch(() => null);
            console.error('Delete failed:', data || response.statusText);
            toast.error('Failed to delete episode.');
        }
    } catch (error) {
        console.error('Error deleting episode:', error);
        toast.error('An error occurred while deleting the episode.');
    }
};

// ##############################
// Sync
// ##############################

const syncEpisodes = async () => {
    try {
        await router.post('/dashboard/episodes/sync', {}, {
            onSuccess: () => {
                toast.success('Sync queued to Redis');
                refreshEpisodes();
            },
            onError: () => {
                toast.error('Failed to queue sync');
            },
        } as any);
    } catch (e) {
        toast.error('Failed to queue sync');
        console.error(e);
    }
};

// #######################################
// Lifecycle Hooks
// #######################################

onMounted(() => {
    // ####################
    // Load Episodes
    // ####################

    loadEpisodes();

    // ####################
    // Handle Flash Messages
    // ####################

    const flashProps = page.props as any;
    const flashTypes = ['success', 'error'] as const;
    flashTypes.forEach(type => {
        if (flashProps.flash?.[type]) {
            toast[type](flashProps.flash[type]);
        }
    });
});
</script>

<template>
    <div class="space-y-6">
        <!-- ======================================= -->
        <!-- Header                                  -->
        <!-- ======================================= -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">
                    Episode Management
                </h2>
                <p class="text-muted-foreground">
                    Manage your podcast episodes
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Button variant="outline" @click="syncEpisodes">Sync to Redis</Button>

                <!-- ============================== -->
                <!-- Add Episode Dialog             -->
                <!-- ============================== -->
                <Dialog v-model:open="showAddDialog">
                <DialogTrigger asChild>
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Add Episode
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Add New Episode</DialogTitle>
                        <DialogDescription>
                            Upload a new episode to your podcast.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <!-- ==================== -->
                        <!-- Title Field          -->
                        <!-- ==================== -->
                        <div class="grid gap-2">
                            <Label htmlFor="title">Title</Label>
                            <Input
                                id="title"
                                v-model="createForm.title"
                                placeholder="Episode title"
                                :class="{
                                    'border-red-500': createForm.errors.title,
                                }"
                            />
                            <p
                                v-if="createForm.errors.title"
                                class="text-sm text-red-500"
                            >
                                {{ createForm.errors.title }}
                            </p>
                        </div>

                        <!-- ==================== -->
                        <!-- Description Field    -->
                        <!-- ==================== -->
                        <div class="grid gap-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                v-model="createForm.description"
                                placeholder="Episode description"
                                :class="{
                                    'border-red-500':
                                        createForm.errors.description,
                                }"
                            />
                            <p
                                v-if="createForm.errors.description"
                                class="text-sm text-red-500"
                            >
                                {{ createForm.errors.description }}
                            </p>
                        </div>

                        <!-- ==================== -->
                        <!-- Audio File Field     -->
                        <!-- ==================== -->
                        <div class="grid gap-2">
                            <Label htmlFor="audio_file">Audio File</Label>
                            <Input
                                id="audio_file"
                                type="file"
                                accept=".mp3,.m4a"
                                @change="makeFileSelectHandler(createForm)"
                                :class="{
                                    'border-red-500':
                                        createForm.errors.audio_file,
                                }"
                            />
                            <p
                                v-if="createForm.errors.audio_file"
                                class="text-sm text-red-500"
                            >
                                {{ createForm.errors.audio_file }}
                            </p>
                        </div>

                        <!-- ==================== -->
                        <!-- Published Date Field -->
                        <!-- ==================== -->
                        <div class="grid gap-2">
                            <Label htmlFor="published_date"
                                >Published Date</Label
                            >
                            <Input
                                id="published_date"
                                type="date"
                                v-model="createForm.published_date"
                                :class="{
                                    'border-red-500':
                                        createForm.errors.published_date,
                                }"
                            />
                            <p
                                v-if="createForm.errors.published_date"
                                class="text-sm text-red-500"
                            >
                                {{ createForm.errors.published_date }}
                            </p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="showAddDialog = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            @click="createEpisode"
                            :disabled="createForm.processing"
                        >
                            <Upload
                                v-if="!createForm.processing"
                                class="mr-2 h-4 w-4"
                            />
                            {{
                                createForm.processing
                                    ? 'Creating...'
                                    : 'Create Episode'
                            }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
                </Dialog>
            </div>
        </div>

        <!-- ======================================= -->
        <!-- Flash Messages                         -->
        <!-- ======================================= -->

        <!-- ============================== -->
        <!-- Success Message                -->
        <!-- ============================== -->
        <div v-if="successMessage" class="rounded-md bg-green-50 p-4">
            <div class="text-sm text-green-800">
                {{ successMessage }}
            </div>
        </div>

        <!-- ============================== -->
        <!-- Error Messages                 -->
        <!-- ============================== -->
        <div
            v-if="Object.keys(errors).length > 0"
            class="rounded-md bg-red-50 p-4"
        >
            <div class="text-sm text-red-800">
                <ul class="list-inside list-disc space-y-1">
                    <li v-for="(error, key) in errors" :key="key">
                        {{ error }}
                    </li>
                </ul>
            </div>
        </div>

        <!-- ======================================= -->
        <!-- Episodes Table                         -->
        <!-- ======================================= -->
        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Title</TableHead>
                        <TableHead>Duration</TableHead>
                        <TableHead>File Size</TableHead>
                        <TableHead>Format</TableHead>
                        <TableHead>Published Date</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <!-- ============================== -->
                    <!-- Episode Rows                   -->
                    <!-- ============================== -->
                    <TableRow v-for="episode in episodes" :key="episode.id">
                        <TableCell class="font-medium">
                            <div>
                                <div class="font-semibold">
                                    {{ episode.title }}
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    {{ episode.description }}
                                </div>
                            </div>
                        </TableCell>
                        <TableCell>{{
                            formatDuration(episode.duration)
                        }}</TableCell>
                        <TableCell>{{ episode.file_size }}</TableCell>
                        <TableCell>{{
                            episode.format.toUpperCase()
                        }}</TableCell>
                        <TableCell>{{
                            new Date(
                                episode.published_date,
                            ).toLocaleDateString()
                        }}</TableCell>
                        <TableCell class="text-right">
                            <div class="flex justify-end gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="editEpisode(episode)"
                                >
                                    <Edit class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="deleteEpisode(episode.id)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>

                    <!-- ============================== -->
                    <!-- Empty State                    -->
                    <!-- ============================== -->
                    <TableRow v-if="episodes.length === 0">
                        <TableCell
                            colspan="6"
                            class="py-8 text-center text-muted-foreground"
                        >
                            No episodes found. Add your first episode to get
                            started.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- ======================================= -->
        <!-- Edit Episode Dialog                    -->
        <!-- ======================================= -->
        <Dialog v-model:open="showEditDialog">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Edit Episode</DialogTitle>
                    <DialogDescription>
                        Update episode information.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <!-- ==================== -->
                    <!-- Title Field          -->
                    <!-- ==================== -->
                    <div class="grid gap-2">
                        <Label htmlFor="edit_title">Title</Label>
                        <Input
                            id="edit_title"
                            v-model="updateForm.title"
                            placeholder="Episode title"
                            :class="{
                                'border-red-500': updateForm.errors.title,
                            }"
                        />
                        <p
                            v-if="updateForm.errors.title"
                            class="text-sm text-red-500"
                        >
                            {{ updateForm.errors.title }}
                        </p>
                    </div>

                    <!-- ==================== -->
                    <!-- Description Field    -->
                    <!-- ==================== -->
                    <div class="grid gap-2">
                        <Label htmlFor="edit_description">Description</Label>
                        <Textarea
                            id="edit_description"
                            v-model="updateForm.description"
                            placeholder="Episode description"
                            :class="{
                                'border-red-500': updateForm.errors.description,
                            }"
                        />
                        <p
                            v-if="updateForm.errors.description"
                            class="text-sm text-red-500"
                        >
                            {{ updateForm.errors.description }}
                        </p>
                    </div>

                    <!-- ==================== -->
                    <!-- Audio File Field     -->
                    <!-- ==================== -->
                    <div class="grid gap-2">
                        <Label htmlFor="edit_audio_file"
                            >Audio File (optional)</Label
                        >
                        <Input
                            id="edit_audio_file"
                            type="file"
                            accept=".mp3,.m4a"
                            @change="makeFileSelectHandler(updateForm)"
                            :class="{
                                'border-red-500': updateForm.errors.audio_file,
                            }"
                        />
                        <p
                            v-if="updateForm.errors.audio_file"
                            class="text-sm text-red-500"
                        >
                            {{ updateForm.errors.audio_file }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            Leave empty to keep the current audio file.
                        </p>
                    </div>

                    <!-- ==================== -->
                    <!-- Published Date Field -->
                    <!-- ==================== -->
                    <div class="grid gap-2">
                        <Label htmlFor="edit_published_date"
                            >Published Date</Label
                        >
                        <Input
                            id="edit_published_date"
                            type="date"
                            v-model="updateForm.published_date"
                            :class="{
                                'border-red-500':
                                    updateForm.errors.published_date,
                            }"
                        />
                        <p
                            v-if="updateForm.errors.published_date"
                            class="text-sm text-red-500"
                        >
                            {{ updateForm.errors.published_date }}
                        </p>
                    </div>
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="showEditDialog = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        @click="updateEpisode"
                        :disabled="updateForm.processing"
                    >
                        {{
                            updateForm.processing
                                ? 'Updating...'
                                : 'Update Episode'
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
