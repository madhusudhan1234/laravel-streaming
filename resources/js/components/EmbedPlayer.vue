<template>
  <div class="embed-player bg-white rounded-lg shadow-sm border border-gray-200 p-4 max-w-md mx-auto">
    <!-- Episode Info -->
    <div class="episode-info mb-4">
      <h3 class="text-lg font-semibold text-gray-900 mb-1 truncate">
        {{ episode.title }}
      </h3>
      <p class="text-sm text-gray-600 flex items-center gap-2">
        <span>{{ episode.duration }}</span>
        <span>•</span>
        <span>{{ formatDate(episode.published_date) }}</span>
      </p>
    </div>

    <!-- Audio Controls -->
    <div class="audio-controls">
      <!-- Progress Bar -->
      <div class="progress-container mb-3">
        <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
          <span>{{ formatTime(currentTime) }}</span>
          <span>{{ formatTime(duration) }}</span>
        </div>
        <div 
          class="progress-bar bg-gray-200 rounded-full h-2 cursor-pointer relative"
          @click="handleProgressClick"
          ref="progressBar"
        >
          <!-- Buffered Progress -->
          <div 
            class="buffered-progress bg-gray-300 h-full rounded-full absolute"
            :style="{ width: `${bufferedProgress}%` }"
          ></div>
          <!-- Current Progress -->
          <div 
            class="current-progress bg-blue-600 h-full rounded-full relative"
            :style="{ width: `${progress}%` }"
          >
            <div class="progress-thumb w-3 h-3 bg-blue-600 rounded-full absolute right-0 top-1/2 transform translate-x-1/2 -translate-y-1/2 shadow-sm"></div>
          </div>
        </div>
      </div>

      <!-- Control Buttons -->
      <div class="controls-row flex items-center justify-between">
        <!-- Play/Pause Button -->
        <button 
          @click="togglePlayPause"
          :disabled="loading"
          class="play-pause-btn w-12 h-12 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 rounded-full flex items-center justify-center text-white transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          :aria-label="isPlaying ? 'Pause' : 'Play'"
        >
          <div v-if="loading" class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
          <svg v-else-if="isPlaying" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
            <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
          </svg>
          <svg v-else class="w-6 h-6 ml-1" fill="currentColor" viewBox="0 0 24 24">
            <path d="M8 5v14l11-7z"/>
          </svg>
        </button>

        <!-- Volume Control -->
        <div class="volume-control flex items-center space-x-2">
          <button 
            @click="toggleMute"
            class="volume-btn p-2 text-gray-600 hover:text-blue-600 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            :aria-label="isMuted ? 'Unmute' : 'Mute'"
          >
            <svg v-if="isMuted || volume === 0" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>
            </svg>
            <svg v-else-if="volume < 0.5" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M18.5 12c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM5 9v6h4l5 5V4L9 9H5z"/>
            </svg>
            <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
            </svg>
          </button>
          <input 
            type="range"
            min="0"
            max="1"
            step="0.1"
            :value="volume"
            @input="handleVolumeChange"
            class="volume-slider w-16 h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
        </div>

        <!-- Link to Full Site -->
        <a 
          :href="fullSiteUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="external-link p-2 text-gray-600 hover:text-blue-600 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          title="Open in full player"
          aria-label="Open in full player"
        >
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>
          </svg>
        </a>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="error-message bg-red-50 border border-red-200 rounded-md p-3 mt-3">
      <div class="flex items-center">
        <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
        </svg>
        <span class="text-sm text-red-700">{{ error }}</span>
      </div>
    </div>

    <!-- Powered By -->
    <div class="powered-by text-center mt-3 pt-3 border-t border-gray-100">
      <a 
        :href="fullSiteUrl"
        target="_blank"
        rel="noopener noreferrer"
        class="text-xs text-gray-500 hover:text-blue-600 transition-colors"
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
import { ref, computed, onMounted, onUnmounted } from 'vue'

interface Episode {
  id: number
  title: string
  filename: string
  url: string
  duration: string
  published_date: string
  description?: string
}

interface Props {
  episode: Episode
  autoplay?: boolean
  fullSiteUrl?: string
}

const props = withDefaults(defineProps<Props>(), {
  autoplay: false,
  fullSiteUrl: '/'
})

// Audio state
const audioElement = ref<HTMLAudioElement>()
const progressBar = ref<HTMLDivElement>()
const isPlaying = ref(false)
const currentTime = ref(0)
const duration = ref(0)
const volume = ref(1)
const isMuted = ref(false)
const loading = ref(true)
const error = ref('')
const buffered = ref<TimeRanges | null>(null)

// Computed properties
const progress = computed(() => {
  if (duration.value === 0) return 0
  return (currentTime.value / duration.value) * 100
})

const bufferedProgress = computed(() => {
  if (!buffered.value || duration.value === 0) return 0
  
  let bufferedEnd = 0
  for (let i = 0; i < buffered.value.length; i++) {
    if (buffered.value.start(i) <= currentTime.value && buffered.value.end(i) > currentTime.value) {
      bufferedEnd = buffered.value.end(i)
      break
    }
  }
  
  return (bufferedEnd / duration.value) * 100
})

// Methods
const togglePlayPause = async () => {
  if (!audioElement.value) return
  
  try {
    if (isPlaying.value) {
      audioElement.value.pause()
      isPlaying.value = false
    } else {
      await audioElement.value.play()
      isPlaying.value = true
    }
  } catch (err) {
    error.value = 'Failed to play audio. Please try again.'
    console.error('Audio play error:', err)
  }
}

const toggleMute = () => {
  if (!audioElement.value) return
  
  if (isMuted.value) {
    audioElement.value.volume = volume.value
    isMuted.value = false
  } else {
    audioElement.value.volume = 0
    isMuted.value = true
  }
}

const handleVolumeChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  const newVolume = parseFloat(target.value)
  volume.value = newVolume
  
  if (audioElement.value) {
    audioElement.value.volume = newVolume
    isMuted.value = newVolume === 0
  }
}

const handleProgressClick = (event: MouseEvent) => {
  if (!progressBar.value || !audioElement.value || duration.value === 0) return
  
  const rect = progressBar.value.getBoundingClientRect()
  const clickX = event.clientX - rect.left
  const percentage = clickX / rect.width
  const newTime = percentage * duration.value
  
  audioElement.value.currentTime = newTime
  currentTime.value = newTime
}

// Audio event handlers
const handleLoadedMetadata = () => {
  if (audioElement.value) {
    duration.value = audioElement.value.duration
  }
}

const handleTimeUpdate = () => {
  if (audioElement.value) {
    currentTime.value = audioElement.value.currentTime
  }
}

const handleProgress = () => {
  if (audioElement.value) {
    buffered.value = audioElement.value.buffered
  }
}

const handleEnded = () => {
  isPlaying.value = false
  currentTime.value = 0
}

const handleError = () => {
  error.value = 'Failed to load audio. Please check your connection.'
  loading.value = false
}

const handleLoadStart = () => {
  loading.value = true
  error.value = ''
}

const handleCanPlay = () => {
  loading.value = false
  
  // Auto-play if enabled
  if (props.autoplay && audioElement.value) {
    audioElement.value.play().then(() => {
      isPlaying.value = true
    }).catch(() => {
      // Auto-play failed (browser policy), ignore silently
    })
  }
}

// Utility functions
const formatTime = (seconds: number): string => {
  if (isNaN(seconds) || seconds < 0) return '0:00'
  
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = Math.floor(seconds % 60)
  
  if (hours > 0) {
    return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
  }
  
  return `${minutes}:${secs.toString().padStart(2, '0')}`
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}

// Lifecycle
onMounted(() => {
  if (audioElement.value) {
    audioElement.value.volume = volume.value
  }
})

onUnmounted(() => {
  if (audioElement.value) {
    audioElement.value.pause()
  }
})
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