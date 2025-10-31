<template>
    <div
        class="embed-player mx-auto max-w-md rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
    >
        <!-- Episode Info -->
        <div class="episode-info mb-4">
            <h3 class="mb-1 truncate text-lg font-semibold text-gray-900">
                {{ episode.title }}
            </h3>
            <p class="flex items-center gap-2 text-sm text-gray-600">
                <span>{{ formatDuration(episode.duration) }}</span>
                <span>•</span>
                <span>{{ formatDate(episode.published_date) }}</span>
            </p>
        </div>

        <!-- Audio Controls -->
        <div class="audio-controls">
            <!-- Progress Bar -->
            <div class="progress-container mb-3">
                <div
                    class="mb-1 flex items-center justify-between text-xs text-gray-500"
                >
                    <span>{{ formatTime(currentTime) }}</span>
                    <span>{{ formatTime(duration) }}</span>
                </div>
                <div
                    class="progress-bar relative h-2 cursor-pointer rounded-full bg-gray-200"
                    @click="handleProgressClick"
                    ref="progressBar"
                >
                    <!-- Buffered Progress -->
                    <div
                        class="buffered-progress absolute h-full rounded-full bg-gray-300"
                        :style="{ width: `${bufferedProgress}%` }"
                    ></div>
                    <!-- Current Progress -->
                    <div
                        class="current-progress relative h-full rounded-full bg-blue-600"
                        :style="{ width: `${progress}%` }"
                    >
                        <div
                            class="progress-thumb absolute top-1/2 right-0 h-3 w-3 translate-x-1/2 -translate-y-1/2 transform rounded-full bg-blue-600 shadow-sm"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Control Buttons -->
            <div class="controls-row flex items-center justify-between">
                <!-- Play/Pause Button -->
                <button
                    @click="togglePlayPause"
                    :disabled="loading"
                    class="play-pause-btn flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 text-white transition-colors hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:bg-gray-400"
                    :aria-label="isPlaying ? 'Pause' : 'Play'"
                >
                    <div
                        v-if="loading"
                        class="h-5 w-5 animate-spin rounded-full border-2 border-white border-t-transparent"
                    ></div>
                    <svg
                        v-else-if="isPlaying"
                        class="h-6 w-6"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                    </svg>
                    <svg
                        v-else
                        class="ml-1 h-6 w-6"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path d="M8 5v14l11-7z" />
                    </svg>
                </button>

                <!-- Volume Control -->
                <div class="volume-control flex items-center space-x-2">
                    <button
                        @click="toggleMute"
                        class="volume-btn rounded-md p-2 text-gray-600 transition-colors hover:text-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                        :aria-label="isMuted ? 'Unmute' : 'Mute'"
                    >
                        <svg
                            v-if="isMuted || volume === 0"
                            class="h-5 w-5"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"
                            />
                        </svg>
                        <svg
                            v-else-if="volume < 0.5"
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
                    <input
                        type="range"
                        min="0"
                        max="1"
                        step="0.1"
                        :value="volume"
                        @input="handleVolumeChange"
                        class="volume-slider h-1 w-16 cursor-pointer appearance-none rounded-lg bg-gray-200 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    />
                </div>

                <!-- Link to Full Site -->
                <a
                    :href="fullSiteUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="external-link rounded-md p-2 text-gray-600 transition-colors hover:text-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                    title="Open in full player"
                    aria-label="Open in full player"
                >
                    <svg
                        class="h-5 w-5"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"
                        />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Error State -->
        <div
            v-if="error"
            class="error-message mt-3 rounded-md border border-red-200 bg-red-50 p-3"
        >
            <div class="flex items-center">
                <svg
                    class="mr-2 h-5 w-5 text-red-500"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"
                    />
                </svg>
                <span class="text-sm text-red-700">{{ error }}</span>
            </div>
        </div>

        <!-- Powered By -->
        <div class="powered-by mt-3 border-t border-gray-100 pt-3 text-center">
            <a
                :href="fullSiteUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="text-xs text-gray-500 transition-colors hover:text-blue-600"
            >
                Powered by Tech Weekly Podcast
            </a>
        </div>

        <!-- Hidden Audio Element -->
        <audio
            ref="audioElement"
            :src="episode.url"
            preload="none"
            @loadedmetadata="handleLoadedMetadata"
            @timeupdate="handleTimeUpdate"
            @progress="handleProgress"
            @ended="handleEnded"
            @error="handleError"
            @loadstart="handleLoadStart"
            @canplay="handleCanPlay"
        ></audio>
    </div>
</template>

<script setup lang="ts">
import { useGlobalAudioManager } from '@/composables/useGlobalAudioManager';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Episode {
    id: number;
    title: string;
    filename: string;
    url: string;
    duration: number;
    published_date: string;
    description?: string;
}

interface Props {
    episode: Episode;
    autoplay?: boolean;
    fullSiteUrl?: string;
}

const props = withDefaults(defineProps<Props>(), {
    autoplay: false,
    fullSiteUrl: '/',
});

// Format duration from decimal minutes to MM:SS format
const formatDuration = (
    durationInMinutes: number | string | null | undefined,
): string => {
    // Convert string to number if needed
    const duration =
        typeof durationInMinutes === 'string'
            ? parseFloat(durationInMinutes)
            : durationInMinutes;

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

// Global audio manager
const { registerPlayer } = useGlobalAudioManager();
const audioManager = registerPlayer(() => {
    if (audioElement.value && isPlaying.value) {
        audioElement.value.pause();
        isPlaying.value = false;
    }
});

// Audio state
const audioElement = ref<HTMLAudioElement>();
const progressBar = ref<HTMLDivElement>();
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const volume = ref(1);
const isMuted = ref(false);
const loading = ref(true);
const error = ref('');
const buffered = ref<TimeRanges | null>(null);

// Computed properties
const progress = computed(() => {
    if (duration.value === 0) return 0;
    return (currentTime.value / duration.value) * 100;
});

const bufferedProgress = computed(() => {
    if (!buffered.value || duration.value === 0) return 0;

    let bufferedEnd = 0;
    for (let i = 0; i < buffered.value.length; i++) {
        if (
            buffered.value.start(i) <= currentTime.value &&
            buffered.value.end(i) > currentTime.value
        ) {
            bufferedEnd = buffered.value.end(i);
            break;
        }
    }

    return (bufferedEnd / duration.value) * 100;
});

// Methods
const togglePlayPause = async () => {
    if (!audioElement.value) return;

    try {
        if (isPlaying.value) {
            audioElement.value.pause();
            isPlaying.value = false;
            audioManager.notifyPause();
        } else {
            audioManager.notifyPlay(props.episode.id);
            await audioElement.value.play();
            isPlaying.value = true;
        }
    } catch (err) {
        error.value = 'Failed to play audio. Please try again.';
        console.error('Audio play error:', err);
    }
};

const toggleMute = () => {
    if (!audioElement.value) return;

    if (isMuted.value) {
        audioElement.value.volume = volume.value;
        isMuted.value = false;
    } else {
        audioElement.value.volume = 0;
        isMuted.value = true;
    }
};

const handleVolumeChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const newVolume = parseFloat(target.value);
    volume.value = newVolume;

    if (audioElement.value) {
        audioElement.value.volume = newVolume;
        isMuted.value = newVolume === 0;
    }
};

const handleProgressClick = (event: MouseEvent) => {
    if (!progressBar.value || !audioElement.value || duration.value === 0)
        return;

    const rect = progressBar.value.getBoundingClientRect();
    const clickX = event.clientX - rect.left;
    const percentage = clickX / rect.width;
    const newTime = percentage * duration.value;

    audioElement.value.currentTime = newTime;
    currentTime.value = newTime;
};

// Audio event handlers
const handleLoadedMetadata = () => {
    if (audioElement.value) {
        duration.value = audioElement.value.duration;
    }
};

const handleTimeUpdate = () => {
    if (audioElement.value) {
        currentTime.value = audioElement.value.currentTime;
    }
};

const handleProgress = () => {
    if (audioElement.value) {
        buffered.value = audioElement.value.buffered;
    }
};

const handleEnded = () => {
    isPlaying.value = false;
    currentTime.value = 0;
    audioManager.notifyStop();
};

const handleError = () => {
    error.value = 'Failed to load audio. Please check your connection.';
    loading.value = false;
};

const handleLoadStart = () => {
    loading.value = true;
    error.value = '';
};

const handleCanPlay = () => {
    loading.value = false;

    // Auto-play if enabled
    if (props.autoplay && audioElement.value) {
        audioManager.notifyPlay(props.episode.id);
        audioElement.value
            .play()
            .then(() => {
                isPlaying.value = true;
            })
            .catch(() => {
                // Auto-play failed (browser policy), ignore silently
            });
    }
};

// Utility functions
const formatTime = (seconds: number): string => {
    if (isNaN(seconds) || seconds < 0) return '0:00';

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);

    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    return `${minutes}:${secs.toString().padStart(2, '0')}`;
};

const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Lifecycle
onMounted(() => {
    if (audioElement.value) {
        audioElement.value.volume = volume.value;
    }
});

onUnmounted(() => {
    if (audioElement.value) {
        audioElement.value.pause();
    }
    // Unregister from global audio manager
    audioManager.unregister();
});
</script>

<style scoped>
/* Volume slider styling */
.volume-slider::-webkit-slider-thumb {
    appearance: none;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    border: 2px solid white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.volume-slider::-moz-range-thumb {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    border: 2px solid white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Progress bar hover effects */
.progress-bar:hover .progress-thumb {
    transform: translate(50%, -50%) scale(1.2);
}

/* Loading animation */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Responsive adjustments */
@media (max-width: 480px) {
    .controls-row {
        flex-wrap: wrap;
        gap: 8px;
    }

    .volume-control {
        order: 3;
        flex-basis: 100%;
        justify-content: center;
    }

    .volume-slider {
        width: 100px;
    }
}

/* High contrast support */
@media (prefers-contrast: high) {
    .progress-bar {
        border: 1px solid #000;
    }

    .current-progress {
        background: #000;
    }

    .progress-thumb {
        background: #000;
        border-color: #fff;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .progress-thumb,
    .play-pause-btn,
    .volume-btn,
    .external-link {
        transition: none;
    }

    .animate-spin {
        animation: none;
    }
}

/* Focus styles for accessibility */
.play-pause-btn:focus,
.volume-btn:focus,
.external-link:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

.volume-slider:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}
</style>
