/**
 * Canonical AudioState type
 *
 * This is the single source of truth for audio playback state across the frontend.
 * All audio composables and components should import AudioState from this file.
 */
export interface AudioState {
    /** Whether audio is currently playing */
    isPlaying: boolean;
    /** Current playback position in seconds */
    currentTime: number;
    /** Total duration in seconds */
    duration: number;
    /** Volume level (0-100) */
    volume: number;
    /** Whether audio is loading/buffering */
    isLoading: boolean;
    /** Error message if playback failed, null otherwise */
    error: string | null;
    /** Buffered percentage (0-100) */
    buffered: number;
    /** Whether seeking is allowed (metadata must be loaded first) */
    canSeek: boolean;
}

/**
 * Options for initializing the audio player
 */
export interface AudioPlayerOptions {
    /**
     * Whether to fetch a streaming URL from the API before playing.
     * When true, calls /api/episodes/:id/stream to get an optimized streaming URL.
     * When false, uses episode.url directly.
     * @default false
     */
    fetchStreamUrl?: boolean;
    /**
     * Whether to start playing immediately after initialization.
     * @default false
     */
    autoPlay?: boolean;
    /**
     * Preload strategy for the audio element.
     * - 'none': Don't preload anything (saves bandwidth, requires user action)
     * - 'metadata': Only load metadata like duration (good for streaming)
     * - 'auto': Let browser decide (may preload entire file)
     * @default 'metadata'
     */
    preload?: 'none' | 'metadata' | 'auto';
}
