import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';

export interface Episode {
    id: number;
    title: string;
    filename: string;
    duration: string;
    file_size: string;
    format: string;
    published_date: string;
    description?: string;
    url: string;
}

export interface AudioState {
    isPlaying: boolean;
    currentTime: number;
    duration: number;
    volume: number;
    isLoading: boolean;
    error: string | null;
    buffered: number;
    canSeek: boolean;
}

export function useAudioStreaming(episode?: Episode) {
    const audioElement = ref<HTMLAudioElement | null>(null);

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

    const formattedCurrentTime = computed(() =>
        formatTime(audioState.currentTime),
    );
    const formattedDuration = computed(() => formatTime(audioState.duration));

    // Get streaming URL for episode
    const getStreamingUrl = async (episodeData: Episode): Promise<string> => {
        try {
            const response = await fetch(
                `/api/episodes/${episodeData.id}/stream`,
            );
            const data = await response.json();

            if (data.stream_url) {
                return data.stream_url;
            }

            // Fallback to direct URL if streaming not available
            return episodeData.url;
        } catch (error) {
            console.warn(
                'Failed to get streaming URL, using direct URL:',
                error,
            );
            return episodeData.url;
        }
    };

    // Initialize audio element with streaming support
    const initAudio = async (episodeData: Episode) => {
        if (audioElement.value) {
            audioElement.value.pause();
            audioElement.value.src = '';
        }

        audioState.isLoading = true;
        audioState.error = null;

        try {
            // Get streaming URL
            const streamUrl = await getStreamingUrl(episodeData);

            audioElement.value = new Audio();

            // Configure for progressive streaming
            audioElement.value.preload = 'metadata'; // Load metadata for duration info
            audioElement.value.crossOrigin = 'anonymous';

            // Set up event listeners before setting src
            setupAudioListeners();

            // Set the streaming URL
            audioElement.value.src = streamUrl;
            audioElement.value.volume = audioState.volume / 100;
        } catch (error) {
            audioState.error = 'Failed to initialize audio streaming';
            audioState.isLoading = false;
            console.error('Audio initialization error:', error);
        }
    };

    // Setup event listeners with streaming optimizations
    const setupAudioListeners = () => {
        if (!audioElement.value) return;

        audioElement.value.addEventListener('loadstart', () => {
            audioState.isLoading = true;
            audioState.error = null;
        });

        audioElement.value.addEventListener('loadedmetadata', () => {
            audioState.duration = audioElement.value?.duration || 0;
            audioState.canSeek = true;
            audioState.isLoading = false;
        });

        audioElement.value.addEventListener('timeupdate', () => {
            audioState.currentTime = audioElement.value?.currentTime || 0;
        });

        audioElement.value.addEventListener('progress', () => {
            if (audioElement.value && audioElement.value.buffered.length > 0) {
                const bufferedEnd = audioElement.value.buffered.end(
                    audioElement.value.buffered.length - 1,
                );
                audioState.buffered = (bufferedEnd / audioState.duration) * 100;
            }
        });

        audioElement.value.addEventListener('canplay', () => {
            audioState.isLoading = false;
            audioState.canSeek = true;
        });

        audioElement.value.addEventListener('canplaythrough', () => {
            audioState.isLoading = false;
        });

        audioElement.value.addEventListener('play', () => {
            audioState.isPlaying = true;
        });

        audioElement.value.addEventListener('pause', () => {
            audioState.isPlaying = false;
        });

        audioElement.value.addEventListener('ended', () => {
            audioState.isPlaying = false;
            audioState.currentTime = 0;
        });

        audioElement.value.addEventListener('error', (e) => {
            audioState.error = 'Failed to load or stream audio file';
            audioState.isLoading = false;
            audioState.isPlaying = false;
            console.error('Audio streaming error:', e);
        });

        audioElement.value.addEventListener('waiting', () => {
            audioState.isLoading = true;
        });

        audioElement.value.addEventListener('seeking', () => {
            audioState.isLoading = true;
        });

        audioElement.value.addEventListener('seeked', () => {
            audioState.isLoading = false;
        });

        // Handle network state changes for streaming
        audioElement.value.addEventListener('stalled', () => {
            console.log('Audio streaming stalled');
        });

        audioElement.value.addEventListener('suspend', () => {
            console.log('Audio streaming suspended');
        });
    };

    // Audio control methods optimized for streaming
    const play = async () => {
        if (!audioElement.value) return;

        try {
            // For streaming, we can start playing immediately
            await audioElement.value.play();
        } catch (error) {
            audioState.error = 'Failed to play audio stream';
            console.error('Audio play error:', error);
        }
    };

    const pause = () => {
        if (!audioElement.value) return;
        audioElement.value.pause();
    };

    const togglePlay = () => {
        if (audioState.isPlaying) {
            pause();
        } else {
            play();
        }
    };

    // Enhanced seek function for streaming
    const seek = (time: number) => {
        if (!audioElement.value || !audioState.canSeek) return;

        // Clamp time to valid range
        const clampedTime = Math.max(0, Math.min(time, audioState.duration));
        audioElement.value.currentTime = clampedTime;
    };

    const seekToPercentage = (percentage: number) => {
        if (
            !audioElement.value ||
            audioState.duration === 0 ||
            !audioState.canSeek
        )
            return;

        const time = (percentage / 100) * audioState.duration;
        seek(time);
    };

    const setVolume = (volume: number) => {
        audioState.volume = Math.max(0, Math.min(100, volume));
        if (audioElement.value) {
            audioElement.value.volume = audioState.volume / 100;
        }
    };

    const mute = () => {
        if (audioElement.value) {
            audioElement.value.muted = !audioElement.value.muted;
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

    // Cleanup
    const cleanup = () => {
        if (audioElement.value) {
            audioElement.value.pause();
            audioElement.value.src = '';
            audioElement.value = null;
        }
    };

    // Initialize with episode if provided
    if (episode) {
        onMounted(() => {
            initAudio(episode);
        });
    }

    onUnmounted(() => {
        cleanup();
    });

    return {
        audioState,
        progress,
        formattedCurrentTime,
        formattedDuration,
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
    };
}
