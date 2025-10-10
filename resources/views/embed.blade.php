<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $episode['title'] }} - Tech Weekly</title>

<style>
  body {
    margin: 0;
    padding: 0;
    background: #f8f9fa;
    font-family: 'Inter', system-ui, sans-serif;
    color: #333;
  }

  .player {
    display: flex;
    align-items: center;
    background: #ffffff;
    border-radius: 10px;
    padding: 14px 18px;
    height: 100px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    overflow: hidden;
  }

  .play-btn {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    border: none;
    background: #ff5500;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: transform 0.25s ease;
  }
  .play-btn:hover { transform: scale(1.08); }

  .play-icon, .pause-icon {
    width: 26px;
    height: 26px;
    fill: currentColor;
  }
  .pause-icon { display: none; }
  .playing .play-icon { display: none; }
  .playing .pause-icon { display: block; }

  .details {
    flex: 1;
    margin-left: 16px;
    overflow: hidden;
  }

  .title {
    font-weight: 600;
    font-size: 22px;
    color: #1a1a1a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .author {
    font-size: 13px;
    color: #666;
  }

  .waveform {
    margin-top: 12px;
    height: 32px;
    display: flex;
    align-items: center;
    gap: 1px;
    padding: 0 2px;
  }

  .bar {
    width: 2px;
    background: linear-gradient(to top, #e1e5e9, #f0f2f5);
    border-radius: 1px;
    height: 20%;
    transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
    opacity: 0.8;
  }
  .bar.active { 
    background: linear-gradient(to top, #ff5500, #ff7733);
    opacity: 1;
    transform: scaleY(1.1);
  }

  .time {
    font-size: 12px;
    color: #666;
    font-family: monospace;
    margin-left: 12px;
    white-space: nowrap;
  }
</style>
</head>

<body>
  <div class="player" id="player">
    <button class="play-btn" id="playButton">
      <svg class="play-icon" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
      <svg class="pause-icon" viewBox="0 0 24 24"><path d="M6 19h4V5H6zm8-14v14h4V5z"/></svg>
    </button>

    <div class="details">
      <div class="title">{{ $episode['title'] }}</div>
      <div class="waveform" id="waveform"></div>
    </div>

    <div class="time" id="time">0:00 / 0:00</div>

    <audio id="audio" preload="metadata"></audio>
  </div>

<script>
  const audio = document.getElementById('audio');
  const player = document.getElementById('player');
  const playButton = document.getElementById('playButton');
  const timeDisplay = document.getElementById('time');
  const waveform = document.getElementById('waveform');
  
  // Initialize streaming URL
  let streamingUrl = null;
  let isStreamingSupported = false;

  // Generate waveform bars
  const totalBars = 120;
  for (let i = 0; i < totalBars; i++) {
    const bar = document.createElement('div');
    bar.className = 'bar';
    // Create more varied heights for a natural waveform look
    const baseHeight = 15 + Math.random() * 25;
    const variation = Math.sin(i * 0.1) * 15 + Math.random() * 20;
    bar.style.height = `${Math.max(8, Math.min(85, baseHeight + variation))}%`;
    waveform.appendChild(bar);
  }
  const bars = waveform.querySelectorAll('.bar');

  function formatTime(sec) {
    if (isNaN(sec)) return '0:00';
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
  }

  function updateTime() {
    const current = formatTime(audio.currentTime);
    const total = formatTime(audio.duration);
    timeDisplay.textContent = `${current} / ${total}`;
  }

  function updateWaveform() {
    const progress = (audio.currentTime / audio.duration) * totalBars;
    bars.forEach((bar, i) => {
      bar.classList.toggle('active', i < progress);
    });
    updateTime();
  }

  playButton.addEventListener('click', () => {
    if (audio.paused) {
      audio.play();
      player.classList.add('playing');
    } else {
      audio.pause();
      player.classList.remove('playing');
    }
  });

  // Initialize streaming
  async function initializeStreaming() {
    try {
      const response = await fetch(`/api/episodes/{{ $episode['id'] }}/stream`);
      const data = await response.json();
      
      if (data.stream_url) {
        streamingUrl = data.stream_url;
        isStreamingSupported = data.supports_range || false;
        audio.src = streamingUrl;
      } else {
        // Fallback to direct URL
        audio.src = '{{ $episode['url'] }}';
      }
    } catch (error) {
      console.warn('Streaming not available, using direct URL:', error);
      audio.src = '{{ $episode['url'] }}';
    }
  }

  // Enhanced waveform click for seeking
  waveform.addEventListener('click', (e) => {
    if (!audio.duration) return;
    
    const rect = waveform.getBoundingClientRect();
    const clickX = e.clientX - rect.left;
    const percentage = clickX / rect.width;
    const seekTime = percentage * audio.duration;
    
    audio.currentTime = seekTime;
  });

  audio.addEventListener('timeupdate', updateWaveform);
  audio.addEventListener('loadedmetadata', updateTime);
  audio.addEventListener('ended', () => {
    player.classList.remove('playing');
  });

  // Initialize streaming on load
  initializeStreaming();
</script>
</body>
</html>
