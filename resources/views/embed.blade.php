<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $episode['title'] }} - Tech Weekly</title>

<!-- DNS prefetch and preconnect for external audio domain -->
@php
  $r2Base = config('filesystems.disks.r2.url') ?? env('R2_PUBLIC_URL');
  $isExternalHttp = is_string($episode['url']) && str_starts_with($episode['url'], 'http');
  $isExternalRelative = is_string($episode['url']) && (str_starts_with($episode['url'], '/episodes/') || str_starts_with($episode['url'], 'episodes/'));
  $resolvedExternalUrl = $isExternalHttp ? $episode['url'] : ($isExternalRelative && $r2Base ? rtrim($r2Base, '/').'/'.ltrim($episode['url'], '/') : null);
  $resolvedHost = $resolvedExternalUrl ? parse_url($resolvedExternalUrl, PHP_URL_HOST) : null;
@endphp
@if($resolvedHost)
<link rel="dns-prefetch" href="//{{ $resolvedHost }}">
<link rel="preconnect" href="https://{{ $resolvedHost }}" crossorigin>
@endif

<!-- Preload the audio file for faster loading -->
@php
  // variables already computed above: $resolvedExternalUrl
@endphp
@if($resolvedExternalUrl)
<link rel="preload" href="{{ $resolvedExternalUrl }}" as="audio" type="audio/{{ strtolower($episode['format'] ?? 'mp3') }}">
@endif

<style>
  * {
    box-sizing: border-box;
  }

  body {
    margin: 0;
    padding: 0;
    background: #667eea;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: #333;
    min-height: 100vh;
  }

  .player {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 0;
    padding: 8px 6px;
    width: 100%;
    height: 120px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-top: none;
    position: sticky;
    top: 0;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    will-change: transform;
  }

  .player:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
  }

  .player-header {
    display: flex;
    align-items: center;
    margin-bottom: 4px;
    flex-shrink: 0;
  }

  .play-btn {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: none;
    background: #ff6b35;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: transform 0.2s ease;
    box-shadow: 0 4px 8px rgba(255, 107, 53, 0.3);
    will-change: transform;
  }

  .play-btn:hover {
    transform: scale(1.05);
  }

  .play-btn:active {
    transform: scale(0.98);
  }

  .play-icon, .pause-icon {
    width: 20px;
    height: 20px;
    fill: currentColor;
    transition: all 0.2s ease;
  }

  .pause-icon { display: none; }
  .playing .play-icon { display: none; }
  .playing .pause-icon { display: block; }

  .episode-info {
    flex: 1;
    margin-left: 12px;
    overflow: hidden;
    min-width: 0;
  }

  .title {
    font-weight: 700;
    font-size: 14px;
    color: #1a1a1a;
    line-height: 1.2;
    margin-bottom: 2px;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .subtitle {
    font-size: 12px;
    color: #666;
    font-weight: 500;
  }

  .controls-section {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 0;
    width: 100%;
    padding: 0;
    margin: 0;
  }

  .waveform-container {
    position: relative;
    flex: 1;
    padding: 0;
    display: flex;
    align-items: center;
    width: 100%;
  }

  .waveform {
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    position: relative;
    border-radius: 6px;
    padding: 0;
    background: #f8f9fa;
    width: 100%;
    will-change: contents;
  }

  .waveform:hover {
    background: #f1f3f4;
  }

  .bar {
    width: 3px;
    background: #cbd5e0;
    border-radius: 2px;
    height: 30%;
    opacity: 0.8;
    cursor: pointer;
    flex-shrink: 0;
    will-change: background-color, height;
  }

  .bar:hover {
    opacity: 1;
    background: #ff6b35;
  }

  .bar.active { 
    background: linear-gradient(to top, #ff6b35 0%, #ff8c42 100%);
    opacity: 1;
    transform: scaleY(1.3);
    box-shadow: 0 2px 12px rgba(255, 107, 53, 0.5);
  }

  .bar.playing {
    background: linear-gradient(to top, #ff4757 0%, #ff6b35 100%);
    animation: pulse 1.2s ease-in-out infinite alternate;
    box-shadow: 0 2px 16px rgba(255, 71, 87, 0.6);
  }

  @keyframes pulse {
    0% { 
      transform: scaleY(1.3); 
      box-shadow: 0 2px 16px rgba(255, 71, 87, 0.6);
    }
    100% { 
      transform: scaleY(1.5); 
      box-shadow: 0 3px 20px rgba(255, 71, 87, 0.8);
    }
  }

  .progress-indicator {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: 3px;
    background: linear-gradient(to bottom, #ff6b35, #ff8c42);
    border-radius: 2px;
    opacity: 0;
    transition: opacity 0.3s ease, left 0.1s ease;
    pointer-events: none;
    box-shadow: 0 2px 12px rgba(255, 107, 53, 0.7);
  }

  .waveform:hover .progress-indicator {
    opacity: 1;
  }

  .time-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 11px;
    color: #666;
    font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
    font-weight: 500;
    flex-shrink: 0;
  }

  .time-current {
    color: #ff6b35;
    font-weight: 600;
  }

  .time-separator {
    margin: 0 8px;
    opacity: 0.5;
  }

  .playback-controls {
    display: flex;
    gap: 8px;
    align-items: center;
  }

  .control-btn {
      width: 28px;
      height: 28px;
      border: none;
      background: rgba(255, 107, 53, 0.1);
      color: #ff6b35;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s ease;
      flex-shrink: 0;
    }

  .control-btn:hover {
    background: rgba(255, 107, 53, 0.2);
    transform: scale(1.05);
  }

  .control-btn:active {
    transform: scale(0.95);
  }

  .loading-indicator {
    display: none;
    width: 16px;
    height: 16px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #ff6b35;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 8px;
  }

  .loading .loading-indicator {
    display: block;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  .error-message {
    display: none;
    background: #fee;
    color: #c33;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 12px;
    margin-top: 8px;
    border: 1px solid #fcc;
  }

  /* Responsive design */
  @media (max-width: 480px) {
    .player {
      padding: 6px 4px;
      border-radius: 0;
      height: 120px;
    }
    
    .play-btn {
      width: 40px;
      height: 40px;
    }
    
    .play-icon, .pause-icon {
      width: 16px;
      height: 16px;
    }
    
    .title {
      font-size: 13px;
    }
    
    .subtitle {
      font-size: 11px;
    }
    
    .episode-info {
      margin-left: 10px;
    }
    
    .time-controls {
      font-size: 10px;
    }
    
    .control-btn {
      width: 24px;
      height: 24px;
    }
  }

  /* Accessibility improvements */
  .play-btn:focus {
    outline: 3px solid rgba(255, 107, 53, 0.5);
    outline-offset: 2px;
  }

  .waveform:focus {
    outline: 2px solid rgba(255, 107, 53, 0.5);
    outline-offset: 2px;
  }
</style>
</head>

<body>
  <div class="player" id="player">
    <div class="player-header">
      <button class="play-btn" id="playButton" aria-label="Play/Pause">
        <svg class="play-icon" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M8 5v14l11-7z"/>
        </svg>
        <svg class="pause-icon" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M6 19h4V5H6zm8-14v14h4V5z"/>
        </svg>
      </button>

      <div class="episode-info">
        <div class="title">{{ $episode['title'] }}</div>
      </div>

      <div class="playback-controls">
        <button class="control-btn" id="backwardBtn" aria-label="Rewind 10 seconds">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
            <path d="M11.99 5V1l-5 5 5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6h-2c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/>
            <text x="12" y="15" text-anchor="middle" font-size="8" font-weight="bold" fill="currentColor">10</text>
          </svg>
        </button>
        
        <button class="control-btn" id="forwardBtn" aria-label="Fast forward 10 seconds">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
            <path d="M12 5V1l5 5-5 5V7c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6h2c0 4.42-3.58 8-8 8s-8-3.58-8-8 3.58-8 8-8z"/>
            <text x="12" y="15" text-anchor="middle" font-size="8" font-weight="bold" fill="currentColor">10</text>
          </svg>
        </button>
      </div>

      <div class="loading-indicator"></div>
    </div>

    <div class="controls-section">
      <div class="waveform-container">
        <div class="waveform" id="waveform" tabindex="0" role="slider" aria-label="Audio progress"></div>
        <div class="progress-indicator" id="progressIndicator"></div>
      </div>

      <div class="time-controls">
        <span class="time-current" id="currentTime">0:00</span>
        <span class="time-separator">/</span>
        <span class="time-total" id="totalTime">0:00</span>
      </div>
    </div>

    <div class="error-message" id="errorMessage"></div>

    <audio id="audio" preload="metadata" src="@if($resolvedExternalUrl){{ $resolvedExternalUrl }}@else/api/stream/{{ $episode['filename'] }}@endif"></audio>
  </div>

<script>
  const audio = document.getElementById('audio');
  const player = document.getElementById('player');
  const playButton = document.getElementById('playButton');
  const backwardBtn = document.getElementById('backwardBtn');
  const forwardBtn = document.getElementById('forwardBtn');
  const currentTimeDisplay = document.getElementById('currentTime');
  const totalTimeDisplay = document.getElementById('totalTime');
  const waveform = document.getElementById('waveform');
  const progressIndicator = document.getElementById('progressIndicator');
  const errorMessage = document.getElementById('errorMessage');
  
  // State management
  let streamingUrl = null;
  let isStreamingSupported = false;
  let isDragging = false;
  let isLoading = false;
  let bars = [];
  let animationFrameId = null;
  let lastUpdateTime = 0;
  const UPDATE_THROTTLE = 100; // Update every 100ms instead of every frame

  // Generate optimized waveform bars with pre-calculated heights
  const totalBars = 60;
  const preCalculatedHeights = [
    45, 65, 35, 75, 25, 85, 55, 40, 70, 30, 80, 50, 60, 35, 75, 45, 65, 25, 85, 55,
    40, 70, 30, 80, 50, 60, 35, 75, 45, 65, 25, 85, 55, 40, 70, 30, 80, 50, 60, 35,
    75, 45, 65, 25, 85, 55, 40, 70, 30, 80, 50, 60, 35, 75, 45, 65, 25, 85, 55, 40
  ];
  
  function generateWaveform() {
    const fragment = document.createDocumentFragment();
    bars = [];
    
    for (let i = 0; i < totalBars; i++) {
      const bar = document.createElement('div');
      bar.className = 'bar';
      bar.setAttribute('data-index', i);
      bar.style.height = `${preCalculatedHeights[i]}%`;
      fragment.appendChild(bar);
      bars.push(bar);
    }
    
    waveform.innerHTML = '';
    waveform.appendChild(fragment);
  }

  // Improved time formatting
  function formatTime(seconds) {
    if (isNaN(seconds) || seconds < 0) return '0:00';
    
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);
    
    if (hours > 0) {
      return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    return `${minutes}:${secs.toString().padStart(2, '0')}`;
  }

  // Update time displays
  function updateTimeDisplays() {
    currentTimeDisplay.textContent = formatTime(audio.currentTime);
    totalTimeDisplay.textContent = formatTime(audio.duration || 0);
  }

  // Optimized waveform update with throttling
  function updateWaveform() {
    if (!audio.duration) return;
    
    const now = Date.now();
    if (now - lastUpdateTime < UPDATE_THROTTLE) return;
    lastUpdateTime = now;
    
    const progress = audio.currentTime / audio.duration;
    const activeBarIndex = Math.floor(progress * totalBars);
    
    // Use requestAnimationFrame for smooth visual updates
    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    
    animationFrameId = requestAnimationFrame(() => {
      bars.forEach((bar, index) => {
        bar.classList.remove('active', 'playing');
        
        if (index < activeBarIndex) {
          bar.classList.add('active');
        } else if (index === activeBarIndex && !audio.paused) {
          bar.classList.add('playing');
        }
      });
      
      updateTimeDisplays();
    });
  }

  // Enhanced seeking with full-width precision
  function seekToPosition(clientX) {
    if (!audio.duration) return;
    
    const rect = waveform.getBoundingClientRect();
    
    // Calculate click position across full width (no horizontal padding)
    const clickX = clientX - rect.left;
    const fullWidth = rect.width;
    
    // Ensure we're within bounds and calculate percentage
    const clampedClickX = Math.max(0, Math.min(fullWidth, clickX));
    const percentage = clampedClickX / fullWidth;
    
    // Calculate and set the exact seek time
    const seekTime = percentage * audio.duration;
    const newTime = Math.max(0, Math.min(audio.duration, seekTime));
    
    // Set the audio time and force immediate update
    audio.currentTime = newTime;
    updateWaveform();
    
    // Update progress indicator to exact position (full width)
    progressIndicator.style.left = `${percentage * fullWidth}px`;
  }

  // Show loading state
  function setLoadingState(loading) {
    isLoading = loading;
    player.classList.toggle('loading', loading);
  }

  // Show error message
  function showError(message) {
    errorMessage.textContent = message;
    errorMessage.style.display = 'block';
    setTimeout(() => {
      errorMessage.style.display = 'none';
    }, 5000);
  }

  // Enhanced audio loading with timeout and better error handling
  async function loadAudioIfNeeded() {
    // Audio is already loaded with metadata, just ensure it's ready
    if (audio.readyState < 1) {
      return new Promise((resolve, reject) => {
        const timeout = setTimeout(() => {
          audio.removeEventListener('loadedmetadata', onLoadedMetadata);
          audio.removeEventListener('error', onError);
          reject(new Error('Audio loading timeout - external server may be slow'));
        }, 15000); // 15 second timeout for external URLs

        const onLoadedMetadata = () => {
          clearTimeout(timeout);
          audio.removeEventListener('loadedmetadata', onLoadedMetadata);
          audio.removeEventListener('error', onError);
          resolve();
        };

        const onError = (e) => {
          clearTimeout(timeout);
          audio.removeEventListener('loadedmetadata', onLoadedMetadata);
          audio.removeEventListener('error', onError);
          reject(new Error('Failed to load audio from external server'));
        };

        audio.addEventListener('loadedmetadata', onLoadedMetadata);
        audio.addEventListener('error', onError);
      });
    }
  }

  // Event listeners
  playButton.addEventListener('click', async () => {
    try {
      // Only show loading if we need to load audio or if it's not ready to play
      if (audio.readyState < 3) {
        setLoadingState(true);
      }
      await loadAudioIfNeeded();
      
      if (audio.paused) {
        await audio.play();
        player.classList.add('playing');
      } else {
        audio.pause();
        player.classList.remove('playing');
      }
    } catch (error) {
      let errorMessage = 'Failed to play audio. Please try again.';
      
      // Provide more specific error messages for external URLs
      if (error.message.includes('timeout')) {
        errorMessage = 'Audio is taking too long to load. The external server may be slow.';
      } else if (error.message.includes('external server')) {
        errorMessage = 'Cannot connect to audio server. Please check your internet connection.';
      } else if (error.name === 'NotAllowedError') {
        errorMessage = 'Audio playback blocked. Please click play to start.';
      }
      
      showError(errorMessage);
      console.error('Playback error:', error);
    } finally {
      setLoadingState(false);
    }
  });

  // Fast forward and backward controls
  backwardBtn.addEventListener('click', () => {
    if (audio.duration) {
      const newTime = Math.max(0, audio.currentTime - 10);
      audio.currentTime = newTime;
      updateWaveform();
    }
  });

  forwardBtn.addEventListener('click', () => {
    if (audio.duration) {
      const newTime = Math.min(audio.duration, audio.currentTime + 10);
      audio.currentTime = newTime;
      updateWaveform();
    }
  });

  // Enhanced waveform interaction with optimized event listeners
  waveform.addEventListener('mousedown', async (e) => {
    e.preventDefault(); // Prevent text selection
    await loadAudioIfNeeded();
    isDragging = true;
    seekToPosition(e.clientX);
    waveform.focus(); // Ensure keyboard events work
  });

  waveform.addEventListener('mousemove', (e) => {
    if (!isDragging) {
      // Show progress indicator on hover with full-width positioning
      const rect = waveform.getBoundingClientRect();
      
      const hoverX = e.clientX - rect.left;
      const fullWidth = rect.width;
      const clampedHoverX = Math.max(0, Math.min(fullWidth, hoverX));
      const percentage = clampedHoverX / fullWidth;
      
      progressIndicator.style.left = `${percentage * fullWidth}px`;
    } else {
      seekToPosition(e.clientX);
    }
  }, { passive: false });

  waveform.addEventListener('mouseup', () => {
    isDragging = false;
  }, { passive: true });

  waveform.addEventListener('mouseleave', () => {
    isDragging = false;
    progressIndicator.style.opacity = '0';
  }, { passive: true });

  waveform.addEventListener('mouseenter', () => {
    progressIndicator.style.opacity = '1';
  }, { passive: true });

  // Global mouse events for dragging
  document.addEventListener('mouseup', () => {
    isDragging = false;
  });

  document.addEventListener('mousemove', (e) => {
    if (isDragging) {
      seekToPosition(e.clientX);
    }
  });

  // Keyboard accessibility
  waveform.addEventListener('keydown', (e) => {
    if (!audio.duration) return;
    
    let seekAmount = 0;
    switch (e.key) {
      case 'ArrowLeft':
        seekAmount = -10; // 10 seconds back
        break;
      case 'ArrowRight':
        seekAmount = 10; // 10 seconds forward
        break;
      case 'Home':
        audio.currentTime = 0;
        updateWaveform();
        return;
      case 'End':
        audio.currentTime = audio.duration;
        updateWaveform();
        return;
      case ' ':
        e.preventDefault();
        playButton.click();
        return;
    }
    
    if (seekAmount !== 0) {
      e.preventDefault();
      const newTime = Math.max(0, Math.min(audio.duration, audio.currentTime + seekAmount));
      audio.currentTime = newTime;
      updateWaveform();
    }
  });

  // Audio event listeners with improved accuracy
  audio.addEventListener('timeupdate', () => {
    if (!isDragging) { // Only update if not actively seeking
      updateWaveform();
      
      // Update progress indicator position during playback (full width)
      if (audio.duration) {
        const rect = waveform.getBoundingClientRect();
        const fullWidth = rect.width;
        const percentage = audio.currentTime / audio.duration;
        
        progressIndicator.style.left = `${percentage * fullWidth}px`;
      }
    }
  });
  audio.addEventListener('loadedmetadata', () => {
    updateTimeDisplays();
    updateWaveform(); // Initial waveform update
    setLoadingState(false);
  });
  audio.addEventListener('loadstart', () => {
    // Only show loading if user is actively trying to play
    if (!audio.paused || player.classList.contains('playing')) {
      setLoadingState(true);
    }
  });
  audio.addEventListener('canplay', () => setLoadingState(false));
  audio.addEventListener('canplaythrough', () => setLoadingState(false));
  audio.addEventListener('waiting', () => {
    // Only show loading during playback, not during initial load
    if (!audio.paused) {
      setLoadingState(true);
    }
  });
  audio.addEventListener('playing', () => setLoadingState(false));
  audio.addEventListener('ended', () => {
    player.classList.remove('playing');
    // Reset to beginning
    audio.currentTime = 0;
  });
  audio.addEventListener('error', (e) => {
    setLoadingState(false);
    showError('Failed to load audio file.');
    console.error('Audio error:', e);
  });

  // Audio is already initialized with direct source, no need for additional streaming setup
  isStreamingSupported = true;
  streamingUrl = '@if($resolvedExternalUrl){{ $resolvedExternalUrl }}@else/api/stream/{{ $episode['filename'] }}@endif';

  // Cleanup function
  function cleanup() {
    if (animationFrameId) {
      cancelAnimationFrame(animationFrameId);
    }
  }

  // Initialize everything - generate waveform and log audio initialization
  generateWaveform();
  console.log('Audio initialized with source:', audio.src);
  
  // Clean up on page unload
  window.addEventListener('beforeunload', cleanup, { passive: true });
</script>
</body>
</html>
