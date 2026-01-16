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
            <div
                ref="waveformContainer"
                @click="handleWaveformClick"
                @mousemove="handleWaveformHover"
                @mouseleave="handleWaveformLeave"
                class="waveform-container group relative cursor-pointer overflow-hidden rounded-lg bg-gradient-to-r from-gray-100 to-gray-50 p-4"
                :class="{ playing: audioState.isPlaying }"
                role="slider"
                :aria-valuenow="audioState.currentTime"
                :aria-valuemin="0"
                :aria-valuemax="audioState.duration"
                aria-label="Audio waveform - click to seek"
            >
                <!-- Background Waveform -->
                <div class="waveform-bars flex h-20 items-end justify-between">
                    <div
                        v-for="(bar, index) in waveformBars"
                        :key="index"
                        class="waveform-bar relative overflow-hidden rounded-t-sm bg-gray-400 transition-all duration-300 ease-out hover:bg-gray-500"
                        :style="{
                            height: `${bar.height}%`,
                            minHeight: '8px',
                            width: `${barWidth}px`,
                        }"
                    >
                        <!-- Progress Fill -->
                        <div
                            class="progress-fill absolute bottom-0 left-0 w-full transition-all duration-300 ease-out"
                            :class="[
                                index <= progressBarIndex
                                    ? 'bg-gradient-to-t from-orange-500 to-orange-400 shadow-sm'
                                    : 'bg-transparent',
                                audioState.isPlaying &&
                                index === progressBarIndex
                                    ? 'animate-pulse'
                                    : '',
                            ]"
                            :style="{
                                height:
                                    index < progressBarIndex
                                        ? '100%'
                                        : index === progressBarIndex
                                          ? `${progressWithinBar}%`
                                          : '0%',
                            }"
                        ></div>

                        <!-- Hover Effect -->
                        <div
                            v-if="hoverIndex >= 0 && index <= hoverIndex"
                            class="hover-fill absolute bottom-0 left-0 w-full bg-gradient-to-t from-orange-300 to-orange-200 opacity-60 transition-opacity duration-200"
                            :style="{ height: '100%' }"
                        ></div>
                    </div>
                </div>

                <!-- Play Button Overlay -->
                <div
                    v-if="!audioState.isPlaying && !audioState.isLoading"
                    class="play-overlay bg-opacity-20 absolute inset-0 flex items-center justify-center bg-black opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                >
                    <div
                        class="play-button transform rounded-full bg-orange-500 p-3 text-white shadow-lg transition-all duration-200 hover:scale-110 hover:bg-orange-600"
                    >
                        <svg
                            class="ml-1 h-6 w-6"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path d="M8 5v14l11-7z" />
                        </svg>
                    </div>
                </div>

                <!-- Loading Overlay -->
                <div
                    v-if="audioState.isLoading"
                    class="loading-overlay bg-opacity-80 absolute inset-0 flex items-center justify-center rounded-lg bg-white"
                >
                    <div class="flex space-x-1">
                        <div
                            v-for="i in 5"
                            :key="i"
                            class="loading-bar animate-bounce rounded-sm bg-orange-500"
                            :style="{
                                width: '4px',
                                height: `${20 + (i % 3) * 10}px`,
                                animationDelay: `${i * 0.1}s`,
                            }"
                        ></div>
                    </div>
                </div>

                <!-- Time Display -->
                <div
                    class="time-display absolute right-4 bottom-1 left-4 flex justify-between text-xs text-gray-600"
                >
                    <span
                        class="bg-opacity-90 rounded bg-white px-2 py-1 shadow-sm"
                        >{{ formattedCurrentTime }}</span
                    >
                    <span
                        class="bg-opacity-90 rounded bg-white px-2 py-1 shadow-sm"
                        >{{ formattedDuration }}</span
                    >
                </div>
            </div>
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
import {
    useAudioStreaming,
    type Episode,
} from '@/composables/useAudioStreaming';
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
    formattedCurrentTime,
    formattedDuration,
    initAudio,
    togglePlay,
    seekToPercentage,
    setVolume,
    mute,
} = useAudioStreaming();

// #######################################
// Waveform Display
// #######################################

// ##############################
// Waveform State
// ##############################

const waveformContainer = ref<HTMLElement>();
const hoverIndex = ref(-1);
const totalBars = 180;

// ##############################
// Waveform Computed Properties
// ##############################

const barWidth = computed(() => {
    if (!waveformContainer.value) return 2;
    const containerWidth = waveformContainer.value.offsetWidth - 32; // Account for padding
    return Math.max(
        2,
        Math.floor(containerWidth / totalBars), // Remove gap calculation for full width
    );
});

const waveformBars = computed(() => {
    const bars = [];
    for (let i = 0; i < totalBars; i++) {
        // ####################
        // Create multi-layer wave algorithm
        // ####################

        const mainWave = Math.sin(i * 0.08) * 0.4;
        const secondaryWave = Math.sin(i * 0.23) * 0.25;
        const detailWave = Math.sin(i * 0.47) * 0.15;

        // ####################
        // Combine waves and scale
        // ####################

        const combined = mainWave + secondaryWave + detailWave;
        const randomness = (Math.random() - 0.5) * 0.3;

        // Scale to percentage (8% to 85% range)
        let height = ((combined + randomness + 1) / 2) * 77 + 8;

        // Make every 3rd bar smaller for detail
        if (i % 3 === 0) {
            height *= 0.4;
        }

        // Ensure bounds
        height = Math.max(8, Math.min(85, height));

        bars.push({ height });
    }
    return bars;
});

// ##############################
// Progress Calculations
// ##############################

const progressBarIndex = computed(() => {
    return Math.floor((progress.value / 100) * totalBars);
});

const progressWithinBar = computed(() => {
    const exactProgress = (progress.value / 100) * totalBars;
    return (exactProgress - Math.floor(exactProgress)) * 100;
});

// ##############################
// Waveform Event Handlers
// ##############################

const getWaveformPercentage = (event: MouseEvent): number | null => {
    if (!waveformContainer.value) return null;
    const rect = waveformContainer.value.getBoundingClientRect();
    const x = event.clientX - rect.left;
    return (x / rect.width) * 100;
};

const handleWaveformClick = (event: MouseEvent) => {
    if (audioState.duration === 0) return;
    const percentage = getWaveformPercentage(event);
    if (percentage !== null) {
        seekToPercentage(Math.max(0, Math.min(100, percentage)));
    }
};

const handleWaveformHover = (event: MouseEvent) => {
    const percentage = getWaveformPercentage(event);
    if (percentage !== null) {
        hoverIndex.value = Math.floor((percentage / 100) * totalBars);
    }
};

const handleWaveformLeave = () => {
    hoverIndex.value = -1;
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
            initAudio(newEpisode);
        }
    },
    { immediate: true },
);

// ##############################
// Resize Handler
// ##############################

const handleResize = () => {
    // Force reactivity update for barWidth
    if (waveformContainer.value) {
        waveformContainer.value.style.width =
            waveformContainer.value.style.width;
    }
};

// ##############################
// Mount and Unmount
// ##############################

onMounted(() => {
    window.addEventListener('resize', handleResize);
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
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
   Waveform Animations
   ####################################### */

.waveform-container.playing .waveform-bar .progress-fill {
    animation: waveform-pulse 0.8s ease-in-out infinite alternate;
}

@keyframes waveform-pulse {
    0% {
        opacity: 0.8;
        transform: scaleY(0.95);
    }
    100% {
        opacity: 1;
        transform: scaleY(1.05);
    }
}

/* #######################################
   Loading Animation
   ####################################### */

.loading-bar {
    animation: loading-bounce 1.5s ease-in-out infinite;
}

@keyframes loading-bounce {
    0%,
    100% {
        transform: scaleY(0.5);
        opacity: 0.7;
    }
    50% {
        transform: scaleY(1.2);
        opacity: 1;
    }
}

/* #######################################
   Hover Effects
   ####################################### */

.waveform-container:hover .waveform-bar {
    transform: scaleY(1.02);
}

.waveform-container:hover .play-overlay {
    backdrop-filter: blur(2px);
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
    .waveform-bar,
    .progress-fill,
    .play-button,
    .loading-bar,
    .audio-controls button {
        animation: none;
        transition: none;
    }
}
</style>
