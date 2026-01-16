<template>
    <div
        class="audio-player mx-auto max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-md"
    >
        <!-- Episode Header -->
        <div class="episode-header mb-4">
            <h3 class="mb-1 text-lg font-semibold text-gray-900">
                {{ episode?.title || 'No episode selected' }}
            </h3>
            <p v-if="episode" class="text-sm text-gray-600">
                {{ formatDate(episode.published_date) }}
            </p>
        </div>

        <!-- Audio Controls -->
        <div class="audio-controls mb-4 flex items-center space-x-4">
            <button
                @click="togglePlay"
                :disabled="!episode || audioState.isLoading"
                class="play-pause-btn flex h-12 w-12 transform items-center justify-center rounded-full bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow-lg transition-all duration-300 hover:scale-110 hover:from-orange-600 hover:to-orange-700 hover:shadow-xl focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 focus:outline-none disabled:from-gray-400 disabled:to-gray-500"
                :aria-label="
                    audioState.isPlaying ? 'Pause episode' : 'Play episode'
                "
                :aria-pressed="audioState.isPlaying"
            >
                <svg
                    v-if="audioState.isLoading"
                    class="h-6 w-6 animate-spin"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
                <svg
                    v-else-if="!audioState.isPlaying"
                    class="ml-1 h-6 w-6"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path d="M8 5v14l11-7z" />
                </svg>
                <svg
                    v-else
                    class="h-6 w-6"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                </svg>
            </button>

            <!-- 10-second backward button -->
            <button
                @click="skipBackward"
                :disabled="!episode || audioState.duration === 0"
                class="skip-btn flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition-all duration-200 hover:scale-105 hover:bg-gray-200 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:outline-none disabled:bg-gray-50 disabled:text-gray-300"
                aria-label="Skip backward 10 seconds"
                title="Skip backward 10 seconds"
            >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                    <!-- Backward arrow with 10 indicator -->
                    <path d="M11 18V6l-8.5 6 8.5 6zm.5-6l8.5 6V6l-8.5 6z" />
                    <!-- Small "10" integrated into the design -->
                    <circle cx="16" cy="4" r="3" fill="currentColor" />
                    <path
                        d="M14.5 2.5h1v3h-1v-3zm2 0h1v1.5h-1v-1.5zm0 1.5h1v1.5h-1v-1.5z"
                        fill="white"
                    />
                </svg>
            </button>

            <!-- 10-second forward button -->
            <button
                @click="skipForward"
                :disabled="!episode || audioState.duration === 0"
                class="skip-btn flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition-all duration-200 hover:scale-105 hover:bg-gray-200 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:outline-none disabled:bg-gray-50 disabled:text-gray-300"
                aria-label="Skip forward 10 seconds"
                title="Skip forward 10 seconds"
            >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                    <!-- Forward arrow with 10 indicator -->
                    <path d="M4 18l8.5-6L4 6v12zm9-12v12l8.5-6L13 6z" />
                    <!-- Small "10" integrated into the design -->
                    <circle cx="8" cy="4" r="3" fill="currentColor" />
                    <path
                        d="M6.5 2.5h1v3h-1v-3zm2 0h1v1.5h-1v-1.5zm0 1.5h1v1.5h-1v-1.5z"
                        fill="white"
                    />
                </svg>
            </button>

            <button
                @click="previousEpisode"
                :disabled="!hasPrevious"
                class="previous-btn flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition-colors hover:bg-gray-200 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:outline-none disabled:bg-gray-50 disabled:text-gray-300"
                aria-label="Previous episode"
            >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M6 6h2v12H6zm3.5 6l8.5 6V6z" />
                </svg>
            </button>

            <button
                @click="nextEpisode"
                :disabled="!hasNext"
                class="next-btn flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition-colors hover:bg-gray-200 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:outline-none disabled:bg-gray-50 disabled:text-gray-300"
                aria-label="Next episode"
            >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z" />
                </svg>
            </button>
        </div>

        <!-- SoundCloud-like Waveform -->
        <div class="waveform-section mb-4">
            <Waveform
                :progress="progress"
                :current-time="audioState.currentTime"
                :duration="audioState.duration"
                :is-playing="audioState.isPlaying"
                :is-loading="audioState.isLoading"
                :total-bars="180"
                :height="80"
                :min-bar-height="8"
                :seed="episode?.id"
                :show-play-overlay="true"
                @seek="handleWaveformSeek"
            />
        </div>

        <!-- Volume Control -->
        <div class="volume-section mb-6 flex items-center space-x-3">
            <button
                @click="mute"
                class="volume-icon text-gray-600 transition-colors hover:text-gray-800"
            >
                <svg
                    v-if="audioState.volume === 0"
                    class="h-5 w-5"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 = 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"
                    />
                </svg>
                <svg
                    v-else-if="audioState.volume < 50"
                    class="h-5 w-5"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M18.5 12c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM5 9v6h4l5 5V4L9 9H5z"
                    />
                </svg>
                <svg
                    v-else
                    class="h-5 w-5"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"
                    />
                </svg>
            </button>
            <div class="volume-slider max-w-24 flex-1">
                <input
                    type="range"
                    min="0"
                    max="100"
                    :value="audioState.volume"
                    @input="handleVolumeChange"
                    class="volume-range h-2 w-full cursor-pointer appearance-none rounded-lg bg-gray-200"
                    aria-label="Volume control"
                />
            </div>
            <span class="w-8 text-right text-xs text-gray-500">{{
                Math.round(audioState.volume)
            }}</span>
        </div>

        <!-- Embed Code Button -->
        <div class="embed-section">
            <button
                @click="showEmbedCode"
                :disabled="!episode"
                class="embed-btn w-full rounded-md border border-orange-200 bg-gradient-to-r from-orange-100 to-orange-50 px-4 py-3 text-sm font-medium text-orange-700 transition-colors hover:from-orange-200 hover:to-orange-100 focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 focus:outline-none disabled:bg-gray-50 disabled:text-gray-400"
            >
                Get Embed Code
            </button>
        </div>

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

        <!-- Error Message -->
        <div
            v-if="audioState.error"
            class="error-message mt-4 rounded-md border border-red-200 bg-red-50 p-3"
        >
            <p class="text-sm text-red-600">{{ audioState.error }}</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAudioPlayer, type Episode } from '@/composables/useAudioPlayer';
import Waveform from '@/components/Waveform.vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

// #######################################
// Types
// #######################################

interface Props {
    episode?: Episode | null;
    episodes?: Episode[];
}

interface Emits {
    (e: 'episodeChange', episode: Episode): void;
}

// #######################################
// Props and Emits
// #######################################

const props = withDefaults(defineProps<Props>(), {
    episode: null,
    episodes: () => [],
});

const emit = defineEmits<Emits>();

// #######################################
// Audio Streaming
// #######################################

const {
    audioState,
    progress,
    initAudio,
    togglePlay,
    seekToPercentage,
    setVolume,
    mute,
} = useAudioPlayer();

// #######################################
// Waveform Event Handlers
// #######################################

const handleWaveformSeek = (percentage: number) => {
    if (audioState.duration === 0) return;
    seekToPercentage(Math.max(0, Math.min(100, percentage)));
};

// #######################################
// Episode Navigation
// #######################################

const currentEpisodeIndex = computed(() => {
    if (!props.episode || !props.episodes.length) return -1;
    return props.episodes.findIndex((ep) => ep.id === props.episode?.id);
});

const canNavigate = (offset: number): boolean => {
    if (currentEpisodeIndex.value === -1) return false;
    const targetIndex = currentEpisodeIndex.value + offset;
    return targetIndex >= 0 && targetIndex < props.episodes.length;
};

const hasPrevious = computed(() => canNavigate(-1));
const hasNext = computed(() => canNavigate(1));

const navigateEpisode = (offset: number) => {
    const targetIndex = currentEpisodeIndex.value + offset;
    const canNav = targetIndex >= 0 && targetIndex < props.episodes.length;
    if (canNav && currentEpisodeIndex.value !== -1) {
        emit('episodeChange', props.episodes[targetIndex]);
    }
};

const previousEpisode = () => navigateEpisode(-1);
const nextEpisode = () => navigateEpisode(1);

// #######################################
// Playback Controls
// #######################################

// ##############################
// Skip Controls
// ##############################

const skipBySeconds = (delta: number) => {
    if (audioState.duration === 0) return;
    const currentTime = (progress.value / 100) * audioState.duration;
    const newTime = Math.max(0, Math.min(audioState.duration, currentTime + delta));
    const newPercentage = (newTime / audioState.duration) * 100;
    seekToPercentage(newPercentage);
};

const skipBackward = () => skipBySeconds(-10);
const skipForward = () => skipBySeconds(10);

// ##############################
// Volume Control
// ##############################

const handleVolumeChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    setVolume(parseInt(target.value));
};

// #######################################
// Embed Modal
// #######################################

const showEmbedModal = ref(false);
const embedCode = ref('');
const embedCodeTextarea = ref<HTMLTextAreaElement>();

const showEmbedCode = async () => {
    if (!props.episode) return;

    try {
        const response = await fetch(`/api/embed/${props.episode.id}/code`);
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

// #######################################
// Utilities
// #######################################

const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

// #######################################
// Keyboard Shortcuts
// #######################################

const handleKeydown = (event: KeyboardEvent) => {
    // ####################
    // Skip if typing in an input
    // ####################

    if (
        event.target instanceof HTMLInputElement ||
        event.target instanceof HTMLTextAreaElement
    ) {
        return;
    }

    // ####################
    // Handle shortcut keys
    // ####################

    switch (event.key) {
        case 'ArrowLeft':
            event.preventDefault();
            skipBackward();
            break;
        case 'ArrowRight':
            event.preventDefault();
            skipForward();
            break;
        case ' ':
            event.preventDefault();
            togglePlay();
            break;
    }
};

// #######################################
// Lifecycle
// #######################################

// ##############################
// Episode Watcher
// ##############################

watch(
    () => props.episode,
    (newEpisode) => {
        if (newEpisode) {
            // Use streaming URL fetching for optimal playback
            initAudio(newEpisode, { fetchStreamUrl: true });
        }
    },
    { immediate: true },
);

// ##############################
// Mount and Unmount
// ##############################

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<style scoped>
/* #######################################
   Volume Slider
   ####################################### */

.volume-range::-webkit-slider-thumb {
    appearance: none;
    width: 16px;
    height: 16px;
    background: linear-gradient(135deg, #f97316, #ea580c);
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.volume-range::-moz-range-thumb {
    width: 16px;
    height: 16px;
    background: linear-gradient(135deg, #f97316, #ea580c);
    border-radius: 50%;
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.volume-range::-webkit-slider-track {
    background: #e5e7eb;
    border-radius: 4px;
    height: 8px;
}

.volume-range::-moz-range-track {
    background: #e5e7eb;
    border-radius: 4px;
    height: 8px;
    border: none;
}

/* #######################################
   Focus States for Accessibility
   ####################################### */

.play-pause-btn:focus,
.previous-btn:focus,
.next-btn:focus,
.skip-btn:focus,
.embed-btn:focus {
    outline: none;
    ring: 2px;
    ring-color: #f97316;
    ring-offset: 2px;
}

/* #######################################
   Skip Button Effects
   ####################################### */

.skip-btn:hover:not(:disabled) {
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.skip-btn:active:not(:disabled) {
    transform: scale(0.95);
}

.skip-btn.skip-active {
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: white;
    animation: skip-feedback 0.3s ease-out;
}

@keyframes skip-feedback {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
    }
}

/* #######################################
   Reduced Motion Support
   ####################################### */

@media (prefers-reduced-motion: reduce) {
    .audio-controls button {
        animation: none;
        transition: none;
    }
}
</style>
