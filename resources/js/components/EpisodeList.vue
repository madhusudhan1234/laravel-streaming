<template>
  <div class="episode-list">
    <!-- Header -->
    <div class="list-header mb-8 text-center">
      <h1 class="text-2xl font-bold text-gray-900 mb-2 flex items-center justify-center gap-2">
        <span class="text-2xl">📻</span>
        Madhu Sudhan Subedi Tech Weekly
      </h1>
      <p class="text-gray-600">{{ episodes.length }} episodes available</p>
    </div>
    
    <!-- Episodes Grid -->
    <div class="episodes-grid space-y-4">
      <div 
        v-for="episode in episodes" 
        :key="episode.id" 
        class="episode-card bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-lg transition-all duration-300 cursor-pointer transform hover:-translate-y-1"
        :class="{ 'ring-2 ring-blue-500 bg-blue-50 shadow-lg translate-y-0': currentEpisode?.id === episode.id }"
        @click="selectEpisode(episode)"
      >
        <div class="episode-content flex items-center space-x-4">
          <!-- Play Button -->
          <button 
            @click.stop="playEpisode(episode)" 
            class="play-btn w-12 h-12 bg-blue-600 hover:bg-blue-700 rounded-full flex items-center justify-center text-white transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform hover:scale-105 shadow-lg hover:shadow-xl"
            :class="{ 'bg-blue-700 hover:bg-blue-800 scale-105 shadow-xl': currentEpisode?.id === episode.id && isPlaying }"
            :aria-label="`Play ${episode.title}`"
          >
            <svg v-if="currentEpisode?.id === episode.id && isPlaying" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
              <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
            </svg>
            <svg v-else class="w-6 h-6 ml-1" fill="currentColor" viewBox="0 0 24 24">
              <path d="M8 5v14l11-7z"/>
            </svg>
          </button>
          
          <!-- Episode Info -->
          <div class="episode-info flex-1 min-w-0">
            <h3 class="episode-title text-lg font-semibold text-gray-900 mb-1 truncate">
              {{ episode.title }}
            </h3>
            <div class="episode-meta text-sm text-gray-600 flex flex-wrap items-center gap-x-3 gap-y-1">
              <span class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                {{ episode.duration }}
              </span>
              <span class="hidden sm:inline">•</span>
              <span class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M9 11H7v6h2v-6zm4 0h-2v6h2v-6zm4 0h-2v6h2v-6zm2-7H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zM5 20V6h14v14H5z"/>
                </svg>
                {{ formatDate(episode.published_date) }}
              </span>
              <span class="hidden sm:inline">•</span>
              <span class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                </svg>
                {{ episode.file_size }}
              </span>
            </div>

          </div>
          
          <!-- Embed Link -->
          <button 
            @click.stop="showEmbedCode(episode)" 
            class="embed-link p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            :aria-label="`Get embed code for ${episode.title}`"
            title="Get embed code"
          >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
            </svg>
          </button>
        </div>

        <!-- Progress Bar for Current Episode -->
        <div v-if="currentEpisode?.id === episode.id && (progress > 0 || isPlaying)" class="episode-progress mt-3 pt-3 border-t border-gray-200">
          <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
            <span>{{ formatTime(currentTime) }}</span>
            <span>{{ formatTime(duration) }}</span>
          </div>
          <div class="progress-bar bg-gray-200 rounded-full h-1">
            <div 
              class="progress-fill bg-blue-600 h-full rounded-full transition-all duration-300"
              :style="{ width: `${progress}%` }"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="episodes.length === 0" class="empty-state text-center py-12">
      <div class="text-6xl mb-4">🎵</div>
      <h3 class="text-lg font-semibold text-gray-900 mb-2">No episodes available</h3>
      <p class="text-gray-600">Check back later for new episodes!</p>
    </div>

    <!-- Embed Code Modal -->
    <div v-if="showEmbedModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="closeEmbedModal">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4" @click.stop>
        <h3 class="text-lg font-semibold mb-4">Embed Code</h3>
        <p class="text-sm text-gray-600 mb-3">Copy this code to embed "{{ selectedEpisodeForEmbed?.title }}" on your website:</p>
        <textarea 
          ref="embedCodeTextarea"
          :value="embedCode"
          readonly
          class="w-full h-24 p-3 border border-gray-300 rounded-md text-sm font-mono bg-gray-50 resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
        ></textarea>
        <div class="flex justify-end space-x-3 mt-4">
          <button 
            @click="closeEmbedModal"
            class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 rounded"
          >
            Cancel
          </button>
          <button 
            @click="copyEmbedCode"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          >
            Copy Code
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { Episode } from '@/composables/useAudioPlayer'

interface Props {
  episodes: Episode[]
  currentEpisode?: Episode | null
  isPlaying?: boolean
  progress?: number
  currentTime?: number
  duration?: number
}

interface Emits {
  (e: 'episodeSelect', episode: Episode): void
  (e: 'episodePlay', episode: Episode): void
}

const props = withDefaults(defineProps<Props>(), {
  currentEpisode: null,
  isPlaying: false,
  progress: 0,
  currentTime: 0,
  duration: 0
})

const emit = defineEmits<Emits>()

// Embed modal state
const showEmbedModal = ref(false)
const embedCode = ref('')
const selectedEpisodeForEmbed = ref<Episode | null>(null)
const embedCodeTextarea = ref<HTMLTextAreaElement>()

// Methods
const selectEpisode = (episode: Episode) => {
  emit('episodeSelect', episode)
}

const playEpisode = (episode: Episode) => {
  emit('episodePlay', episode)
}

const showEmbedCode = async (episode: Episode) => {
  selectedEpisodeForEmbed.value = episode
  
  try {
    const response = await fetch(`/api/embed/${episode.id}/code`)
    const data = await response.json()
    embedCode.value = data.embedCode
    showEmbedModal.value = true
  } catch (error) {
    console.error('Failed to generate embed code:', error)
  }
}

const closeEmbedModal = () => {
  showEmbedModal.value = false
  selectedEpisodeForEmbed.value = null
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
    month: 'short', 
    day: 'numeric' 
  })
}

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
</script>

<style scoped>
/* Line clamp utility for episode descriptions */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .episode-content {
    flex-direction: column;
    space-x: 0;
    gap: 12px;
    text-align: center;
  }
  
  .episode-info {
    text-align: center;
  }
  
  .episode-meta {
    justify-content: center;
    flex-wrap: wrap;
  }
  
  .episode-meta .hidden {
    display: none;
  }
}

/* Focus states for accessibility */
.episode-card:focus-within {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

/* High contrast support */
@media (prefers-contrast: high) {
  .episode-card {
    border-width: 2px;
  }
  
  .progress-fill {
    background-color: #1e40af;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .episode-card,
  .progress-fill,
  .play-btn,
  .embed-link {
    transition: none;
  }
}
</style>