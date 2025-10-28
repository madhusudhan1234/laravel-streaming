<template>
    <div
        class="home-page min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50"
    >
        <!-- Header -->
        <header class="border-b border-gray-200 bg-white shadow-sm">
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg overflow-hidden"
                        >
                            <img 
                                src="/images/image.png" 
                                alt="Tech Weekly Podcast Logo" 
                                class="h-10 w-10 object-cover rounded-lg"
                            />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">
                                Tech Weekly Podcast
                            </h1>
                            <p class="text-sm text-gray-600">
                                by Madhu Sudhan Subedi
                            </p>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div
                        class="hidden items-center space-x-6 text-sm text-gray-600 sm:flex"
                    >
                        <div class="flex items-center space-x-1">
                            <svg
                                class="h-4 w-4"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"
                                />
                            </svg>
                            <span>{{ episodes.length }} Episodes</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <svg
                                class="h-4 w-4"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
                                />
                            </svg>
                            <span>Tech & Programming</span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Episode List - Takes up 2/3 on large screens -->
                <div class="lg:col-span-2">
                    <EpisodeList
                        :episodes="episodes"
                        :current-episode="currentEpisode"
                        :is-playing="audioState.isPlaying"
                        :progress="progress"
                        :current-time="audioState.currentTime"
                        :duration="audioState.duration"
                        @episode-select="handleEpisodeSelect"
                        @episode-play="handleEpisodePlay"
                        @episode-seek="handleEpisodeSeek"
                    />
                </div>

                <!-- Audio Player Sidebar - Takes up 1/3 on large screens -->
                <div class="lg:col-span-1">
                    <div class="sticky top-8">
                        <!-- Current Episode Info -->
                        <div
                            v-if="currentEpisode"
                            class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
                        >
                            <h2
                                class="mb-2 text-lg font-semibold text-gray-900"
                            >
                                Now Playing
                            </h2>
                            <div class="mb-4 flex items-center space-x-3">
                                <div
                                    class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-r from-blue-600 to-purple-600"
                                >
                                    <svg
                                        class="h-6 w-6 text-white"
                                        fill="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"
                                        />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3
                                        class="truncate font-medium text-gray-900"
                                    >
                                        {{ currentEpisode.title }}
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        {{
                                            formatDate(
                                                currentEpisode.published_date,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <!-- Episode Meta -->
                            <div
                                class="flex items-center justify-between border-t border-gray-100 pt-3 text-xs text-gray-500"
                            >
                                <span>{{ formatDuration(currentEpisode.duration) }}</span>
                                <span>{{ currentEpisode.file_size }}</span>
                                <span>{{
                                    currentEpisode.format?.toUpperCase() || 'MP3'
                                }}</span>
                            </div>

                            <!-- Get Embed Code Button -->
                            <div class="mt-4 border-t border-gray-100 pt-4">
                                <button
                                    @click="showEmbedCode"
                                    class="w-full rounded-md border border-orange-200 bg-gradient-to-r from-orange-100 to-orange-50 px-4 py-3 text-sm font-medium text-orange-700 transition-colors hover:from-orange-200 hover:to-orange-100 focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 focus:outline-none"
                                >
                                    Get Embed Code
                                </button>
                            </div>
                        </div>

                        <!-- Welcome Message when no episode selected -->
                        <div
                            v-else
                            class="rounded-lg border border-gray-200 bg-white p-6 text-center shadow-sm"
                        >
                            <div
                                class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-r from-blue-600 to-purple-600"
                            >
                                <svg
                                    class="h-8 w-8 text-white"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"
                                    />
                                </svg>
                            </div>
                            <h3
                                class="mb-2 text-lg font-semibold text-gray-900"
                            >
                                Welcome to Tech Weekly
                            </h3>
                            <p class="mb-4 text-gray-600">
                                Select an episode from the list to start
                                listening to insightful discussions about
                                technology and programming.
                            </p>
                            <button
                                v-if="episodes.length > 0"
                                @click="handleEpisodePlay(episodes[0])"
                                class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                            >
                                <svg
                                    class="mr-2 h-4 w-4"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                                Play Latest Episode
                            </button>
                        </div>

                        <!-- Quick Stats -->
                        <div
                            class="mt-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
                        >
                            <h3
                                class="mb-4 text-lg font-semibold text-gray-900"
                            >
                                Podcast Stats
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600"
                                        >Total Episodes</span
                                    >
                                    <span class="font-semibold text-gray-900">{{
                                        episodes.length
                                    }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600"
                                        >Total Duration</span
                                    >
                                    <span class="font-semibold text-gray-900">{{
                                        totalDuration
                                    }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600"
                                        >Latest Episode</span
                                    >
                                    <span class="font-semibold text-gray-900">{{
                                        latestEpisodeDate
                                    }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="mt-16 border-t border-gray-200 bg-gray-50">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div class="text-center">
                    <p class="mb-2 text-gray-600">
                        © 2024 Madhu Sudhan Subedi Tech Weekly. All rights
                        reserved.
                    </p>
                    <p class="text-sm text-gray-500">
                        Sharing insights about technology, programming, and
                        software development.
                    </p>
                </div>
            </div>
        </footer>

        <!-- Embed Code Modal -->
        <div
            v-if="showEmbedModal"
            class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black"
            @click="closeEmbedModal"
        >
            <div
                class="mx-4 w-full max-w-md rounded-lg bg-white p-6"
                @click.stop
            >
                <h3 class="mb-4 text-lg font-semibold">Embed Code</h3>
                <p class="mb-3 text-sm text-gray-600">
                    Copy this code to embed the audio player on your website:
                </p>
                <textarea
                    ref="embedCodeTextarea"
                    :value="embedCode"
                    readonly
                    class="h-24 w-full resize-none rounded-md border border-gray-300 bg-gray-50 p-3 font-mono text-sm"
                ></textarea>
                <div class="mt-4 flex justify-end space-x-3">
                    <button
                        @click="closeEmbedModal"
                        class="px-4 py-2 text-gray-600 transition-colors hover:text-gray-800"
                    >
                        Cancel
                    </button>
                    <button
                        @click="copyEmbedCode"
                        class="rounded-md bg-gradient-to-r from-orange-500 to-orange-600 px-4 py-2 text-white shadow-md transition-colors hover:from-orange-600 hover:to-orange-700 hover:shadow-lg"
                    >
                        Copy Code
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
// import AudioPlayer from '@/components/AudioPlayer.vue';
import EpisodeList from '@/components/EpisodeList.vue';
import { useAudioPlayer, type Episode } from '@/composables/useAudioPlayer';
import { computed, onMounted, ref } from 'vue';

interface Props {
    episodes: Episode[];
}

const props = defineProps<Props>();

// Audio player composable
const { audioState, progress, initAudio, play, pause, seekToPercentage } = useAudioPlayer();

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

// Computed properties
const totalDuration = computed(() => {
    const totalMinutes = props.episodes.reduce((total, episode) => {
        // Duration is now stored as decimal minutes
        if (!episode.duration) return total;
        
        const duration = typeof episode.duration === 'string' ? parseFloat(episode.duration) : episode.duration;
        return total + (duration || 0);
    }, 0);

    const hours = Math.floor(totalMinutes / 60);
    const minutes = Math.floor(totalMinutes % 60);

    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }
    return `${minutes}m`;
});

const latestEpisodeDate = computed(() => {
    if (props.episodes.length === 0) return 'N/A';

    const latest = props.episodes.reduce((latest, episode) => {
        return new Date(episode.published_date) >
            new Date(latest.published_date)
            ? episode
            : latest;
    });

    return formatDate(latest.published_date);
});

// Current episode state
const currentEpisode = ref<Episode | null>(null);

// Embed modal state
const showEmbedModal = ref(false);
const embedCode = ref('');
const embedCodeTextarea = ref<HTMLTextAreaElement>();

// Methods
const handleEpisodeSelect = (episode: Episode) => {
    if (currentEpisode.value?.id !== episode.id) {
        currentEpisode.value = episode;
        initAudio(episode);
    }
};

const showEmbedCode = async () => {
    if (!currentEpisode.value) return;

    try {
        const response = await fetch(`/api/embed/${currentEpisode.value.id}/code`);
        const data = await response.json();
        embedCode.value = data.embedCode;
        showEmbedModal.value = true;
    } catch (error) {
        console.error('Failed to generate embed code:', error);
    }
};

const closeEmbedModal = () => {
    showEmbedModal.value = false;
};

const copyEmbedCode = async () => {
    if (embedCodeTextarea.value) {
        try {
            await navigator.clipboard.writeText(embedCode.value);
            closeEmbedModal();
        } catch {
            // Fallback for older browsers
            embedCodeTextarea.value.select();
            document.execCommand('copy');
            closeEmbedModal();
        }
    }
};

const handleEpisodePlay = (episode: Episode) => {
    if (currentEpisode.value?.id === episode.id) {
        if (audioState.isPlaying) {
            pause();
        } else {
            play();
        }
    } else {
        currentEpisode.value = episode;
        initAudio(episode);
        // Small delay to ensure audio is initialized before playing
        setTimeout(() => {
            play();
        }, 100);
    }
};

const handleEpisodeSeek = (episode: Episode, percentage: number) => {
    if (currentEpisode.value?.id === episode.id) {
        seekToPercentage(percentage);
    }
};



const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Initialize with latest episode on mount (Episode 31)
onMounted(() => {
    if (props.episodes.length > 0) {
        // Find the latest episode (highest ID or most recent date)
        const latestEpisode = props.episodes.reduce((latest, episode) => {
            return episode.id > latest.id ? episode : latest;
        });
        // Don't auto-play, just initialize the latest episode
        currentEpisode.value = latestEpisode;
        initAudio(latestEpisode);
    }
});
</script>

<style scoped>
/* Line clamp utility */
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Responsive grid adjustments */
@media (max-width: 1024px) {
    .sticky {
        position: static;
    }
}

/* High contrast support */
@media (prefers-contrast: high) {
    .bg-gradient-to-br {
        background: white;
    }

    .bg-gradient-to-r {
        background: #1e40af;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    * {
        transition: none !important;
        animation: none !important;
    }
}

/* Focus management for accessibility */
.focus-within\:ring-2:focus-within {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}
</style>
