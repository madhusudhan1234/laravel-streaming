# Laravel Audio Streaming Application - UI Design Plan

## 1. Design System Overview

### 1.1 Color Palette
```css
/* Primary Colors */
--primary-blue: #1e40af;      /* Deep blue for primary actions */
--primary-white: #ffffff;     /* Clean white background */

/* Secondary Colors */
--light-gray: #f3f4f6;        /* Light backgrounds and dividers */
--dark-gray: #374151;         /* Text and secondary elements */
--medium-gray: #6b7280;       /* Subtle text and borders */

/* Accent Colors */
--success-green: #10b981;     /* Success states and play indicators */
--warning-orange: #f59e0b;    /* Loading and buffering states */
--error-red: #ef4444;         /* Error states */

/* Audio Player Specific */
--progress-bg: #e5e7eb;       /* Progress bar background */
--progress-fill: #1e40af;     /* Progress bar fill */
--volume-bg: #d1d5db;         /* Volume control background */
```

### 1.2 Typography
```css
/* Font Family */
font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;

/* Font Sizes */
--text-xs: 0.75rem;    /* 12px - Small labels */
--text-sm: 0.875rem;   /* 14px - Secondary text */
--text-base: 1rem;     /* 16px - Base text */
--text-lg: 1.125rem;   /* 18px - Episode titles */
--text-xl: 1.25rem;    /* 20px - Page headers */
--text-2xl: 1.5rem;    /* 24px - Main headings */

/* Font Weights */
--font-normal: 400;
--font-medium: 500;
--font-semibold: 600;
--font-bold: 700;
```

### 1.3 Spacing & Layout
```css
/* Spacing Scale */
--space-1: 0.25rem;   /* 4px */
--space-2: 0.5rem;    /* 8px */
--space-3: 0.75rem;   /* 12px */
--space-4: 1rem;      /* 16px */
--space-6: 1.5rem;    /* 24px */
--space-8: 2rem;      /* 32px */
--space-12: 3rem;     /* 48px */

/* Border Radius */
--radius-sm: 0.25rem;  /* 4px - Small elements */
--radius-md: 0.375rem; /* 6px - Buttons */
--radius-lg: 0.5rem;   /* 8px - Cards */
--radius-xl: 0.75rem;  /* 12px - Large containers */

/* Shadows */
--shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
--shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
--shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
```

## 2. Component Specifications

### 2.1 Audio Player Component (`AudioPlayer.vue`)

#### Visual Design
```
┌─────────────────────────────────────────────────────────────┐
│  🎵 Episode 1 - Madhu Sudhan Subedi Tech Weekly            │
│  ─────────────────────────────────────────────────────────  │
│  ⏸️  ⏮️  ⏭️     ████████████░░░░░░░░  15:30 / 45:30  🔊 ███ │
│                                                             │
│  [Get Embed Code]                                           │
└─────────────────────────────────────────────────────────────┘
```

#### Component Structure
```vue
<template>
  <div class="audio-player bg-white rounded-lg shadow-md p-6 border border-gray-200">
    <!-- Episode Title -->
    <div class="episode-header mb-4">
      <h3 class="text-lg font-semibold text-gray-900">{{ episode.title }}</h3>
      <p class="text-sm text-gray-600">{{ formatDate(episode.published_date) }}</p>
    </div>
    
    <!-- Audio Controls -->
    <div class="audio-controls flex items-center space-x-4 mb-4">
      <button class="play-pause-btn">
        <PlayIcon v-if="!isPlaying" class="w-8 h-8 text-primary-blue" />
        <PauseIcon v-else class="w-8 h-8 text-primary-blue" />
      </button>
      
      <button class="previous-btn">
        <BackwardIcon class="w-6 h-6 text-gray-600" />
      </button>
      
      <button class="next-btn">
        <ForwardIcon class="w-6 h-6 text-gray-600" />
      </button>
    </div>
    
    <!-- Progress Bar -->
    <div class="progress-section mb-4">
      <div class="progress-bar-container">
        <div class="progress-bar bg-gray-200 rounded-full h-2 cursor-pointer">
          <div class="progress-fill bg-primary-blue h-2 rounded-full transition-all duration-300"></div>
        </div>
      </div>
      
      <div class="time-display flex justify-between text-sm text-gray-600 mt-2">
        <span>{{ formatTime(currentTime) }}</span>
        <span>{{ formatTime(duration) }}</span>
      </div>
    </div>
    
    <!-- Volume Control -->
    <div class="volume-section flex items-center space-x-3">
      <SpeakerWaveIcon class="w-5 h-5 text-gray-600" />
      <div class="volume-slider flex-1 max-w-24">
        <input type="range" min="0" max="100" v-model="volume" 
               class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
      </div>
    </div>
    
    <!-- Embed Code Button -->
    <div class="embed-section mt-6 pt-4 border-t border-gray-200">
      <button class="embed-btn bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors">
        Get Embed Code
      </button>
    </div>
  </div>
</template>
```

#### Tailwind CSS Classes
```css
/* Audio Player Container */
.audio-player {
  @apply bg-white rounded-lg shadow-md p-6 border border-gray-200 max-w-2xl mx-auto;
}

/* Play/Pause Button */
.play-pause-btn {
  @apply w-12 h-12 bg-primary-blue hover:bg-blue-700 rounded-full flex items-center justify-center text-white transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
}

/* Progress Bar */
.progress-bar {
  @apply bg-gray-200 rounded-full h-2 cursor-pointer relative overflow-hidden;
}

.progress-fill {
  @apply bg-primary-blue h-full rounded-full transition-all duration-300 ease-out;
}

/* Volume Slider */
.volume-slider input[type="range"] {
  @apply w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer;
}

.volume-slider input[type="range"]::-webkit-slider-thumb {
  @apply appearance-none w-4 h-4 bg-primary-blue rounded-full cursor-pointer;
}
```

### 2.2 Episode List Component (`EpisodeList.vue`)

#### Visual Design
```
┌─────────────────────────────────────────────────────────────┐
│  📻 Madhu Sudhan Subedi Tech Weekly                         │
│  ═══════════════════════════════════════════════════════════ │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ ▶️  Episode 1 - Madhu Sudhan Subedi Tech Weekly    │   │
│  │     45:30 • Jan 1, 2024 • 42.5MB                   │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ ▶️  Episode 2 - Madhu Sudhan Subedi Tech Weekly    │   │
│  │     38:15 • Jan 8, 2024 • 35.2MB                   │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ... (more episodes)                                        │
└─────────────────────────────────────────────────────────────┘
```

#### Component Structure
```vue
<template>
  <div class="episode-list">
    <!-- Header -->
    <div class="list-header mb-8 text-center">
      <h1 class="text-2xl font-bold text-gray-900 mb-2">
        📻 Madhu Sudhan Subedi Tech Weekly
      </h1>
      <p class="text-gray-600">{{ episodes.length }} episodes available</p>
    </div>
    
    <!-- Episodes Grid -->
    <div class="episodes-grid space-y-4">
      <div v-for="episode in episodes" :key="episode.id" 
           class="episode-card bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow duration-200">
        
        <div class="episode-content flex items-center space-x-4">
          <!-- Play Button -->
          <button @click="playEpisode(episode)" 
                  class="play-btn w-12 h-12 bg-primary-blue hover:bg-blue-700 rounded-full flex items-center justify-center text-white transition-colors">
            <PlayIcon class="w-6 h-6 ml-1" />
          </button>
          
          <!-- Episode Info -->
          <div class="episode-info flex-1">
            <h3 class="episode-title text-lg font-semibold text-gray-900 mb-1">
              {{ episode.title }}
            </h3>
            <div class="episode-meta text-sm text-gray-600 flex items-center space-x-3">
              <span>{{ episode.duration }}</span>
              <span>•</span>
              <span>{{ formatDate(episode.published_date) }}</span>
              <span>•</span>
              <span>{{ episode.file_size }}</span>
            </div>
          </div>
          
          <!-- Embed Link -->
          <button @click="showEmbedCode(episode)" 
                  class="embed-link text-gray-500 hover:text-primary-blue transition-colors">
            <LinkIcon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
```

### 2.3 Embed Player Component (`EmbedPlayer.vue`)

#### Visual Design (Minimal)
```
┌─────────────────────────────────────────────────────┐
│ ⏸️ Episode 1 - Madhu Sudhan... ████████░░ 15:30    │
└─────────────────────────────────────────────────────┘
```

#### Component Structure
```vue
<template>
  <div class="embed-player bg-white rounded border border-gray-300 p-3 max-w-md">
    <div class="embed-content flex items-center space-x-3">
      <!-- Play/Pause -->
      <button class="play-btn w-8 h-8 bg-primary-blue hover:bg-blue-700 rounded-full flex items-center justify-center text-white">
        <PlayIcon v-if="!isPlaying" class="w-4 h-4 ml-0.5" />
        <PauseIcon v-else class="w-4 h-4" />
      </button>
      
      <!-- Episode Title (Truncated) -->
      <div class="episode-info flex-1 min-w-0">
        <h4 class="text-sm font-medium text-gray-900 truncate">{{ episode.title }}</h4>
      </div>
      
      <!-- Progress & Time -->
      <div class="progress-time flex items-center space-x-2 text-xs text-gray-600">
        <div class="progress-mini w-16 h-1 bg-gray-200 rounded-full overflow-hidden">
          <div class="progress-fill-mini bg-primary-blue h-full transition-all duration-300"></div>
        </div>
        <span class="time-mini whitespace-nowrap">{{ formatTime(currentTime) }}</span>
      </div>
    </div>
  </div>
</template>
```

## 3. Page Layouts

### 3.1 Home Page Layout (`Home.vue`)

#### Desktop Layout (1024px+)
```
┌─────────────────────────────────────────────────────────────┐
│                        Header Area                          │
│  📻 Madhu Sudhan Subedi Tech Weekly                         │
│  ═══════════════════════════════════════════════════════════ │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                                                     │   │
│  │              Audio Player Component                 │   │
│  │            (Currently Playing)                      │   │
│  │                                                     │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                                                     │   │
│  │              Episode List Component                 │   │
│  │                                                     │   │
│  │  • Episode 1 - Madhu Sudhan Subedi Tech Weekly     │   │
│  │  • Episode 2 - Madhu Sudhan Subedi Tech Weekly     │   │
│  │  • Episode 3 - Madhu Sudhan Subedi Tech Weekly     │   │
│  │  • ...                                              │   │
│  │                                                     │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│                        Footer Area                          │
└─────────────────────────────────────────────────────────────┘
```

#### Mobile Layout (< 768px)
```
┌─────────────────────────┐
│    📻 Tech Weekly       │
│  ═════════════════════  │
│                         │
│  ┌─────────────────┐   │
│  │   Audio Player  │   │
│  │   (Compact)     │   │
│  └─────────────────┘   │
│                         │
│  ┌─────────────────┐   │
│  │ ▶️ Episode 1    │   │
│  │   45:30 • Jan 1 │   │
│  └─────────────────┘   │
│                         │
│  ┌─────────────────┐   │
│  │ ▶️ Episode 2    │   │
│  │   38:15 • Jan 8 │   │
│  └─────────────────┘   │
│                         │
│  ...                    │
└─────────────────────────┘
```

### 3.2 Embed Page Layout (`/embed/{id}`)

#### Iframe Embed (Responsive)
```
┌─────────────────────────────────────────────────────┐
│ ⏸️ Episode 1 - Madhu Sudhan... ████████░░ 15:30    │
└─────────────────────────────────────────────────────┘

<!-- Embed Code Example -->
<iframe src="https://yourapp.com/embed/1" 
        width="400" 
        height="80" 
        frameborder="0">
</iframe>
```

## 4. Responsive Design Guidelines

### 4.1 Breakpoints
```css
/* Mobile First Approach */
/* xs: 0px - 639px (Mobile) */
/* sm: 640px - 767px (Large Mobile) */
/* md: 768px - 1023px (Tablet) */
/* lg: 1024px - 1279px (Desktop) */
/* xl: 1280px+ (Large Desktop) */
```

### 4.2 Component Responsive Behavior

#### Audio Player Responsiveness
```css
/* Mobile (< 768px) */
.audio-player {
  @apply p-4 mx-2;
}

.audio-controls {
  @apply flex-col space-y-3 space-x-0;
}

.progress-section {
  @apply order-first mb-4;
}

/* Tablet (768px - 1023px) */
@media (min-width: 768px) {
  .audio-player {
    @apply p-6 mx-4;
  }
  
  .audio-controls {
    @apply flex-row space-y-0 space-x-4;
  }
}

/* Desktop (1024px+) */
@media (min-width: 1024px) {
  .audio-player {
    @apply max-w-2xl mx-auto;
  }
}
```

#### Episode List Responsiveness
```css
/* Mobile */
.episode-card {
  @apply p-3;
}

.episode-content {
  @apply flex-col space-x-0 space-y-3 text-center;
}

.episode-meta {
  @apply flex-col space-x-0 space-y-1;
}

/* Tablet & Desktop */
@media (min-width: 768px) {
  .episode-card {
    @apply p-4;
  }
  
  .episode-content {
    @apply flex-row space-y-0 space-x-4 text-left;
  }
  
  .episode-meta {
    @apply flex-row space-y-0 space-x-3;
  }
}
```

## 5. Accessibility Considerations

### 5.1 ARIA Labels and Roles
```vue
<!-- Audio Player Accessibility -->
<div class="audio-player" role="region" aria-label="Audio Player">
  <button class="play-pause-btn" 
          :aria-label="isPlaying ? 'Pause episode' : 'Play episode'"
          :aria-pressed="isPlaying">
    <!-- Icon -->
  </button>
  
  <div class="progress-bar" 
       role="slider" 
       :aria-valuenow="currentTime"
       :aria-valuemin="0"
       :aria-valuemax="duration"
       aria-label="Audio progress">
  </div>
  
  <input type="range" 
         class="volume-slider"
         aria-label="Volume control"
         min="0" 
         max="100" 
         :value="volume">
</div>
```

### 5.2 Keyboard Navigation
```css
/* Focus States */
.play-pause-btn:focus,
.episode-card button:focus {
  @apply outline-none ring-2 ring-primary-blue ring-offset-2;
}

/* High Contrast Support */
@media (prefers-contrast: high) {
  .progress-bar {
    @apply border border-gray-800;
  }
  
  .progress-fill {
    @apply bg-gray-900;
  }
}

/* Reduced Motion Support */
@media (prefers-reduced-motion: reduce) {
  .progress-fill,
  .audio-controls button {
    @apply transition-none;
  }
}
```

## 6. Implementation Guidelines

### 6.1 Vue.js Component Structure
```typescript
// AudioPlayer.vue Composition API Structure
interface Episode {
  id: number;
  title: string;
  filename: string;
  duration: string;
  url: string;
  published_date: string;
  file_size: string;
}

interface AudioState {
  isPlaying: boolean;
  currentTime: number;
  duration: number;
  volume: number;
  isLoading: boolean;
  error: string | null;
}

// Composable for audio functionality
export function useAudioPlayer(episode: Ref<Episode>) {
  const audioState = reactive<AudioState>({
    isPlaying: false,
    currentTime: 0,
    duration: 0,
    volume: 80,
    isLoading: false,
    error: null
  });
  
  // Audio methods
  const play = () => { /* implementation */ };
  const pause = () => { /* implementation */ };
  const seek = (time: number) => { /* implementation */ };
  const setVolume = (volume: number) => { /* implementation */ };
  
  return {
    audioState,
    play,
    pause,
    seek,
    setVolume
  };
}
```

### 6.2 Tailwind CSS Configuration
```javascript
// tailwind.config.js
module.exports = {
  content: [
    './resources/js/**/*.{vue,js,ts}',
    './resources/views/**/*.blade.php',
  ],
  theme: {
    extend: {
      colors: {
        'primary-blue': '#1e40af',
        'light-gray': '#f3f4f6',
        'dark-gray': '#374151',
      },
      fontFamily: {
        'sans': ['Inter', 'system-ui', 'sans-serif'],
      },
      spacing: {
        '18': '4.5rem',
        '88': '22rem',
      },
      animation: {
        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}
```

### 6.3 Performance Optimizations
```vue
<!-- Lazy Loading for Episode List -->
<template>
  <div class="episode-list">
    <div v-for="episode in visibleEpisodes" 
         :key="episode.id"
         class="episode-card"
         v-intersection-observer="onEpisodeVisible">
      <!-- Episode content -->
    </div>
  </div>
</template>

<script setup>
// Virtual scrolling for large episode lists
import { useVirtualList } from '@vueuse/core';

const { list, containerProps, wrapperProps } = useVirtualList(
  episodes,
  {
    itemHeight: 80,
    overscan: 5,
  }
);
</script>
```

## 7. Testing Considerations

### 7.1 Component Testing
```typescript
// AudioPlayer.test.ts
import { mount } from '@vue/test-utils';
import AudioPlayer from '@/components/AudioPlayer.vue';

describe('AudioPlayer', () => {
  it('should play audio when play button is clicked', async () => {
    const wrapper = mount(AudioPlayer, {
      props: {
        episode: mockEpisode
      }
    });
    
    await wrapper.find('.play-pause-btn').trigger('click');
    expect(wrapper.vm.isPlaying).toBe(true);
  });
  
  it('should be accessible via keyboard', async () => {
    const wrapper = mount(AudioPlayer);
    const playButton = wrapper.find('.play-pause-btn');
    
    await playButton.trigger('keydown.space');
    expect(wrapper.vm.isPlaying).toBe(true);
  });
});
```

### 7.2 Visual Regression Testing
```javascript
// Use tools like Percy or Chromatic for visual testing
// Test different screen sizes and states
const testCases = [
  { name: 'Audio Player - Desktop', viewport: '1024x768' },
  { name: 'Audio Player - Mobile', viewport: '375x667' },
  { name: 'Episode List - Loading', state: 'loading' },
  { name: 'Embed Player - Playing', state: 'playing' }
];
```

This comprehensive UI design plan provides the foundation for implementing a clean, accessible, and responsive audio streaming application using Vue.js and Tailwind CSS within the Laravel framework.