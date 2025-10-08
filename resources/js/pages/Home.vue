<template>
  <div class="home-page min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
              <span class="text-white text-xl font-bold">📻</span>
            </div>
            <div>
              <h1 class="text-xl font-bold text-gray-900">Tech Weekly Podcast</h1>
              <p class="text-sm text-gray-600">by Madhu Sudhan Subedi</p>
            </div>
          </div>
          
          <!-- Stats -->
          <div class="hidden sm:flex items-center space-x-6 text-sm text-gray-600">
            <div class="flex items-center space-x-1">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
              </svg>
              <span>{{ episodes.length }} Episodes</span>
            </div>
            <div class="flex items-center space-x-1">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
              </svg>
              <span>Tech & Programming</span>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Episode List - Takes up 2/3 on large screens -->
        <div class="lg:col-span-2">
          <EpisodeList
            :episodes="episodes"
            :current-episode="currentEpisode"
            :is-playing="audioState.isPlaying"
            :progress="progress"
            :current-time="audioState.currentTime"
            :duration="audioState.duration"
            @episode-select="handleEpisodeSelect"
            @episode-play="handleEpisodePlay"
          />
        </div>

        <!-- Audio Player Sidebar - Takes up 1/3 on large screens -->
        <div class="lg:col-span-1">
          <div class="sticky top-8">
            <!-- Current Episode Info -->
            <div v-if="currentEpisode" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-2">Now Playing</h2>
              <div class="flex items-center space-x-3 mb-4">
                <div class="w-12 h-12 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                  <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                  </svg>
                </div>
                <div class="min-w-0 flex-1">
                  <h3 class="font-medium text-gray-900 truncate">{{ currentEpisode.title }}</h3>
                  <p class="text-sm text-gray-600">{{ formatDate(currentEpisode.published_date) }}</p>
                </div>
              </div>
              

              
              <!-- Episode Meta -->
              <div class="flex items-center justify-between text-xs text-gray-500 border-t border-gray-100 pt-3">
                <span>{{ currentEpisode.duration }}</span>
                <span>{{ currentEpisode.file_size }}</span>
                <span>{{ currentEpisode.format.toUpperCase() }}</span>
              </div>
            </div>

            <!-- Audio Player -->
            <AudioPlayer
              v-if="currentEpisode"
              :episode="currentEpisode"
              :episodes="episodes"
              @episode-change="handleEpisodeChange"
            />

            <!-- Welcome Message when no episode selected -->
            <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-center">
              <div class="w-16 h-16 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                </svg>
              </div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Welcome to Tech Weekly</h3>
              <p class="text-gray-600 mb-4">Select an episode from the list to start listening to insightful discussions about technology and programming.</p>
              <button 
                v-if="episodes.length > 0"
                @click="handleEpisodePlay(episodes[0])"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
              >
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z"/>
                </svg>
                Play Latest Episode
              </button>
            </div>

            <!-- Quick Stats -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Podcast Stats</h3>
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-gray-600">Total Episodes</span>
                  <span class="font-semibold text-gray-900">{{ episodes.length }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-gray-600">Total Duration</span>
                  <span class="font-semibold text-gray-900">{{ totalDuration }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-gray-600">Latest Episode</span>
                  <span class="font-semibold text-gray-900">{{ latestEpisodeDate }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-50 border-t border-gray-200 mt-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center">
          <p class="text-gray-600 mb-2">
            © 2024 Madhu Sudhan Subedi Tech Weekly. All rights reserved.
          </p>
          <p class="text-sm text-gray-500">
            Sharing insights about technology, programming, and software development.
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AudioPlayer from '@/components/AudioPlayer.vue'
import EpisodeList from '@/components/EpisodeList.vue'
import { useAudioPlayer, type Episode } from '@/composables/useAudioPlayer'

interface Props {
  episodes: Episode[]
}

const props = defineProps<Props>()

// Audio player composable
const {
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
  cleanup
} = useAudioPlayer()

// Computed properties
const totalDuration = computed(() => {
  const totalSeconds = props.episodes.reduce((total, episode) => {
    // Parse duration string (e.g., "45:30" or "1:23:45")
    const parts = episode.duration.split(':').map(Number)
    let seconds = 0
    if (parts.length === 2) {
      seconds = parts[0] * 60 + parts[1] // MM:SS
    } else if (parts.length === 3) {
      seconds = parts[0] * 3600 + parts[1] * 60 + parts[2] // HH:MM:SS
    }
    return total + seconds
  }, 0)
  
  const hours = Math.floor(totalSeconds / 3600)
  const minutes = Math.floor((totalSeconds % 3600) / 60)
  
  if (hours > 0) {
    return `${hours}h ${minutes}m`
  }
  return `${minutes}m`
})

const latestEpisodeDate = computed(() => {
  if (props.episodes.length === 0) return 'N/A'
  
  const latest = props.episodes.reduce((latest, episode) => {
    return new Date(episode.published_date) > new Date(latest.published_date) ? episode : latest
  })
  
  return formatDate(latest.published_date)
})

// Current episode state
const currentEpisode = ref<Episode | null>(null)

// Methods
const handleEpisodeSelect = (episode: Episode) => {
  if (currentEpisode.value?.id !== episode.id) {
    currentEpisode.value = episode
    initAudio(episode)
  }
}

const handleEpisodePlay = (episode: Episode) => {
  if (currentEpisode.value?.id === episode.id) {
    if (audioState.isPlaying) {
      pause()
    } else {
      play()
    }
  } else {
    currentEpisode.value = episode
    initAudio(episode)
    // Small delay to ensure audio is initialized before playing
    setTimeout(() => {
      play()
    }, 100)
  }
}

const handleEpisodeChange = (episode: Episode) => {
  currentEpisode.value = episode
  initAudio(episode)
  setTimeout(() => {
    play()
  }, 100)
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}

// Initialize with first episode on mount
onMounted(() => {
  if (props.episodes.length > 0) {
    // Don't auto-play, just initialize the first episode
    currentEpisode.value = props.episodes[0]
    initAudio(props.episodes[0])
  }
})
</script>

<style scoped>
/* Line clamp utility */
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Responsive grid adjustments */
@media (max-width: 1024px) {
  .sticky {
    position: static;
  }
}

/* High contrast support */
@media (prefers-contrast: high) {
  .bg-gradient-to-br {
    background: white;
  }
  
  .bg-gradient-to-r {
    background: #1e40af;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  * {
    transition: none !important;
    animation: none !important;
  }
}

/* Focus management for accessibility */
.focus-within\:ring-2:focus-within {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}
</style>