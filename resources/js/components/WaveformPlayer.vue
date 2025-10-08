<template>
  <div class="waveform-player">
    <!-- Waveform Container -->
    <div 
      ref="waveformContainer"
      @click="handleWaveformClick"
      @mousemove="handleWaveformHover"
      @mouseleave="handleWaveformLeave"
      class="waveform-container relative bg-gradient-to-r from-gray-100 to-gray-50 rounded-lg p-4 cursor-pointer overflow-hidden group"
      :class="{ 'playing': isPlaying }"
    >
      <!-- Background Waveform -->
      <div class="waveform-bars flex items-end justify-between h-16 space-x-1">
        <div 
          v-for="(bar, index) in waveformBars" 
          :key="index"
          class="waveform-bar bg-gray-300 rounded-t-sm transition-all duration-300 ease-out relative overflow-hidden"
          :style="{ 
            height: `${bar.height}%`, 
            minHeight: '4px',
            width: `${barWidth}px`
          }"
        >
          <!-- Progress Fill -->
          <div 
            class="progress-fill absolute bottom-0 left-0 w-full transition-all duration-300 ease-out"
            :class="[
              index <= progressBarIndex ? 'bg-gradient-to-t from-orange-500 to-orange-400' : 'bg-transparent',
              isPlaying && index === progressBarIndex ? 'animate-pulse' : ''
            ]"
            :style="{ 
              height: index < progressBarIndex ? '100%' : 
                      index === progressBarIndex ? `${progressWithinBar}%` : '0%'
            }"
          ></div>
          
          <!-- Hover Effect -->
          <div 
            v-if="hoverIndex >= 0 && index <= hoverIndex"
            class="hover-fill absolute bottom-0 left-0 w-full bg-gradient-to-t from-orange-300 to-orange-200 opacity-50 transition-opacity duration-200"
            :style="{ height: '100%' }"
          ></div>
        </div>
      </div>
      
      <!-- Play Button Overlay -->
      <div 
        v-if="!isPlaying && !isLoading"
        class="play-overlay absolute inset-0 flex items-center justify-center bg-black bg-opacity-20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
      >
        <div class="play-button bg-orange-500 hover:bg-orange-600 text-white rounded-full p-4 shadow-lg transform hover:scale-110 transition-all duration-200">
          <svg class="w-8 h-8 ml-1" fill="currentColor" viewBox="0 0 24 24">
            <path d="M8 5v14l11-7z"/>
          </svg>
        </div>
      </div>
      
      <!-- Loading Overlay -->
      <div 
        v-if="isLoading"
        class="loading-overlay absolute inset-0 flex items-center justify-center bg-white bg-opacity-80"
      >
        <div class="flex space-x-1">
          <div 
            v-for="i in 5" 
            :key="i"
            class="loading-bar bg-orange-500 rounded-sm animate-pulse"
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
        <span class="bg-white bg-opacity-80 px-2 py-1 rounded">{{ formattedCurrentTime }}</span>
        <span class="bg-white bg-opacity-80 px-2 py-1 rounded">{{ formattedDuration }}</span>
      </div>
    </div>
    
    <!-- Waveform Controls -->
    <div class="waveform-controls flex items-center justify-between mt-4">
      <div class="playback-controls flex items-center space-x-3">
        <button 
          @click="togglePlay"
          :disabled="isLoading"
          class="play-pause-btn w-12 h-12 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 disabled:from-gray-400 disabled:to-gray-500 rounded-full flex items-center justify-center text-white transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transform hover:scale-110 shadow-lg hover:shadow-xl"
          :aria-label="isPlaying ? 'Pause episode' : 'Play episode'"
        >
          <svg v-if="isLoading" class="animate-spin w-6 h-6" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else-if="!isPlaying" class="w-6 h-6 ml-1" fill="currentColor" viewBox="0 0 24 24">
            <path d="M8 5v14l11-7z"/>
          </svg>
          <svg v-else class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
            <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
          </svg>
        </button>
        
        <div class="episode-info">
          <h4 class="text-sm font-medium text-gray-900 truncate max-w-xs">{{ episode?.title || 'No episode selected' }}</h4>
          <p class="text-xs text-gray-500">{{ formatDate(episode?.published_date) }}</p>
        </div>
      </div>
      
      <div class="volume-controls flex items-center space-x-2">
        <button @click="toggleMute" class="volume-icon text-gray-600 hover:text-orange-500 transition-colors">
          <svg v-if="volume === 0" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73 4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>
          </svg>
          <svg v-else-if="volume < 50" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M18.5 12c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM5 9v6h4l5 5V4L9 9H5z"/>
          </svg>
          <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
          </svg>
        </button>
        <div class="volume-slider">
          <input 
            type="range" 
            min="0" 
            max="100" 
            :value="volume"
            @input="handleVolumeChange"
            class="w-20 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer volume-range"
            aria-label="Volume control"
          >
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import type { Episode } from '@/composables/useAudioPlayer'

interface Props {
  episode?: Episode | null
  isPlaying?: boolean
  isLoading?: boolean
  currentTime?: number
  duration?: number
  volume?: number
  formattedCurrentTime?: string
  formattedDuration?: string
}

interface Emits {
  (e: 'play'): void
  (e: 'pause'): void
  (e: 'seek', time: number): void
  (e: 'volumeChange', volume: number): void
  (e: 'mute'): void
}

const props = withDefaults(defineProps<Props>(), {
  episode: null,
  isPlaying: false,
  isLoading: false,
  currentTime: 0,
  duration: 0,
  volume: 80,
  formattedCurrentTime: '0:00',
  formattedDuration: '0:00'
})

const emit = defineEmits<Emits>()

// Refs
const waveformContainer = ref<HTMLElement>()
const hoverIndex = ref(-1)

// Waveform configuration
const totalBars = 100
const barWidth = computed(() => {
  if (!waveformContainer.value) return 3
  const containerWidth = waveformContainer.value.offsetWidth - 32 // Account for padding
  return Math.max(2, Math.floor((containerWidth - (totalBars - 1) * 4) / totalBars)) // 4px gap between bars
})

// Generate waveform bars with realistic audio-like pattern
const waveformBars = computed(() => {
  const bars = []
  for (let i = 0; i < totalBars; i++) {
    // Create a more realistic waveform pattern
    const baseHeight = 20 + Math.sin(i * 0.1) * 15
    const variation = Math.sin(i * 0.3) * 20 + Math.cos(i * 0.2) * 15
    const randomness = (Math.random() - 0.5) * 30
    const height = Math.max(10, Math.min(100, baseHeight + variation + randomness))
    
    bars.push({ height })
  }
  return bars
})

// Progress calculations
const progress = computed(() => {
  if (props.duration === 0) return 0
  return (props.currentTime / props.duration) * 100
})

const progressBarIndex = computed(() => {
  return Math.floor((progress.value / 100) * totalBars)
})

const progressWithinBar = computed(() => {
  const exactProgress = (progress.value / 100) * totalBars
  return (exactProgress - Math.floor(exactProgress)) * 100
})

// Methods
const togglePlay = () => {
  if (props.isPlaying) {
    emit('pause')
  } else {
    emit('play')
  }
}

const handleWaveformClick = (event: MouseEvent) => {
  if (!waveformContainer.value || props.duration === 0) return
  
  const rect = waveformContainer.value.getBoundingClientRect()
  const clickX = event.clientX - rect.left
  const percentage = (clickX / rect.width) * 100
  const seekTime = (percentage / 100) * props.duration
  
  emit('seek', Math.max(0, Math.min(props.duration, seekTime)))
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
  emit('volumeChange', parseInt(target.value))
}

const toggleMute = () => {
  emit('mute')
}

const formatDate = (dateString?: string): string => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
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
  animation: waveform-pulse 0.5s ease-in-out infinite alternate;
}

@keyframes waveform-pulse {
  0% { opacity: 0.8; }
  100% { opacity: 1; }
}

/* Loading animation */
.loading-bar {
  animation: loading-bounce 1.5s ease-in-out infinite;
}

@keyframes loading-bounce {
  0%, 100% { transform: scaleY(0.5); }
  50% { transform: scaleY(1); }
}

/* Hover effects */
.waveform-container:hover .waveform-bar {
  transform: scaleY(1.05);
}

.waveform-container:hover .play-overlay {
  backdrop-filter: blur(2px);
}

/* Focus states for accessibility */
.play-pause-btn:focus,
.volume-icon:focus {
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
  .loading-bar {
    animation: none;
    transition: none;
  }
}
</style>