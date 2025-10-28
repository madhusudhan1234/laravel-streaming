<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
// Alert dialog functionality will be handled using regular Dialog components
import { Plus, Edit, Trash2, Upload } from 'lucide-vue-next';
import { type Episode } from '@/types';
import { toast } from '@/composables/useToast';

interface Props {
    episodes: Episode[];
}

const props = defineProps<Props>();
const page = usePage();

const episodes = ref<Episode[]>(props.episodes);
const loading = ref(false);
const showAddDialog = ref(false);
const showEditDialog = ref(false);
const editingEpisode = ref<Episode | null>(null);
const deleteDialogOpen = ref<Record<number, boolean>>({});

// Form data using Inertia Form
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

const errors = ref<Record<string, string>>({});
const successMessage = ref('');

// Format duration from decimal minutes to MM:SS format
const formatDuration = (durationInMinutes: number | string | null | undefined): string => {
    // Convert string to number if needed
    const duration = typeof durationInMinutes === 'string' ? parseFloat(durationInMinutes) : durationInMinutes;
    
    if (!duration || duration <= 0 || isNaN(duration)) {
        return '0:00';
    }
    
    const totalMinutes = Math.floor(duration);
    const seconds = Math.round((duration - totalMinutes) * 60);
    
    // Handle case where seconds round to 60
    if (seconds === 60) {
        return `${totalMinutes + 1}:00`;
    }
    
    return `${totalMinutes}:${seconds.toString().padStart(2, '0')}`;
};

// Load episodes from props (no need for API call)
const loadEpisodes = async () => {
    episodes.value = props.episodes;
};

// Reset form
const resetForm = () => {
    createForm.reset();
    updateForm.reset();
    errors.value = {};
    successMessage.value = '';
};

// Handle file selection
const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        createForm.audio_file = target.files[0];
    }
};

const handleEditFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        updateForm.audio_file = target.files[0];
    }
};

// Create episode
const createEpisode = async () => {
    createForm.post('/dashboard/episodes', {
        onSuccess: (page) => {
            toast.success('Episode created successfully!');
            showAddDialog.value = false;
            resetForm();
            // Use Inertia visit to refresh with fresh data
            router.visit('/dashboard/episodes', {
                preserveState: false,
                preserveScroll: true
            });
        },
        onError: (errors) => {
            console.error('Error creating episode:', errors);
            toast.error('Failed to create episode. Please try again.');
        }
    });
};

// Edit episode
const editEpisode = (episode: Episode) => {
    editingEpisode.value = episode;
    updateForm.title = episode.title;
    updateForm.description = episode.description || '';
    updateForm.audio_file = null;
    updateForm.published_date = episode.published_date;
    showEditDialog.value = true;
};

// Update episode
const updateEpisode = async () => {
    if (!editingEpisode.value) return;
    
    updateForm.put(`/dashboard/episodes/${editingEpisode.value.id}`, {
        onSuccess: (page) => {
            toast.success('Episode updated successfully!');
            showEditDialog.value = false;
            editingEpisode.value = null;
            resetForm();
            // Use Inertia visit to refresh with fresh data
            router.visit('/dashboard/episodes', {
                preserveState: false,
                preserveScroll: true
            });
        },
        onError: (errors) => {
            console.error('Error updating episode:', errors);
            toast.error('Failed to update episode. Please try again.');
        }
    });
};

// Delete episode
const deleteEpisode = async (id: number) => {
    if (confirm('Are you sure you want to delete this episode?')) {
        try {
            const response = await fetch(`/dashboard/episodes/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                toast.success('Episode deleted successfully!');
                // Use Inertia visit to refresh with fresh data
                router.visit('/dashboard/episodes', {
                    preserveState: false,
                    preserveScroll: true
                });
            } else {
                toast.error('Failed to delete episode.');
            }
        } catch (error) {
            toast.error('An error occurred while deleting the episode.');
            console.error('Error deleting episode:', error);
        }
    }
};

onMounted(() => {
    loadEpisodes();
    
    // Check for flash messages from Laravel
    const flashProps = page.props as any;
    if (flashProps.flash?.success) {
        toast.success(flashProps.flash.success);
    }
    
    if (flashProps.flash?.error) {
        toast.error(flashProps.flash.error);
    }
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">Episode Management</h2>
                <p class="text-muted-foreground">
                    Manage your podcast episodes
                </p>
            </div>
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
                        <div class="grid gap-2">
                            <Label htmlFor="title">Title</Label>
                            <Input
                                id="title"
                                v-model="createForm.title"
                                placeholder="Episode title"
                                :class="{ 'border-red-500': createForm.errors.title }"
                            />
                            <p v-if="createForm.errors.title" class="text-sm text-red-500">
                                {{ createForm.errors.title }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                v-model="createForm.description"
                                placeholder="Episode description"
                                :class="{ 'border-red-500': createForm.errors.description }"
                            />
                            <p v-if="createForm.errors.description" class="text-sm text-red-500">
                                {{ createForm.errors.description }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label htmlFor="audio_file">Audio File</Label>
                            <Input
                                id="audio_file"
                                type="file"
                                accept=".mp3,.m4a"
                                @change="handleFileSelect"
                                :class="{ 'border-red-500': createForm.errors.audio_file }"
                            />
                            <p v-if="createForm.errors.audio_file" class="text-sm text-red-500">
                                {{ createForm.errors.audio_file }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label htmlFor="published_date">Published Date</Label>
                            <Input
                                id="published_date"
                                type="date"
                                v-model="createForm.published_date"
                                :class="{ 'border-red-500': createForm.errors.published_date }"
                            />
                            <p v-if="createForm.errors.published_date" class="text-sm text-red-500">
                                {{ createForm.errors.published_date }}
                            </p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showAddDialog = false">
                            Cancel
                        </Button>
                        <Button 
                            type="button" 
                            @click="createEpisode"
                            :disabled="createForm.processing"
                        >
                            <Upload v-if="!createForm.processing" class="mr-2 h-4 w-4" />
                            {{ createForm.processing ? 'Creating...' : 'Create Episode' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Success Message -->
        <div v-if="successMessage" class="rounded-md bg-green-50 p-4">
            <div class="text-sm text-green-800">
                {{ successMessage }}
            </div>
        </div>

        <!-- Error Messages -->
        <div v-if="Object.keys(errors).length > 0" class="rounded-md bg-red-50 p-4">
            <div class="text-sm text-red-800">
                <ul class="list-disc list-inside space-y-1">
                    <li v-for="(error, key) in errors" :key="key">{{ error }}</li>
                </ul>
            </div>
        </div>

        <!-- Episodes Table -->
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
                    <TableRow v-for="episode in episodes" :key="episode.id">
                        <TableCell class="font-medium">
                            <div>
                                <div class="font-semibold">{{ episode.title }}</div>
                                <div class="text-sm text-muted-foreground">
                                    {{ episode.description }}
                                </div>
                            </div>
                        </TableCell>
                        <TableCell>{{ formatDuration(episode.duration) }}</TableCell>
                        <TableCell>{{ episode.file_size }}</TableCell>
                        <TableCell>{{ episode.format.toUpperCase() }}</TableCell>
                        <TableCell>{{ new Date(episode.published_date).toLocaleDateString() }}</TableCell>
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
                    <TableRow v-if="episodes.length === 0">
                        <TableCell colspan="6" class="text-center py-8 text-muted-foreground">
                            No episodes found. Add your first episode to get started.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Edit Episode Dialog -->
        <Dialog v-model:open="showEditDialog">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Edit Episode</DialogTitle>
                    <DialogDescription>
                        Update episode information.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label htmlFor="edit_title">Title</Label>
                        <Input
                            id="edit_title"
                            v-model="updateForm.title"
                            placeholder="Episode title"
                            :class="{ 'border-red-500': updateForm.errors.title }"
                        />
                        <p v-if="updateForm.errors.title" class="text-sm text-red-500">
                            {{ updateForm.errors.title }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label htmlFor="edit_description">Description</Label>
                        <Textarea
                            id="edit_description"
                            v-model="updateForm.description"
                            placeholder="Episode description"
                            :class="{ 'border-red-500': updateForm.errors.description }"
                        />
                        <p v-if="updateForm.errors.description" class="text-sm text-red-500">
                            {{ updateForm.errors.description }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label htmlFor="edit_audio_file">Audio File (optional)</Label>
                        <Input
                            id="edit_audio_file"
                            type="file"
                            accept=".mp3,.m4a"
                            @change="handleEditFileSelect"
                            :class="{ 'border-red-500': updateForm.errors.audio_file }"
                        />
                        <p v-if="updateForm.errors.audio_file" class="text-sm text-red-500">
                            {{ updateForm.errors.audio_file }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            Leave empty to keep the current audio file.
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label htmlFor="edit_published_date">Published Date</Label>
                        <Input
                            id="edit_published_date"
                            type="date"
                            v-model="updateForm.published_date"
                            :class="{ 'border-red-500': updateForm.errors.published_date }"
                        />
                        <p v-if="updateForm.errors.published_date" class="text-sm text-red-500">
                            {{ updateForm.errors.published_date }}
                        </p>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="showEditDialog = false">
                        Cancel
                    </Button>
                    <Button 
                        type="button" 
                        @click="updateEpisode"
                        :disabled="updateForm.processing"
                    >
                        {{ updateForm.processing ? 'Updating...' : 'Update Episode' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>