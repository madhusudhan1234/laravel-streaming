<template>
    <div
        ref="containerRef"
        @click="handleWaveformClick"
        @mousemove="handleWaveformHover"
        @mouseleave="handleWaveformLeave"
        class="waveform-container group relative cursor-pointer overflow-hidden rounded-lg p-4"
        :class="[
            containerClass,
            { playing: isPlaying },
            interactive ? 'cursor-pointer' : 'cursor-default',
        ]"
        role="slider"
        :aria-valuenow="currentTime"
        :aria-valuemin="0"
        :aria-valuemax="duration"
        aria-label="Audio waveform - click to seek"
    >
        <!-- Background Waveform -->
        <div
            class="waveform-bars flex items-end justify-between"
            :class="barsContainerClass"
            :style="{ height: `${height}px` }"
        >
            <div
                v-for="(bar, index) in bars"
                :key="index"
                class="waveform-bar relative overflow-hidden rounded-t-sm transition-all duration-300 ease-out"
                :class="[
                    getBarClass(index),
                    barClass,
                ]"
                :style="{
                    height: `${bar.height}%`,
                    minHeight: `${minBarHeight}px`,
                    width: `${computedBarWidth}px`,
                }"
            >
                <!-- Progress Fill -->
                <div
                    class="progress-fill absolute bottom-0 left-0 w-full transition-all duration-300 ease-out"
                    :class="getProgressFillClass(index)"
                    :style="{ height: getProgressFillHeight(index) }"
                ></div>

                <!-- Hover Effect -->
                <div
                    v-if="showHover && hoverIndex >= 0 && index <= hoverIndex && index > progressBarIndex"
                    class="hover-fill absolute bottom-0 left-0 w-full transition-opacity duration-200"
                    :class="hoverFillClass"
                    :style="{ height: '100%' }"
                ></div>
            </div>
        </div>

        <!-- Play Button Overlay -->
        <div
            v-if="showPlayOverlay && !isPlaying && !isLoading"
            class="play-overlay absolute inset-0 flex items-center justify-center bg-black/20 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
        >
            <div
                class="play-button transform rounded-full bg-orange-500 p-3 text-white shadow-lg transition-all duration-200 hover:scale-110 hover:bg-orange-600"
            >
                <svg class="ml-1 h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z" />
                </svg>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div
            v-if="showLoadingOverlay && isLoading"
            class="loading-overlay absolute inset-0 flex items-center justify-center rounded-lg bg-white/80"
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
            v-if="showTimeDisplay"
            class="time-display absolute right-4 bottom-1 left-4 flex justify-between text-xs text-gray-600"
        >
            <span class="rounded bg-white/90 px-2 py-1 shadow-sm">{{
                formattedCurrentTime
            }}</span>
            <span class="rounded bg-white/90 px-2 py-1 shadow-sm">{{
                formattedDuration
            }}</span>
        </div>

        <!-- Slot for custom content -->
        <slot></slot>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import {
    generateWaveformBars,
    type WaveformBar,
    type UseWaveformOptions,
} from '@/composables/useWaveform';
import { formatTime } from '@/lib/time';

interface Props {
    /**
     * Progress percentage (0-100)
     */
    progress?: number;
    /**
     * Current playback time in seconds
     */
    currentTime?: number;
    /**
     * Total duration in seconds
     */
    duration?: number;
    /**
     * Whether audio is currently playing
     */
    isPlaying?: boolean;
    /**
     * Whether audio is loading
     */
    isLoading?: boolean;
    /**
     * Total number of bars
     */
    totalBars?: number;
    /**
     * Seed for deterministic waveform generation
     */
    seed?: number;
    /**
     * Pre-generated bars (overrides totalBars/seed)
     */
    bars?: WaveformBar[];
    /**
     * Height of the waveform in pixels
     */
    height?: number;
    /**
     * Minimum bar height in pixels
     */
    minBarHeight?: number;
    /**
     * Fixed bar width in pixels (computed from container if not provided)
     */
    barWidth?: number;
    /**
     * Whether waveform is interactive (clickable)
     */
    interactive?: boolean;
    /**
     * Show hover highlighting
     */
    showHover?: boolean;
    /**
     * Show play button overlay on hover
     */
    showPlayOverlay?: boolean;
    /**
     * Show loading overlay
     */
    showLoadingOverlay?: boolean;
    /**
     * Show time display
     */
    showTimeDisplay?: boolean;
    /**
     * Custom class for container
     */
    containerClass?: string;
    /**
     * Custom class for bars container
     */
    barsContainerClass?: string;
    /**
     * Custom class for individual bars
     */
    barClass?: string;
    /**
     * Custom class for progress fill
     */
    progressFillClass?: string;
    /**
     * Custom class for hover fill
     */
    hoverFillClass?: string;
}

interface Emits {
    (e: 'seek', percentage: number): void;
    (e: 'hover', index: number): void;
    (e: 'leave'): void;
    (e: 'click', event: MouseEvent): void;
}

const props = withDefaults(defineProps<Props>(), {
    progress: 0,
    currentTime: 0,
    duration: 0,
    isPlaying: false,
    isLoading: false,
    totalBars: 60,
    seed: undefined,
    bars: undefined,
    height: 64,
    minBarHeight: 4,
    barWidth: undefined,
    interactive: true,
    showHover: true,
    showPlayOverlay: false,
    showLoadingOverlay: true,
    showTimeDisplay: true,
    containerClass: 'bg-gradient-to-r from-gray-100 to-gray-50',
    barsContainerClass: '',
    barClass: 'bg-gray-400 hover:bg-gray-500',
    progressFillClass: 'bg-gradient-to-t from-orange-500 to-orange-400 shadow-sm',
    hoverFillClass: 'bg-gradient-to-t from-orange-300 to-orange-200 opacity-60',
});

const emit = defineEmits<Emits>();

// Refs
const containerRef = ref<HTMLElement>();
const hoverIndex = ref(-1);

// Generate or use provided bars
const generatedBars = ref<WaveformBar[]>([]);

const bars = computed(() => {
    if (props.bars) return props.bars;
    return generatedBars.value;
});

// Generate bars on mount or when options change
const generateBars = () => {
    if (!props.bars) {
        const options: UseWaveformOptions = {
            totalBars: props.totalBars,
            seed: props.seed,
        };
        generatedBars.value = generateWaveformBars(options);
    }
};

// Watch for changes that require regeneration
watch(
    () => [props.totalBars, props.seed],
    () => generateBars(),
    { immediate: true }
);

// Computed bar width
const computedBarWidth = computed(() => {
    if (props.barWidth) return props.barWidth;
    if (!containerRef.value) return 2;
    const containerWidth = containerRef.value.offsetWidth - 32;
    const gap = 1;
    return Math.max(2, Math.floor((containerWidth - (props.totalBars - 1) * gap) / props.totalBars));
});

// Progress calculations
const progressBarIndex = computed(() => {
    return Math.floor((props.progress / 100) * props.totalBars);
});

const progressWithinBar = computed(() => {
    const exactProgress = (props.progress / 100) * props.totalBars;
    return (exactProgress - Math.floor(exactProgress)) * 100;
});

// Formatted time strings
const formattedCurrentTime = computed(() => formatTime(props.currentTime));
const formattedDuration = computed(() => formatTime(props.duration));

// Bar styling helpers
const getBarClass = (index: number): string => {
    const classes: string[] = [];

    if (index <= progressBarIndex.value) {
        classes.push('played');
    }

    if (props.isPlaying && index === progressBarIndex.value) {
        classes.push('animate-pulse');
    }

    return classes.join(' ');
};

const getProgressFillClass = (index: number): string => {
    if (index <= progressBarIndex.value) {
        return props.progressFillClass;
    }
    return 'bg-transparent';
};

const getProgressFillHeight = (index: number): string => {
    if (index < progressBarIndex.value) {
        return '100%';
    }
    if (index === progressBarIndex.value) {
        return `${progressWithinBar.value}%`;
    }
    return '0%';
};

// Event handlers
const getPercentageFromEvent = (event: MouseEvent): number | null => {
    if (!containerRef.value) return null;
    const rect = containerRef.value.getBoundingClientRect();
    const x = event.clientX - rect.left;
    return Math.max(0, Math.min(100, (x / rect.width) * 100));
};

const handleWaveformClick = (event: MouseEvent) => {
    emit('click', event);

    if (!props.interactive || props.duration === 0) return;

    const percentage = getPercentageFromEvent(event);
    if (percentage !== null) {
        emit('seek', percentage);
    }
};

const handleWaveformHover = (event: MouseEvent) => {
    if (!props.showHover) return;

    const percentage = getPercentageFromEvent(event);
    if (percentage !== null) {
        hoverIndex.value = Math.floor((percentage / 100) * props.totalBars);
        emit('hover', hoverIndex.value);
    }
};

const handleWaveformLeave = () => {
    hoverIndex.value = -1;
    emit('leave');
};

// Resize handling
const handleResize = () => {
    // Trigger reactivity update
    if (containerRef.value) {
        containerRef.value.style.width = containerRef.value.style.width;
    }
};

onMounted(() => {
    window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
});

// Expose for parent component access
defineExpose({
    containerRef,
    hoverIndex,
    progressBarIndex,
    progressWithinBar,
});
</script>

<style scoped>
/* Waveform animations */
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

/* Loading animation */
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

/* Hover effects */
.waveform-container:hover .waveform-bar {
    transform: scaleY(1.02);
}

.waveform-container:hover .play-overlay {
    backdrop-filter: blur(2px);
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .waveform-bar,
    .progress-fill,
    .play-button,
    .loading-bar {
        animation: none;
        transition: none;
    }
}
</style>
