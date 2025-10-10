import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';

export interface Episode {
    id: number;
    title: string;
    filename: string;
    duration: string;
    file_size: string;
    format: string;
    published_date: string;
    description: string;
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
}

export function useAudioPlayer(episode?: Episode) {
    const audioElement = ref<HTMLAudioElement | null>(null);

    const audioState = reactive<AudioState>({
        isPlaying: false,
        currentTime: 0,
        duration: 0,
        volume: 80,
        isLoading: false,
        error: null,
        buffered: 0,
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

    // Initialize audio element
    const initAudio = (episodeData: Episode) => {
        if (audioElement.value) {
            audioElement.value.pause();
            audioElement.value.src = '';
        }

        audioElement.value = new Audio(episodeData.url);

        // Configure for progressive streaming like SoundCloud
        audioElement.value.preload = 'none'; // Only load when user clicks play
        audioElement.value.crossOrigin = 'anonymous';

        setupAudioListeners();
        audioElement.value.volume = audioState.volume / 100;
    };

    // Setup event listeners
    const setupAudioListeners = () => {
        if (!audioElement.value) return;

        audioElement.value.addEventListener('loadstart', () => {
            audioState.isLoading = true;
            audioState.error = null;
        });

        audioElement.value.addEventListener('loadedmetadata', () => {
            audioState.duration = audioElement.value?.duration || 0;
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

        audioElement.value.addEventListener('error', () => {
            audioState.error = 'Failed to load audio file';
            audioState.isLoading = false;
            audioState.isPlaying = false;
        });

        audioElement.value.addEventListener('waiting', () => {
            audioState.isLoading = true;
        });

        audioElement.value.addEventListener('canplay', () => {
            audioState.isLoading = false;
        });
    };

    // Audio control methods
    const play = async () => {
        if (!audioElement.value) return;

        try {
            await audioElement.value.play();
        } catch (error) {
            audioState.error = 'Failed to play audio';
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

    const seek = (time: number) => {
        if (!audioElement.value) return;
        audioElement.value.currentTime = time;
    };

    const seekToPercentage = (percentage: number) => {
        if (!audioElement.value || audioState.duration === 0) return;
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
        formatTime,
        cleanup,
    };
}
