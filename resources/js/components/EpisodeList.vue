<template>
    <div class="episode-list">
        <!-- Header -->
        <div class="list-header mb-8 text-center">
            <h1
                class="mb-2 flex items-center justify-center gap-3 text-3xl font-bold text-gray-900"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-lg"
                >
                </div>
                Tech Weekly update covering Software Engineering, DevOps, and Software Architecture topics.
            </h1>
            <p class="text-lg text-gray-600">
                {{ episodes.length }} episodes available
            </p>
        </div>

        <!-- Episodes Grid -->
        <div class="episodes-grid space-y-6">
            <div
                v-for="episode in sortedEpisodes"
                :key="episode.id"
                class="episode-card transform overflow-hidden rounded-xl border border-gray-100 bg-white shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl"
                :class="{
                    'translate-y-0 bg-gradient-to-r from-orange-500 to-white shadow-2xl ring-2 ring-orange-400':
                        currentEpisode?.id === episode.id,
                    'hover:ring-1 hover:ring-orange-200':
                        currentEpisode?.id !== episode.id,
                }"
            >
                <!-- Main Content Area -->
                <div class="episode-content p-6">
                    <div class="flex items-start space-x-6">
                        <!-- Large Play Button -->
                        <button
                            @click.stop="playEpisode(episode)"
                            class="play-btn group flex h-16 w-16 transform items-center justify-center rounded-full bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow-xl transition-all duration-300 hover:scale-105 hover:from-orange-600 hover:to-orange-700 hover:shadow-2xl focus:ring-4 focus:ring-orange-200 focus:outline-none"
                            :class="{
                                'scale-105 animate-pulse from-orange-600 to-orange-700 shadow-2xl':
                                    currentEpisode?.id === episode.id &&
                                    isPlaying,
                                'from-gray-400 to-gray-500': !episode.url,
                            }"
                            :disabled="!episode.url"
                            :aria-label="`Play ${episode.title}`"
                        >
                            <svg
                                v-if="
                                    currentEpisode?.id === episode.id &&
                                    isPlaying
                                "
                                class="h-8 w-8"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                            </svg>
                            <svg
                                v-else
                                class="ml-1 h-8 w-8 transition-all duration-200 group-hover:ml-2"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path d="M8 5v14l11-7z" />
                            </svg>
                        </button>

                        <!-- Episode Info -->
                        <div class="episode-info min-w-0 flex-1">
                            <div class="mb-3 flex items-start justify-between">
                                <div class="min-w-0 flex-1">
                                    <h3
                                        class="episode-title mb-2 line-clamp-2 cursor-pointer text-xl font-bold text-gray-900 transition-colors hover:text-orange-600"
                                        @click="selectEpisode(episode)"
                                    >
                                        {{ episode.title }}
                                    </h3>
                                    <div
                                        class="episode-meta flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-500"
                                    >
                                        <span
                                            class="flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1"
                                        >
                                            <svg
                                                class="h-4 w-4 text-orange-500"
                                                fill="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"
                                                />
                                            </svg>
                                            {{ episode.duration }}
                                        </span>
                                        <span
                                            class="flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1"
                                        >
                                            <svg
                                                class="h-4 w-4 text-orange-500"
                                                fill="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    d="M9 11H7v6h2v-6zm4 0h-2v6h2v-6zm4 0h-2v6h2v-6zm2-7H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zM5 20V6h14v14H5z"
                                                />
                                            </svg>
                                            {{
                                                formatDate(
                                                    episode.published_date,
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Waveform Section -->
                <div class="waveform-section px-6 pb-6">
                    <div
                        class="waveform-container group relative cursor-pointer rounded-lg bg-gray-50 p-4 transition-colors hover:bg-gray-100"
                        @click="handleWaveformClick(episode, $event)"
                        @mousemove="handleWaveformHover(episode, $event)"
                        @mouseleave="clearWaveformHover(episode)"
                    >
                        <!-- Waveform Bars -->
                        <div
                            class="waveform-bars flex h-12 items-end justify-between gap-0.5"
                        >
                            <div
                                v-for="(bar, index) in getWaveformBars(episode)"
                                :key="index"
                                class="waveform-bar rounded-sm bg-gray-300 transition-all duration-200 hover:bg-orange-300"
                                :class="{
                                    'bg-gradient-to-t from-orange-500 to-orange-400':
                                        isBarActive(episode, index),
                                    'animate-pulse bg-gradient-to-t from-orange-600 to-orange-500':
                                        isBarPlaying(episode, index),
                                    'bg-orange-200': isBarHovered(
                                        episode,
                                        index,
                                    ),
                                }"
                                :style="{
                                    height: `${bar.height}%`,
                                    width: '2px',
                                    minHeight: '6px',
                                }"
                                :data-index="index"
                            ></div>
                        </div>

                        <!-- Play Overlay - Only show when not playing and on hover -->
                        <div
                            v-if="currentEpisode?.id !== episode.id"
                            class="play-overlay pointer-events-none absolute inset-0 flex items-center justify-center rounded-lg bg-transparent transition-all duration-300"
                        >
                            <div
                                class="play-button bg-opacity-0 group-hover:bg-opacity-90 flex h-12 w-12 scale-0 transform items-center justify-center rounded-full bg-white shadow-lg transition-all duration-300 group-hover:scale-100"
                            >
                                <svg
                                    class="ml-0.5 h-6 w-6 text-orange-500"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Loading Overlay -->
                        <div
                            v-if="
                                currentEpisode?.id === episode.id && isLoading
                            "
                            class="loading-overlay bg-opacity-80 absolute inset-0 flex items-center justify-center rounded-lg bg-white"
                        >
                            <div class="flex items-end space-x-1">
                                <div
                                    v-for="i in 5"
                                    :key="i"
                                    class="loading-bar w-1 animate-bounce rounded-sm bg-orange-500"
                                    :style="{
                                        height: '20px',
                                        animationDelay: `${i * 0.1}s`,
                                    }"
                                ></div>
                            </div>
                        </div>

                        <!-- Time Display -->
                        <div
                            class="time-display mt-3 flex items-center justify-between text-xs text-gray-500"
                        >
                            <span>{{
                                currentEpisode?.id === episode.id
                                    ? formatTime(currentTime)
                                    : '0:00'
                            }}</span>
                            <span>{{ episode.duration }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="episodes.length === 0" class="empty-state py-16 text-center">
            <div
                class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-r from-orange-500 to-orange-600"
            >
                <span class="text-4xl">🎵</span>
            </div>
            <h3 class="mb-3 text-2xl font-bold text-gray-900">
                No episodes available
            </h3>
            <p class="text-lg text-gray-600">
                Check back later for new episodes!
            </p>
        </div>

        <!-- Embed Code Modal -->
        <div
            v-if="showEmbedModal"
            class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black p-4"
            @click="closeEmbedModal"
        >
            <div
                class="w-full max-w-lg rounded-xl bg-white p-8 shadow-2xl"
                @click.stop
            >
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-2xl font-bold text-gray-900">Embed Code</h3>
                    <button
                        @click="closeEmbedModal"
                        class="text-gray-400 transition-colors hover:text-gray-600"
                    >
                        <svg
                            class="h-6 w-6"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"
                            />
                        </svg>
                    </button>
                </div>
                <p class="mb-4 text-gray-600">
                    Copy this code to embed "<strong>{{
                        selectedEpisodeForEmbed?.title
                    }}</strong
                    >" on your website:
                </p>
                <textarea
                    ref="embedCodeTextarea"
                    :value="embedCode"
                    readonly
                    class="h-32 w-full resize-none rounded-lg border border-gray-300 bg-gray-50 p-4 font-mono text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-500 focus:outline-none"
                ></textarea>
                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        @click="closeEmbedModal"
                        class="rounded-lg px-6 py-3 font-medium text-gray-600 transition-colors hover:text-gray-800 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:outline-none"
                    >
                        Cancel
                    </button>
                    <button
                        @click="copyEmbedCode"
                        class="rounded-lg bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-3 font-medium text-white shadow-lg transition-all duration-200 hover:from-orange-600 hover:to-orange-700 hover:shadow-xl focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 focus:outline-none"
                    >
                        Copy Code
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Episode } from '@/composables/useAudioPlayer';
import { computed, reactive, ref } from 'vue';

interface Props {
    episodes: Episode[];
    currentEpisode?: Episode | null;
    isPlaying?: boolean;
    progress?: number;
    currentTime?: number;
    duration?: number;
    isLoading?: boolean;
}

interface Emits {
    (e: 'episodeSelect', episode: Episode): void;
    (e: 'episodePlay', episode: Episode): void;
}

const props = withDefaults(defineProps<Props>(), {
    currentEpisode: null,
    isPlaying: false,
    progress: 0,
    currentTime: 0,
    duration: 0,
    isLoading: false,
});

const emit = defineEmits<Emits>();

// Sort episodes by latest first (descending order by ID)
const sortedEpisodes = computed(() => {
    return [...props.episodes].sort((a, b) => b.id - a.id);
});

// Embed modal state
const showEmbedModal = ref(false);
const embedCode = ref('');
const selectedEpisodeForEmbed = ref<Episode | null>(null);
const embedCodeTextarea = ref<HTMLTextAreaElement>();

// Waveform state
const waveformData = reactive<
    Record<number, { bars: Array<{ height: number }>; hoverIndex: number }>
>({});

// Initialize waveform data for each episode
const initializeWaveform = (episode: Episode) => {
    if (!waveformData[episode.id]) {
        const bars = [];
        const totalBars = 60; // Increase number of bars for better visual density

        // Generate realistic waveform pattern similar to SoundCloud
        for (let i = 0; i < totalBars; i++) {
            // Create more varied and realistic waveform pattern
            const baseHeight = 25 + Math.sin(i * 0.15) * 20;
            const variation = Math.sin(i * 0.4) * 25 + Math.cos(i * 0.3) * 20;
            const randomness = (Math.random() - 0.5) * 30;
            const height = Math.max(
                15,
                Math.min(85, baseHeight + variation + randomness),
            );

            bars.push({ height });
        }

        waveformData[episode.id] = {
            bars,
            hoverIndex: -1,
        };
    }
};

// Get waveform bars for an episode
const getWaveformBars = (episode: Episode) => {
    initializeWaveform(episode);
    return waveformData[episode.id].bars;
};

// Check if a bar is active (played)
const isBarActive = (episode: Episode, index: number) => {
    if (props.currentEpisode?.id !== episode.id || !props.progress)
        return false;

    const totalBars = getWaveformBars(episode).length;
    const activeBarIndex = Math.floor((props.progress / 100) * totalBars);
    return index <= activeBarIndex;
};

// Check if a bar is currently playing
const isBarPlaying = (episode: Episode, index: number) => {
    if (props.currentEpisode?.id !== episode.id || !props.isPlaying)
        return false;

    const totalBars = getWaveformBars(episode).length;
    const activeBarIndex = Math.floor((props.progress / 100) * totalBars);
    return index === activeBarIndex;
};

// Check if a bar is hovered
const isBarHovered = (episode: Episode, index: number) => {
    if (!waveformData[episode.id]) return false;
    const hoverIndex = waveformData[episode.id].hoverIndex;
    return (
        hoverIndex >= 0 && index <= hoverIndex && !isBarActive(episode, index)
    );
};

// Handle waveform click for seeking
const handleWaveformClick = (episode: Episode, event: MouseEvent) => {
    if (props.currentEpisode?.id !== episode.id) {
        // If not current episode, play it first
        emit('episodePlay', episode);
        return;
    }

    const container = event.currentTarget as HTMLElement;
    const rect = container.getBoundingClientRect();
    const clickX = event.clientX - rect.left;
    const percentage = (clickX / rect.width) * 100;

    // Emit seek event (you'll need to handle this in the parent component)
    // For now, we'll just trigger a play event with the seek percentage
    console.log(`Seeking to ${percentage}% in episode ${episode.id}`);
};

// Handle waveform hover
const handleWaveformHover = (episode: Episode, event: MouseEvent) => {
    initializeWaveform(episode);

    const container = event.currentTarget as HTMLElement;
    const rect = container.getBoundingClientRect();
    const hoverX = event.clientX - rect.left;
    const percentage = (hoverX / rect.width) * 100;
    const totalBars = getWaveformBars(episode).length;
    const hoverIndex = Math.floor((percentage / 100) * totalBars);

    waveformData[episode.id].hoverIndex = hoverIndex;
};

// Clear waveform hover
const clearWaveformHover = (episode: Episode) => {
    if (waveformData[episode.id]) {
        waveformData[episode.id].hoverIndex = -1;
    }
};

// Methods
const selectEpisode = (episode: Episode) => {
    emit('episodeSelect', episode);
};

const playEpisode = (episode: Episode) => {
    emit('episodePlay', episode);
};



const closeEmbedModal = () => {
    showEmbedModal.value = false;
    selectedEpisodeForEmbed.value = null;
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

const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

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
</script>

<style scoped>
/* Line clamp utility for episode descriptions */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* SoundCloud-style animations */
@keyframes waveform-pulse {
    0%,
    100% {
        transform: scaleY(1);
    }
    50% {
        transform: scaleY(1.2);
    }
}

@keyframes loading-bounce {
    0%,
    100% {
        transform: scaleY(1);
    }
    50% {
        transform: scaleY(1.5);
    }
}

.waveform-bar.animate-pulse {
    animation: waveform-pulse 1s ease-in-out infinite;
}

.loading-bar {
    animation: loading-bounce 1s ease-in-out infinite;
}

/* Hover effects */
.episode-card:hover .waveform-container {
    background-color: #f9fafb;
}

.episode-card:hover .play-btn {
    transform: scale(1.05);
    box-shadow:
        0 20px 25px -5px rgba(0, 0, 0, 0.1),
        0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.action-btn:hover {
    transform: translateY(-1px);
}

/* Waveform interactions */
.waveform-container:hover .waveform-bar {
    transition: all 0.2s ease;
}

.waveform-bar:hover {
    transform: scaleY(1.1);
}

/* Play button animations */
.play-btn svg {
    transition: all 0.2s ease;
}

.play-btn:hover svg {
    transform: scale(1.1);
}

/* Episode card states */
.episode-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.episode-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

/* Orange gradient utilities */
.bg-orange-gradient {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
}

.text-orange-gradient {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Focus states for accessibility */
.episode-card:focus-within {
    outline: 2px solid #f97316;
    outline-offset: 2px;
}

.play-btn:focus {
    outline: none;
    box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.3);
}

.action-btn:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.3);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .episode-content {
        padding: 1rem;
    }

    .episode-content > div {
        flex-direction: column;
        space-x: 0;
        gap: 1rem;
        text-align: center;
    }

    .play-btn {
        width: 3.5rem;
        height: 3.5rem;
    }

    .episode-info {
        text-align: center;
    }

    .episode-meta {
        justify-content: center;
        flex-wrap: wrap;
    }

    .waveform-bars {
        height: 3rem;
    }

    .action-btn {
        padding: 0.5rem;
    }
}

@media (max-width: 640px) {
    .episode-meta span {
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
    }

    .waveform-section {
        padding: 1rem;
    }

    .waveform-container {
        padding: 0.75rem;
    }
}

/* High contrast support */
@media (prefers-contrast: high) {
    .episode-card {
        border-width: 2px;
        border-color: #374151;
    }

    .waveform-bar {
        border: 1px solid #374151;
    }

    .play-btn {
        border: 2px solid #ffffff;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .episode-card,
    .play-btn,
    .action-btn,
    .waveform-bar,
    .loading-bar {
        transition: none;
        animation: none;
    }

    .episode-card:hover {
        transform: none;
    }

    .play-btn:hover {
        transform: none;
    }
}

/* Dark mode support (if needed) */
@media (prefers-color-scheme: dark) {
    .episode-card {
        background-color: #1f2937;
        border-color: #374151;
    }

    .episode-title {
        color: #f9fafb;
    }

    .episode-meta {
        color: #9ca3af;
    }

    .waveform-container {
        background-color: #374151;
    }

    .waveform-bar {
        background-color: #6b7280;
    }
}

/* Custom scrollbar for modal */
.embed-modal textarea::-webkit-scrollbar {
    width: 8px;
}

.embed-modal textarea::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.embed-modal textarea::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.embed-modal textarea::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
