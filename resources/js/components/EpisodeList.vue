<template>
  <div class="episode-list">
    <!-- Header -->
    <div class="list-header mb-8 text-center">
      <h1 class="text-3xl font-bold text-gray-900 mb-2 flex items-center justify-center gap-3">
        <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg flex items-center justify-center">
          <span class="text-white text-xl">📻</span>
        </div>
        Madhu Sudhan Subedi Tech Weekly
      </h1>
      <p class="text-gray-600 text-lg">{{ episodes.length }} episodes available</p>
    </div>
    
    <!-- Episodes Grid -->
    <div class="episodes-grid space-y-6">
      <div 
        v-for="episode in sortedEpisodes" 
        :key="episode.id" 
        class="episode-card bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1"
        :class="{ 
          'ring-2 ring-orange-400 shadow-2xl translate-y-0 bg-gradient-to-r from-orange-50 to-white': currentEpisode?.id === episode.id,
          'hover:ring-1 hover:ring-orange-200': currentEpisode?.id !== episode.id
        }"
      >
        <!-- Main Content Area -->
        <div class="episode-content p-6">
          <div class="flex items-start space-x-6">
            <!-- Large Play Button -->
            <button 
              @click.stop="playEpisode(episode)" 
              class="play-btn w-16 h-16 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 rounded-full flex items-center justify-center text-white transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-orange-200 transform hover:scale-105 shadow-xl hover:shadow-2xl group"
              :class="{ 
                'from-orange-600 to-orange-700 scale-105 shadow-2xl animate-pulse': currentEpisode?.id === episode.id && isPlaying,
                'from-gray-400 to-gray-500': !episode.url
              }"
              :disabled="!episode.url"
              :aria-label="`Play ${episode.title}`"
            >
              <svg v-if="currentEpisode?.id === episode.id && isPlaying" class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
              </svg>
              <svg v-else class="w-8 h-8 ml-1 group-hover:ml-2 transition-all duration-200" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z"/>
              </svg>
            </button>
            
            <!-- Episode Info -->
            <div class="episode-info flex-1 min-w-0">
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1 min-w-0">
                  <h3 class="episode-title text-xl font-bold text-gray-900 mb-2 line-clamp-2 hover:text-orange-600 transition-colors cursor-pointer"
                      @click="selectEpisode(episode)">
                    {{ episode.title }}
                  </h3>
                  <div class="episode-meta text-sm text-gray-500 flex flex-wrap items-center gap-x-4 gap-y-2">
                    <span class="flex items-center gap-1.5 bg-gray-100 px-3 py-1 rounded-full">
                      <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                      </svg>
                      {{ episode.duration }}
                    </span>
                    <span class="flex items-center gap-1.5 bg-gray-100 px-3 py-1 rounded-full">
                      <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 11H7v6h2v-6zm4 0h-2v6h2v-6zm4 0h-2v6h2v-6zm2-7H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zM5 20V6h14v14H5z"/>
                      </svg>
                      {{ formatDate(episode.published_date) }}
                    </span>
                  </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex items-center space-x-2 ml-4">
                  <button 
                    @click.stop="showEmbedCode(episode)" 
                    class="action-btn p-3 text-gray-400 hover:text-orange-500 hover:bg-orange-50 rounded-full transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-200 group"
                    :aria-label="`Get embed code for ${episode.title}`"
                    title="Get embed code"
                  >
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                    </svg>
                  </button>
                  <button 
                    @click.stop="shareEpisode(episode)" 
                    class="action-btn p-3 text-gray-400 hover:text-orange-500 hover:bg-orange-50 rounded-full transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-200 group"
                    :aria-label="`Share ${episode.title}`"
                    title="Share episode"
                  >
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.50-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Waveform Section -->
        <div class="waveform-section px-6 pb-6">
          <div 
            class="waveform-container relative bg-gray-50 rounded-lg p-4 cursor-pointer hover:bg-gray-100 transition-colors group"
            @click="handleWaveformClick(episode, $event)"
            @mousemove="handleWaveformHover(episode, $event)"
            @mouseleave="clearWaveformHover(episode)"
          >
            <!-- Waveform Bars -->
            <div class="waveform-bars flex items-end justify-between h-12 gap-0.5">
              <div 
                v-for="(bar, index) in getWaveformBars(episode)" 
                :key="index"
                class="waveform-bar bg-gray-300 rounded-sm transition-all duration-200 hover:bg-orange-300"
                :class="{
                  'bg-gradient-to-t from-orange-500 to-orange-400': isBarActive(episode, index),
                  'bg-gradient-to-t from-orange-600 to-orange-500 animate-pulse': isBarPlaying(episode, index),
                  'bg-orange-200': isBarHovered(episode, index)
                }"
                :style="{ 
                  height: `${bar.height}%`, 
                  width: '2px',
                  minHeight: '6px'
                }"
                :data-index="index"
              ></div>
            </div>
            
            <!-- Play Overlay - Only show when not playing and on hover -->
            <div 
              v-if="currentEpisode?.id !== episode.id"
              class="play-overlay absolute inset-0 bg-transparent rounded-lg flex items-center justify-center transition-all duration-300 pointer-events-none"
            >
              <div class="play-button w-12 h-12 bg-white bg-opacity-0 group-hover:bg-opacity-90 rounded-full flex items-center justify-center transform scale-0 group-hover:scale-100 transition-all duration-300 shadow-lg">
                <svg class="w-6 h-6 text-orange-500 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z"/>
                </svg>
              </div>
            </div>
            
            <!-- Loading Overlay -->
            <div 
              v-if="currentEpisode?.id === episode.id && isLoading"
              class="loading-overlay absolute inset-0 bg-white bg-opacity-80 rounded-lg flex items-center justify-center"
            >
              <div class="flex items-end space-x-1">
                <div v-for="i in 5" :key="i" class="loading-bar w-1 bg-orange-500 rounded-sm animate-bounce" 
                     :style="{ height: '20px', animationDelay: `${i * 0.1}s` }"></div>
              </div>
            </div>
            
            <!-- Time Display -->
            <div class="time-display flex items-center justify-between mt-3 text-xs text-gray-500">
              <span>{{ currentEpisode?.id === episode.id ? formatTime(currentTime) : '0:00' }}</span>
              <span>{{ episode.duration }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="episodes.length === 0" class="empty-state text-center py-16">
      <div class="w-24 h-24 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center mx-auto mb-6">
        <span class="text-4xl">🎵</span>
      </div>
      <h3 class="text-2xl font-bold text-gray-900 mb-3">No episodes available</h3>
      <p class="text-gray-600 text-lg">Check back later for new episodes!</p>
    </div>

    <!-- Embed Code Modal -->
    <div v-if="showEmbedModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click="closeEmbedModal">
      <div class="bg-white rounded-xl p-8 max-w-lg w-full shadow-2xl" @click.stop>
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-2xl font-bold text-gray-900">Embed Code</h3>
          <button @click="closeEmbedModal" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
              <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
          </button>
        </div>
        <p class="text-gray-600 mb-4">Copy this code to embed "<strong>{{ selectedEpisodeForEmbed?.title }}</strong>" on your website:</p>
        <textarea 
          ref="embedCodeTextarea"
          :value="embedCode"
          readonly
          class="w-full h-32 p-4 border border-gray-300 rounded-lg text-sm font-mono bg-gray-50 resize-none focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
        ></textarea>
        <div class="flex justify-end space-x-3 mt-6">
          <button 
            @click="closeEmbedModal"
            class="px-6 py-3 text-gray-600 hover:text-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 rounded-lg font-medium"
          >
            Cancel
          </button>
          <button 
            @click="copyEmbedCode"
            class="px-6 py-3 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 font-medium shadow-lg hover:shadow-xl"
          >
            Copy Code
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import type { Episode } from '@/composables/useAudioPlayer'

interface Props {
  episodes: Episode[]
  currentEpisode?: Episode | null
  isPlaying?: boolean
  progress?: number
  currentTime?: number
  duration?: number
  isLoading?: boolean
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
  duration: 0,
  isLoading: false
})

const emit = defineEmits<Emits>()

// Sort episodes by latest first (descending order by ID)
const sortedEpisodes = computed(() => {
  return [...props.episodes].sort((a, b) => b.id - a.id)
})

// Embed modal state
const showEmbedModal = ref(false)
const embedCode = ref('')
const selectedEpisodeForEmbed = ref<Episode | null>(null)
const embedCodeTextarea = ref<HTMLTextAreaElement>()

// Waveform state
const waveformData = reactive<Record<number, { bars: Array<{ height: number }>, hoverIndex: number }>>({})

// Initialize waveform data for each episode
const initializeWaveform = (episode: Episode) => {
  if (!waveformData[episode.id]) {
    const bars = []
    const totalBars = 60 // Increase number of bars for better visual density
    
    // Generate realistic waveform pattern similar to SoundCloud
    for (let i = 0; i < totalBars; i++) {
      // Create more varied and realistic waveform pattern
      const baseHeight = 25 + Math.sin(i * 0.15) * 20
      const variation = Math.sin(i * 0.4) * 25 + Math.cos(i * 0.3) * 20
      const randomness = (Math.random() - 0.5) * 30
      const height = Math.max(15, Math.min(85, baseHeight + variation + randomness))
      
      bars.push({ height })
    }
    
    waveformData[episode.id] = {
      bars,
      hoverIndex: -1
    }
  }
}

// Get waveform bars for an episode
const getWaveformBars = (episode: Episode) => {
  initializeWaveform(episode)
  return waveformData[episode.id].bars
}

// Check if a bar is active (played)
const isBarActive = (episode: Episode, index: number) => {
  if (props.currentEpisode?.id !== episode.id || !props.progress) return false
  
  const totalBars = getWaveformBars(episode).length
  const activeBarIndex = Math.floor((props.progress / 100) * totalBars)
  return index <= activeBarIndex
}

// Check if a bar is currently playing
const isBarPlaying = (episode: Episode, index: number) => {
  if (props.currentEpisode?.id !== episode.id || !props.isPlaying) return false
  
  const totalBars = getWaveformBars(episode).length
  const activeBarIndex = Math.floor((props.progress / 100) * totalBars)
  return index === activeBarIndex
}

// Check if a bar is hovered
const isBarHovered = (episode: Episode, index: number) => {
  if (!waveformData[episode.id]) return false
  const hoverIndex = waveformData[episode.id].hoverIndex
  return hoverIndex >= 0 && index <= hoverIndex && !isBarActive(episode, index)
}

// Handle waveform click for seeking
const handleWaveformClick = (episode: Episode, event: MouseEvent) => {
  if (props.currentEpisode?.id !== episode.id) {
    // If not current episode, play it first
    emit('episodePlay', episode)
    return
  }
  
  const container = event.currentTarget as HTMLElement
  const rect = container.getBoundingClientRect()
  const clickX = event.clientX - rect.left
  const percentage = (clickX / rect.width) * 100
  
  // Emit seek event (you'll need to handle this in the parent component)
  // For now, we'll just trigger a play event with the seek percentage
  console.log(`Seeking to ${percentage}% in episode ${episode.id}`)
}

// Handle waveform hover
const handleWaveformHover = (episode: Episode, event: MouseEvent) => {
  initializeWaveform(episode)
  
  const container = event.currentTarget as HTMLElement
  const rect = container.getBoundingClientRect()
  const hoverX = event.clientX - rect.left
  const percentage = (hoverX / rect.width) * 100
  const totalBars = getWaveformBars(episode).length
  const hoverIndex = Math.floor((percentage / 100) * totalBars)
  
  waveformData[episode.id].hoverIndex = hoverIndex
}

// Clear waveform hover
const clearWaveformHover = (episode: Episode) => {
  if (waveformData[episode.id]) {
    waveformData[episode.id].hoverIndex = -1
  }
}

// Methods
const selectEpisode = (episode: Episode) => {
  emit('episodeSelect', episode)
}

const playEpisode = (episode: Episode) => {
  emit('episodePlay', episode)
}

const shareEpisode = (episode: Episode) => {
  // Implement share functionality
  if (navigator.share) {
    navigator.share({
      title: episode.title,
      text: `Listen to ${episode.title}`,
      url: window.location.href
    })
  } else {
    // Fallback: copy to clipboard
    navigator.clipboard.writeText(window.location.href)
  }
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
    // Fallback embed code
    embedCode.value = `<iframe src="${window.location.origin}/embed/${episode.id}" width="100%" height="120" frameborder="0" allow="autoplay"></iframe>`
    showEmbedModal.value = true
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

/* SoundCloud-style animations */
@keyframes waveform-pulse {
  0%, 100% { transform: scaleY(1); }
  50% { transform: scaleY(1.2); }
}

@keyframes loading-bounce {
  0%, 100% { transform: scaleY(1); }
  50% { transform: scaleY(1.5); }
}

.waveform-bar.animate-pulse {
  animation: waveform-pulse 1s ease-in-out infinite;
}

.loading-bar {
  animation: loading-bounce 1s ease-in-out infinite;
}

/* Hover effects */
.episode-card:hover .waveform-container {
  background-color: #f9fafb;
}

.episode-card:hover .play-btn {
  transform: scale(1.05);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.action-btn:hover {
  transform: translateY(-1px);
}

/* Waveform interactions */
.waveform-container:hover .waveform-bar {
  transition: all 0.2s ease;
}

.waveform-bar:hover {
  transform: scaleY(1.1);
}

/* Play button animations */
.play-btn svg {
  transition: all 0.2s ease;
}

.play-btn:hover svg {
  transform: scale(1.1);
}

/* Episode card states */
.episode-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.episode-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

/* Orange gradient utilities */
.bg-orange-gradient {
  background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
}

.text-orange-gradient {
  background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* Focus states for accessibility */
.episode-card:focus-within {
  outline: 2px solid #f97316;
  outline-offset: 2px;
}

.play-btn:focus {
  outline: none;
  box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.3);
}

.action-btn:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.3);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .episode-content {
    padding: 1rem;
  }
  
  .episode-content > div {
    flex-direction: column;
    space-x: 0;
    gap: 1rem;
    text-align: center;
  }
  
  .play-btn {
    width: 3.5rem;
    height: 3.5rem;
  }
  
  .episode-info {
    text-align: center;
  }
  
  .episode-meta {
    justify-content: center;
    flex-wrap: wrap;
  }
  
  .waveform-bars {
    height: 3rem;
  }
  
  .action-btn {
    padding: 0.5rem;
  }
}

@media (max-width: 640px) {
  .episode-meta span {
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
  }
  
  .waveform-section {
    padding: 1rem;
  }
  
  .waveform-container {
    padding: 0.75rem;
  }
}

/* High contrast support */
@media (prefers-contrast: high) {
  .episode-card {
    border-width: 2px;
    border-color: #374151;
  }
  
  .waveform-bar {
    border: 1px solid #374151;
  }
  
  .play-btn {
    border: 2px solid #ffffff;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .episode-card,
  .play-btn,
  .action-btn,
  .waveform-bar,
  .loading-bar {
    transition: none;
    animation: none;
  }
  
  .episode-card:hover {
    transform: none;
  }
  
  .play-btn:hover {
    transform: none;
  }
}

/* Dark mode support (if needed) */
@media (prefers-color-scheme: dark) {
  .episode-card {
    background-color: #1f2937;
    border-color: #374151;
  }
  
  .episode-title {
    color: #f9fafb;
  }
  
  .episode-meta {
    color: #9ca3af;
  }
  
  .waveform-container {
    background-color: #374151;
  }
  
  .waveform-bar {
    background-color: #6b7280;
  }
}

/* Custom scrollbar for modal */
.embed-modal textarea::-webkit-scrollbar {
  width: 8px;
}

.embed-modal textarea::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

.embed-modal textarea::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.embed-modal textarea::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>