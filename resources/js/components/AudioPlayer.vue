<template>
  <div class="audio-player bg-white rounded-lg shadow-md p-6 border border-gray-200 max-w-2xl mx-auto">
    <!-- Episode Header -->
    <div class="episode-header mb-4">
      <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ episode?.title || 'No episode selected' }}</h3>
      <p v-if="episode" class="text-sm text-gray-600">{{ formatDate(episode.published_date) }}</p>
    </div>
    
    <!-- Audio Controls -->
    <div class="audio-controls flex items-center space-x-4 mb-4">
      <button 
        @click="togglePlay"
        :disabled="!episode || audioState.isLoading"
        class="play-pause-btn w-12 h-12 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 disabled:from-gray-400 disabled:to-gray-500 rounded-full flex items-center justify-center text-white transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transform hover:scale-110 shadow-lg hover:shadow-xl"
        :aria-label="audioState.isPlaying ? 'Pause episode' : 'Play episode'"
        :aria-pressed="audioState.isPlaying"
      >
        <svg v-if="audioState.isLoading" class="animate-spin w-6 h-6" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <svg v-else-if="!audioState.isPlaying" class="w-6 h-6 ml-1" fill="currentColor" viewBox="0 0 24 24">
          <path d="M8 5v14l11-7z"/>
        </svg>
        <svg v-else class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
          <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
        </svg>
      </button>
      
      <button 
        @click="previousEpisode"
        :disabled="!hasPrevious"
        class="previous-btn w-10 h-10 bg-gray-100 hover:bg-gray-200 disabled:bg-gray-50 disabled:text-gray-300 rounded-full flex items-center justify-center text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
        aria-label="Previous episode"
      >
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/>
        </svg>
      </button>
      
      <button 
        @click="nextEpisode"
        :disabled="!hasNext"
        class="next-btn w-10 h-10 bg-gray-100 hover:bg-gray-200 disabled:bg-gray-50 disabled:text-gray-300 rounded-full flex items-center justify-center text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
        aria-label="Next episode"
      >
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/>
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
        class="waveform-container relative bg-gradient-to-r from-gray-100 to-gray-50 rounded-lg p-4 cursor-pointer overflow-hidden group"
        :class="{ 'playing': audioState.isPlaying }"
        role="slider"
        :aria-valuenow="audioState.currentTime"
        :aria-valuemin="0"
        :aria-valuemax="audioState.duration"
        aria-label="Audio waveform - click to seek"
      >
        <!-- Background Waveform -->
        <div class="waveform-bars flex items-end justify-between h-20 space-x-1">
          <div 
            v-for="(bar, index) in waveformBars" 
            :key="index"
            class="waveform-bar bg-gray-400 rounded-t-sm transition-all duration-300 ease-out relative overflow-hidden hover:bg-gray-500"
            :style="{ 
              height: `${bar.height}%`, 
              minHeight: '8px',
              width: `${barWidth}px`
            }"
          >
            <!-- Progress Fill -->
            <div 
              class="progress-fill absolute bottom-0 left-0 w-full transition-all duration-300 ease-out"
              :class="[
                index <= progressBarIndex ? 'bg-gradient-to-t from-orange-500 to-orange-400 shadow-sm' : 'bg-transparent',
                audioState.isPlaying && index === progressBarIndex ? 'animate-pulse' : ''
              ]"
              :style="{ 
                height: index < progressBarIndex ? '100%' : 
                        index === progressBarIndex ? `${progressWithinBar}%` : '0%'
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
          class="play-overlay absolute inset-0 flex items-center justify-center bg-black bg-opacity-20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
        >
          <div class="play-button bg-orange-500 hover:bg-orange-600 text-white rounded-full p-3 shadow-lg transform hover:scale-110 transition-all duration-200">
            <svg class="w-6 h-6 ml-1" fill="currentColor" viewBox="0 0 24 24">
              <path d="M8 5v14l11-7z"/>
            </svg>
          </div>
        </div>
        
        <!-- Loading Overlay -->
        <div 
          v-if="audioState.isLoading"
          class="loading-overlay absolute inset-0 flex items-center justify-center bg-white bg-opacity-80 rounded-lg"
        >
          <div class="flex space-x-1">
            <div 
              v-for="i in 5" 
              :key="i"
              class="loading-bar bg-orange-500 rounded-sm animate-bounce"
              :style="{ 
                width: '4px', 
                height: `${20 + (i % 3) * 10}px`,
                animationDelay: `${i * 0.1}s`
              }"
            ></div>
          </div>
        </div>
        
        <!-- Time Display -->
        <div class="time-display absolute bottom-1 left-4 right-4 flex justify-between text-xs text-gray-600">
          <span class="bg-white bg-opacity-90 px-2 py-1 rounded shadow-sm">{{ formattedCurrentTime }}</span>
          <span class="bg-white bg-opacity-90 px-2 py-1 rounded shadow-sm">{{ formattedDuration }}</span>
        </div>
      </div>
    </div>
    
    <!-- Volume Control -->
    <div class="volume-section flex items-center space-x-3 mb-6">
      <button @click="mute" class="volume-icon text-gray-600 hover:text-gray-800 transition-colors">
        <svg v-if="audioState.volume === 0" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 = 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>
        </svg>
        <svg v-else-if="audioState.volume < 50" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M18.5 12c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM5 9v6h4l5 5V4L9 9H5z"/>
        </svg>
        <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
        </svg>
      </button>
      <div class="volume-slider flex-1 max-w-24">
        <input 
          type="range" 
          min="0" 
          max="100" 
          :value="audioState.volume"
          @input="handleVolumeChange"
          class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer volume-range"
          aria-label="Volume control"
        >
      </div>
      <span class="text-xs text-gray-500 w-8 text-right">{{ Math.round(audioState.volume) }}</span>
    </div>
    
    <!-- Embed Code Button -->
    <div class="embed-section pt-4 border-t border-gray-200">
      <button 
        @click="showEmbedCode"
        :disabled="!episode"
        class="embed-btn bg-gradient-to-r from-orange-100 to-orange-50 hover:from-orange-200 hover:to-orange-100 disabled:bg-gray-50 disabled:text-gray-400 text-orange-700 px-4 py-2 rounded-md text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 border border-orange-200"
      >
        Get Embed Code
      </button>
    </div>

    <!-- Embed Code Modal -->
    <div v-if="showEmbedModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="closeEmbedModal">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4" @click.stop>
        <h3 class="text-lg font-semibold mb-4">Embed Code</h3>
        <p class="text-sm text-gray-600 mb-3">Copy this code to embed the audio player on your website:</p>
        <textarea 
          ref="embedCodeTextarea"
          :value="embedCode"
          readonly
          class="w-full h-24 p-3 border border-gray-300 rounded-md text-sm font-mono bg-gray-50 resize-none"
        ></textarea>
        <div class="flex justify-end space-x-3 mt-4">
          <button 
            @click="closeEmbedModal"
            class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"
          >
            Cancel
          </button>
          <button 
            @click="copyEmbedCode"
            class="px-4 py-2 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-md transition-colors shadow-md hover:shadow-lg"
          >
            Copy Code
          </button>
        </div>
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="audioState.error" class="error-message mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
      <p class="text-sm text-red-600">{{ audioState.error }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { useAudioStreaming, type Episode } from '@/composables/useAudioStreaming'

interface Props {
  episode?: Episode | null
  episodes?: Episode[]
}

interface Emits {
  (e: 'episodeChange', episode: Episode): void
}

const props = withDefaults(defineProps<Props>(), {
  episode: null,
  episodes: () => []
})

const emit = defineEmits<Emits>()

// Audio streaming composable
const {
  audioState,
  progress,
  formattedCurrentTime,
  formattedDuration,
  initAudio,
  togglePlay,
  seekToPercentage,
  setVolume,
  mute
} = useAudioStreaming()

// Embed modal state
const showEmbedModal = ref(false)
const embedCode = ref('')
const embedCodeTextarea = ref<HTMLTextAreaElement>()

// Waveform refs and state
const waveformContainer = ref<HTMLElement>()
const hoverIndex = ref(-1)

// Waveform configuration
const totalBars = 120
const barWidth = computed(() => {
  if (!waveformContainer.value) return 3
  const containerWidth = waveformContainer.value.offsetWidth - 32 // Account for padding
  return Math.max(2, Math.floor((containerWidth - (totalBars - 1) * 4) / totalBars)) // 4px gap between bars
})

// Generate waveform bars with realistic audio-like pattern
const waveformBars = computed(() => {
  const bars = []
  for (let i = 0; i < totalBars; i++) {
    // Create a more realistic waveform pattern similar to SoundCloud
    const baseHeight = 30 + Math.sin(i * 0.1) * 25
    const variation = Math.sin(i * 0.3) * 30 + Math.cos(i * 0.2) * 25
    const randomness = (Math.random() - 0.5) * 40
    const height = Math.max(20, Math.min(100, baseHeight + variation + randomness))
    
    bars.push({ height })
  }
  return bars
})

// Progress calculations for waveform
const progressBarIndex = computed(() => {
  return Math.floor((progress.value / 100) * totalBars)
})

const progressWithinBar = computed(() => {
  const exactProgress = (progress.value / 100) * totalBars
  return (exactProgress - Math.floor(exactProgress)) * 100
})

// Episode navigation
const currentEpisodeIndex = computed(() => {
  if (!props.episode || !props.episodes.length) return -1
  return props.episodes.findIndex(ep => ep.id === props.episode?.id)
})

const hasPrevious = computed(() => currentEpisodeIndex.value > 0)
const hasNext = computed(() => currentEpisodeIndex.value < props.episodes.length - 1 && currentEpisodeIndex.value !== -1)

// Watch for episode changes
watch(() => props.episode, (newEpisode) => {
  if (newEpisode) {
    initAudio(newEpisode)
  }
}, { immediate: true })

// Methods
const handleWaveformClick = (event: MouseEvent) => {
  if (!waveformContainer.value || audioState.duration === 0) return
  
  const rect = waveformContainer.value.getBoundingClientRect()
  const clickX = event.clientX - rect.left
  const percentage = (clickX / rect.width) * 100
  seekToPercentage(Math.max(0, Math.min(100, percentage)))
}

const handleWaveformHover = (event: MouseEvent) => {
  if (!waveformContainer.value) return
  
  const rect = waveformContainer.value.getBoundingClientRect()
  const hoverX = event.clientX - rect.left
  const percentage = (hoverX / rect.width) * 100
  hoverIndex.value = Math.floor((percentage / 100) * totalBars)
}

const handleWaveformLeave = () => {
  hoverIndex.value = -1
}

const handleVolumeChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  setVolume(parseInt(target.value))
}

const previousEpisode = () => {
  if (hasPrevious.value && props.episodes.length > 0) {
    const prevEpisode = props.episodes[currentEpisodeIndex.value - 1]
    emit('episodeChange', prevEpisode)
  }
}

const nextEpisode = () => {
  if (hasNext.value && props.episodes.length > 0) {
    const nextEpisode = props.episodes[currentEpisodeIndex.value + 1]
    emit('episodeChange', nextEpisode)
  }
}

const showEmbedCode = async () => {
  if (!props.episode) return
  
  try {
    const response = await fetch(`/api/embed/${props.episode.id}/code`)
    const data = await response.json()
    embedCode.value = data.embedCode
    showEmbedModal.value = true
  } catch (error) {
    console.error('Failed to generate embed code:', error)
  }
}

const closeEmbedModal = () => {
  showEmbedModal.value = false
}

const copyEmbedCode = async () => {
  if (embedCodeTextarea.value) {
    try {
      await navigator.clipboard.writeText(embedCode.value)
      closeEmbedModal()
    } catch (error) {
      // Fallback for older browsers
      embedCodeTextarea.value.select()
      document.execCommand('copy')
      closeEmbedModal()
    }
  }
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}

// Resize handler for responsive bar width
const handleResize = () => {
  // Force reactivity update for barWidth
  if (waveformContainer.value) {
    waveformContainer.value.style.width = waveformContainer.value.style.width
  }
}

onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<style scoped>
/* Custom styles for volume slider */
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

/* Waveform animations */
.waveform-container.playing .waveform-bar .progress-fill {
  animation: waveform-pulse 0.8s ease-in-out infinite alternate;
}

@keyframes waveform-pulse {
  0% { opacity: 0.8; transform: scaleY(0.95); }
  100% { opacity: 1; transform: scaleY(1.05); }
}

/* Loading animation */
.loading-bar {
  animation: loading-bounce 1.5s ease-in-out infinite;
}

@keyframes loading-bounce {
  0%, 100% { transform: scaleY(0.5); opacity: 0.7; }
  50% { transform: scaleY(1.2); opacity: 1; }
}

/* Hover effects */
.waveform-container:hover .waveform-bar {
  transform: scaleY(1.02);
}

.waveform-container:hover .play-overlay {
  backdrop-filter: blur(2px);
}

/* Focus states for accessibility */
.play-pause-btn:focus,
.previous-btn:focus,
.next-btn:focus,
.embed-btn:focus {
  outline: none;
  ring: 2px;
  ring-color: #f97316;
  ring-offset: 2px;
}

.waveform-container:focus {
  outline: none;
  ring: 2px;
  ring-color: #f97316;
  ring-offset: 2px;
}

/* Reduced motion support */
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