import { computed, ref } from 'vue';

export interface WaveformBar {
    height: number;
}

export interface UseWaveformOptions {
    /**
     * Total number of bars in the waveform
     * @default 60
     */
    totalBars?: number;
    /**
     * Seed for deterministic waveform generation (optional)
     * When provided, the same seed produces the same waveform
     */
    seed?: number;
    /**
     * Minimum bar height percentage
     * @default 10
     */
    minHeight?: number;
    /**
     * Maximum bar height percentage
     * @default 85
     */
    maxHeight?: number;
}

/**
 * Seeded random number generator for deterministic waveforms
 * Uses a simple LCG (Linear Congruential Generator)
 */
function seededRandom(seed: number): () => number {
    let state = seed;
    return () => {
        state = (state * 1664525 + 1013904223) % 4294967296;
        return state / 4294967296;
    };
}

/**
 * Generate waveform bars with a realistic audio-like pattern
 */
export function generateWaveformBars(options: UseWaveformOptions = {}): WaveformBar[] {
    const {
        totalBars = 60,
        seed,
        minHeight = 10,
        maxHeight = 85,
    } = options;

    const random = seed !== undefined ? seededRandom(seed) : Math.random;
    const bars: WaveformBar[] = [];

    for (let i = 0; i < totalBars; i++) {
        // Multi-layer wave algorithm for realistic appearance
        const mainWave = Math.sin(i * 0.08) * 0.4;
        const secondaryWave = Math.sin(i * 0.23) * 0.25;
        const detailWave = Math.sin(i * 0.47) * 0.15;

        // Combine waves and add randomness
        const combined = mainWave + secondaryWave + detailWave;
        const randomness = (random() - 0.5) * 0.3;

        // Scale to percentage range
        let height = ((combined + randomness + 1) / 2) * (maxHeight - minHeight) + minHeight;

        // Make every 3rd bar smaller for visual detail
        if (i % 3 === 0) {
            height *= 0.6;
        }

        // Clamp to bounds
        height = Math.max(minHeight, Math.min(maxHeight, height));

        bars.push({ height });
    }

    return bars;
}

/**
 * Composable for waveform visualization and interaction
 *
 * @param options - Configuration options for the waveform
 */
export function useWaveform(options: UseWaveformOptions = {}) {
    const { totalBars = 60 } = options;

    // State
    const hoverIndex = ref(-1);
    const containerRef = ref<HTMLElement | null>(null);

    // Generate bars (can be regenerated with different options)
    const waveformBars = ref<WaveformBar[]>(generateWaveformBars(options));

    /**
     * Regenerate waveform with new options
     */
    const regenerate = (newOptions?: UseWaveformOptions) => {
        waveformBars.value = generateWaveformBars({ ...options, ...newOptions });
    };

    /**
     * Calculate bar width based on container size
     */
    const barWidth = computed(() => {
        if (!containerRef.value) return 2;
        const containerWidth = containerRef.value.offsetWidth - 32; // Account for padding
        const gap = 1; // Gap between bars
        return Math.max(2, Math.floor((containerWidth - (totalBars - 1) * gap) / totalBars));
    });

    /**
     * Calculate which bar index the progress corresponds to
     */
    const getProgressBarIndex = (progress: number): number => {
        return Math.floor((progress / 100) * totalBars);
    };

    /**
     * Calculate the fill percentage within the current progress bar
     */
    const getProgressWithinBar = (progress: number): number => {
        const exactProgress = (progress / 100) * totalBars;
        return (exactProgress - Math.floor(exactProgress)) * 100;
    };

    /**
     * Get percentage position from a mouse event relative to container
     */
    const getPercentageFromEvent = (event: MouseEvent, container?: HTMLElement): number | null => {
        const el = container || containerRef.value;
        if (!el) return null;

        const rect = el.getBoundingClientRect();
        const x = event.clientX - rect.left;
        return Math.max(0, Math.min(100, (x / rect.width) * 100));
    };

    /**
     * Get bar index from a mouse event
     */
    const getBarIndexFromEvent = (event: MouseEvent, container?: HTMLElement): number | null => {
        const percentage = getPercentageFromEvent(event, container);
        if (percentage === null) return null;
        return Math.floor((percentage / 100) * totalBars);
    };

    /**
     * Handle waveform click - returns the percentage for seeking
     */
    const handleClick = (event: MouseEvent, container?: HTMLElement): number | null => {
        return getPercentageFromEvent(event, container);
    };

    /**
     * Handle waveform hover - updates hoverIndex
     */
    const handleHover = (event: MouseEvent, container?: HTMLElement) => {
        const index = getBarIndexFromEvent(event, container);
        if (index !== null) {
            hoverIndex.value = index;
        }
    };

    /**
     * Handle mouse leave - clears hover state
     */
    const handleLeave = () => {
        hoverIndex.value = -1;
    };

    /**
     * Check if a bar should show hover highlight
     */
    const isBarHovered = (index: number, progressBarIndex?: number): boolean => {
        if (hoverIndex.value < 0) return false;
        // Don't show hover on already-played bars
        if (progressBarIndex !== undefined && index <= progressBarIndex) return false;
        return index <= hoverIndex.value;
    };

    /**
     * Check if a bar is fully played (before the progress bar)
     */
    const isBarPlayed = (index: number, progress: number): boolean => {
        const progressBarIndex = getProgressBarIndex(progress);
        return index < progressBarIndex;
    };

    /**
     * Check if a bar is the current progress bar
     */
    const isBarCurrent = (index: number, progress: number): boolean => {
        const progressBarIndex = getProgressBarIndex(progress);
        return index === progressBarIndex;
    };

    return {
        // State
        waveformBars,
        hoverIndex,
        containerRef,

        // Computed
        barWidth,

        // Methods
        regenerate,
        getProgressBarIndex,
        getProgressWithinBar,
        getPercentageFromEvent,
        getBarIndexFromEvent,
        handleClick,
        handleHover,
        handleLeave,
        isBarHovered,
        isBarPlayed,
        isBarCurrent,
    };
}

/**
 * Create a waveform store for managing multiple waveforms (e.g., episode list)
 */
export function useWaveformStore(defaultOptions: UseWaveformOptions = {}) {
    const waveforms = ref<Map<number | string, { bars: WaveformBar[]; hoverIndex: number }>>(
        new Map()
    );

    /**
     * Get or create waveform data for an ID
     */
    const getWaveform = (id: number | string) => {
        if (!waveforms.value.has(id)) {
            // Use ID as seed for deterministic waveform per item
            const seed = typeof id === 'number' ? id : hashString(id);
            waveforms.value.set(id, {
                bars: generateWaveformBars({ ...defaultOptions, seed }),
                hoverIndex: -1,
            });
        }
        return waveforms.value.get(id)!;
    };

    /**
     * Get waveform bars for an ID
     */
    const getBars = (id: number | string): WaveformBar[] => {
        return getWaveform(id).bars;
    };

    /**
     * Get hover index for an ID
     */
    const getHoverIndex = (id: number | string): number => {
        return getWaveform(id).hoverIndex;
    };

    /**
     * Set hover index for an ID
     */
    const setHoverIndex = (id: number | string, index: number) => {
        const waveform = getWaveform(id);
        waveform.hoverIndex = index;
    };

    /**
     * Clear hover for an ID
     */
    const clearHover = (id: number | string) => {
        const waveform = waveforms.value.get(id);
        if (waveform) {
            waveform.hoverIndex = -1;
        }
    };

    /**
     * Clear all waveforms
     */
    const clear = () => {
        waveforms.value.clear();
    };

    return {
        waveforms,
        getWaveform,
        getBars,
        getHoverIndex,
        setHoverIndex,
        clearHover,
        clear,
    };
}

/**
 * Simple string hash function for generating seeds from string IDs
 */
function hashString(str: string): number {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        const char = str.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash; // Convert to 32bit integer
    }
    return Math.abs(hash);
}
