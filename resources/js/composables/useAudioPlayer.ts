import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';
import { useGlobalAudioManager } from './useGlobalAudioManager';
import { formatTime } from '@/lib/time';
import type { Episode } from '@/types/episode';
import type { AudioState, AudioPlayerOptions } from '@/types/audio';

// Re-export types for backward compatibility
export type { Episode } from '@/types/episode';
export type { AudioState, AudioPlayerOptions } from '@/types/audio';

/**
 * Unified audio player composable
 *
 * Handles both direct URL playback and streaming URL fetching.
 * Integrates with GlobalAudioManager for exclusive playback across frames.
 *
 * @param episode - Optional episode to initialize with on mount
 * @param options - Optional configuration for playback behavior
 */
export function useAudioPlayer(episode?: Episode, options?: AudioPlayerOptions) {
    const audioElement = ref<HTMLAudioElement | null>(null);
    let currentEpisode: Episode | null = null;

    // Global audio manager for exclusive playback
    const { registerPlayer } = useGlobalAudioManager();
    const audioManager = registerPlayer(() => {
        if (audioElement.value && !audioElement.value.paused) {
            audioElement.value.pause();
        }
    });

    const audioState = reactive<AudioState>({
        isPlaying: false,
        currentTime: 0,
        duration: 0,
        volume: 80,
        isLoading: false,
        error: null,
        buffered: 0,
        canSeek: false,
    });

    // Computed properties
    const progress = computed(() => {
        if (audioState.duration === 0) return 0;
        return (audioState.currentTime / audioState.duration) * 100;
    });

    const formattedCurrentTime = computed(() => formatTime(audioState.currentTime));
    const formattedDuration = computed(() => formatTime(audioState.duration));

    /**
     * Fetch streaming URL from API endpoint
     * Falls back to episode.url on failure
     */
    const getStreamingUrl = async (episodeData: Episode): Promise<string> => {
        try {
            const response = await fetch(`/api/episodes/${episodeData.id}/stream`);
            const data = await response.json();

            if (data.stream_url) {
                return data.stream_url;
            }

            // Fallback to direct URL if streaming not available
            return episodeData.url;
        } catch (error) {
            console.warn('Failed to get streaming URL, using direct URL:', error);
            return episodeData.url;
        }
    };

    /**
     * Determine the audio source URL based on episode data and options
     */
    const resolveAudioSource = (episodeData: Episode): string => {
        // If URL is already a full HTTP URL, use it directly
        if (episodeData.url && episodeData.url.startsWith('http')) {
            return episodeData.url;
        }
        // Otherwise use the streaming API endpoint
        return `/api/stream/${episodeData.filename}`;
    };

    /**
     * Initialize audio element with an episode
     *
     * @param episodeData - The episode to play
     * @param initOptions - Override options for this initialization
     */
    const initAudio = async (
        episodeData: Episode,
        initOptions?: AudioPlayerOptions,
    ) => {
        const opts = { ...options, ...initOptions };
        const fetchStreamUrl = opts?.fetchStreamUrl ?? false;
        const preload = opts?.preload ?? 'metadata';
        const autoPlay = opts?.autoPlay ?? false;

        // Cleanup existing audio element
        if (audioElement.value) {
            audioElement.value.pause();
            audioElement.value.src = '';
        }

        // Reset state
        audioState.isLoading = true;
        audioState.error = null;
        audioState.canSeek = false;
        audioState.currentTime = 0;
        audioState.duration = 0;
        audioState.buffered = 0;
        currentEpisode = episodeData;

        try {
            // Determine source URL
            const source = fetchStreamUrl
                ? await getStreamingUrl(episodeData)
                : resolveAudioSource(episodeData);

            audioElement.value = new Audio();

            // Configure for optimal streaming
            audioElement.value.preload = preload;
            audioElement.value.crossOrigin = 'anonymous';

            // Set up event listeners before setting src
            setupAudioListeners();

            // Set the source URL
            audioElement.value.src = source;
            audioElement.value.volume = audioState.volume / 100;

            if (autoPlay) {
                // Wait for enough data to play
                audioElement.value.addEventListener(
                    'canplay',
                    () => {
                        play();
                    },
                    { once: true },
                );
            }
        } catch (error) {
            audioState.error = 'Failed to initialize audio';
            audioState.isLoading = false;
            console.error('Audio initialization error:', error);
        }
    };

    /**
     * Setup all audio element event listeners
     */
    const setupAudioListeners = () => {
        if (!audioElement.value) return;

        const audio = audioElement.value;

        audio.addEventListener('loadstart', () => {
            audioState.isLoading = true;
            audioState.error = null;
        });

        audio.addEventListener('loadedmetadata', () => {
            audioState.duration = audio.duration || 0;
            audioState.canSeek = true;
            audioState.isLoading = false;
        });

        audio.addEventListener('timeupdate', () => {
            audioState.currentTime = audio.currentTime || 0;
        });

        audio.addEventListener('progress', () => {
            if (audio.buffered.length > 0) {
                const bufferedEnd = audio.buffered.end(audio.buffered.length - 1);
                audioState.buffered =
                    audioState.duration > 0
                        ? (bufferedEnd / audioState.duration) * 100
                        : 0;
            }
        });

        audio.addEventListener('canplay', () => {
            audioState.isLoading = false;
            audioState.canSeek = true;
        });

        audio.addEventListener('canplaythrough', () => {
            audioState.isLoading = false;
        });

        audio.addEventListener('play', () => {
            audioState.isPlaying = true;
            audioManager.notifyPlay(currentEpisode?.id);
        });

        audio.addEventListener('pause', () => {
            audioState.isPlaying = false;
            audioManager.notifyPause();
        });

        audio.addEventListener('ended', () => {
            audioState.isPlaying = false;
            audioState.currentTime = 0;
            audioManager.notifyStop();
        });

        audio.addEventListener('error', (e) => {
            audioState.error = 'Failed to load or play audio';
            audioState.isLoading = false;
            audioState.isPlaying = false;
            console.error('Audio error:', e);
        });

        audio.addEventListener('waiting', () => {
            audioState.isLoading = true;
        });

        audio.addEventListener('seeking', () => {
            audioState.isLoading = true;
        });

        audio.addEventListener('seeked', () => {
            audioState.isLoading = false;
        });

        // Network state logging for debugging streaming issues
        audio.addEventListener('stalled', () => {
            console.log('Audio stalled - network may be slow');
        });

        audio.addEventListener('suspend', () => {
            // Normal behavior - browser paused downloading
        });
    };

    /**
     * Start or resume playback
     */
    const play = async () => {
        if (!audioElement.value) return;

        try {
            audioManager.notifyPlay(currentEpisode?.id);
            await audioElement.value.play();
        } catch (error) {
            audioState.error = 'Failed to play audio';
            console.error('Audio play error:', error);
        }
    };

    /**
     * Pause playback
     */
    const pause = () => {
        if (!audioElement.value) return;
        audioElement.value.pause();
    };

    /**
     * Toggle between play and pause
     */
    const togglePlay = async () => {
        if (audioState.isPlaying) {
            pause();
        } else {
            await play();
        }
    };

    /**
     * Seek to a specific time in seconds
     */
    const seek = (time: number) => {
        if (!audioElement.value || !audioState.canSeek) return;

        // Clamp time to valid range
        const clampedTime = Math.max(0, Math.min(time, audioState.duration));
        audioElement.value.currentTime = clampedTime;
    };

    /**
     * Seek to a percentage of the total duration
     */
    const seekToPercentage = (percentage: number) => {
        if (!audioElement.value || audioState.duration === 0 || !audioState.canSeek)
            return;

        const time = (percentage / 100) * audioState.duration;
        seek(time);
    };

    /**
     * Set volume (0-100)
     */
    const setVolume = (volume: number) => {
        audioState.volume = Math.max(0, Math.min(100, volume));
        if (audioElement.value) {
            audioElement.value.volume = audioState.volume / 100;
        }
    };

    /**
     * Toggle mute state
     */
    const mute = () => {
        if (audioElement.value) {
            audioElement.value.muted = !audioElement.value.muted;
        }
    };

    /**
     * Check if audio is muted
     */
    const isMuted = computed(() => audioElement.value?.muted ?? false);

    /**
     * Cleanup audio resources
     */
    const cleanup = () => {
        if (audioElement.value) {
            audioElement.value.pause();
            audioElement.value.src = '';
            audioElement.value = null;
        }
        currentEpisode = null;
        audioManager.unregister();
    };

    // Initialize with episode if provided
    if (episode) {
        onMounted(() => {
            initAudio(episode, options);
        });
    }

    onUnmounted(() => {
        cleanup();
    });

    return {
        // State
        audioState,
        audioElement,

        // Computed
        progress,
        formattedCurrentTime,
        formattedDuration,
        isMuted,

        // Methods
        initAudio,
        play,
        pause,
        togglePlay,
        seek,
        seekToPercentage,
        setVolume,
        mute,
        cleanup,
        getStreamingUrl,

        // Utilities (re-exported for convenience)
        formatTime,

        // Audio manager (for advanced use cases)
        audioManager,
    };
}
