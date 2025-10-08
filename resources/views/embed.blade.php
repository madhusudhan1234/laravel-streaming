<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $episode['title'] }} - Tech Weekly Podcast</title>
    
    <!-- Prevent Vite client loading -->
    <script>
        // Disable Vite client for embed
        if (window.location.search.includes('ide_webview_request_time')) {
            window.__vite_is_modern_browser = false;
        }
    </script>
    
    <!-- Meta tags for embedding -->
    <meta name="description" content="Listen to {{ $episode['title'] }} from Tech Weekly Podcast by Madhu Sudhan Subedi">
    <meta name="author" content="Madhu Sudhan Subedi">
    
    <!-- Open Graph tags -->
    <meta property="og:title" content="{{ $episode['title'] }}">
    <meta property="og:description" content="Listen to {{ $episode['title'] }} from Tech Weekly Podcast">
    <meta property="og:type" content="music.song">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="Tech Weekly Podcast">
    
    <!-- Twitter Card tags -->
    <meta name="twitter:card" content="player">
    <meta name="twitter:title" content="{{ $episode['title'] }}">
    <meta name="twitter:description" content="Listen to {{ $episode['title'] }} from Tech Weekly Podcast">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Custom styles for embed player matching the main design -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8f9fa;
            color: #2d3748;
            font-size: 14px;
            line-height: 1.5;
            padding: 0;
            margin: 0;
        }
        
        .embed-container {
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #fef7ed 0%, #fed7aa 100%);
        }
        
        .embed-player {
            width: 100%;
            max-width: 800px;
            background: linear-gradient(135deg, #fef7ed 0%, #ffffff 100%);
            border: 1px solid #fed7aa;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 10px 25px rgba(251, 146, 60, 0.15), 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .embed-player:hover {
            box-shadow: 0 20px 40px rgba(251, 146, 60, 0.2), 0 8px 16px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .episode-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .episode-title {
            font-size: 18px;
            font-weight: 700;
            color: #1a202c;
            margin: 0;
            flex: 1;
            line-height: 1.4;
        }
        
        .share-button {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            color: #4a5568;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .share-button:hover {
            background: #edf2f7;
            border-color: #cbd5e0;
        }
        
        .player-controls {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .play-button {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(251, 146, 60, 0.4);
            flex-shrink: 0;
        }
        
        .play-button:hover {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(251, 146, 60, 0.5);
        }
        
        .play-button:active {
            transform: scale(0.98);
        }
        
        .play-button:disabled {
            background: #e2e8f0;
            cursor: not-allowed;
            transform: scale(1);
            box-shadow: none;
            color: #a0aec0;
        }
        
        .play-icon, .pause-icon {
            width: 20px;
            height: 20px;
            fill: currentColor;
        }
        
        .pause-icon {
            display: none;
        }
        
        .playing .play-icon {
            display: none;
        }
        
        .playing .pause-icon {
            display: block;
        }
        
        .time-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
            font-size: 13px;
            color: #4a5568;
            font-weight: 500;
        }
        
        .waveform-section {
            margin-bottom: 16px;
        }
        
        .waveform-container {
            position: relative;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .waveform-container:hover {
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
        }
        
        .waveform-bars {
            display: flex;
            align-items: end;
            justify-content: space-between;
            height: 64px;
            gap: 2px;
        }
        
        .waveform-bar {
            background: #9ca3af;
            border-radius: 2px 2px 0 0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            min-height: 4px;
        }
        
        .waveform-bar:hover {
            background: #6b7280;
        }
        
        .waveform-bar.active {
            background: linear-gradient(to top, #f97316 0%, #fb923c 100%);
            box-shadow: 0 0 8px rgba(251, 146, 60, 0.4);
        }
        
        .waveform-bar.playing {
            animation: waveform-pulse 0.8s ease-in-out infinite alternate;
        }
        
        @keyframes waveform-pulse {
            0% { opacity: 0.8; transform: scaleY(0.95); }
            100% { opacity: 1; transform: scaleY(1.05); }
        }
        
        .waveform-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
        }
        
        .play-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: opacity 0.3s ease;
            backdrop-filter: blur(2px);
        }
        
        .waveform-container:hover .play-overlay {
            opacity: 1;
        }
        
        .play-button-overlay {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 4px 12px rgba(251, 146, 60, 0.4);
            transform: scale(1);
            transition: transform 0.2s ease;
        }
        
        .play-button-overlay:hover {
            transform: scale(1.1);
        }
        
        .play-button-overlay svg {
            width: 20px;
            height: 20px;
            margin-left: 2px;
            fill: currentColor;
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.9);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .loading-overlay.active {
            opacity: 1;
        }
        
        .loading-bars {
            display: flex;
            gap: 4px;
        }
        
        .loading-bar {
            width: 4px;
            background: #f97316;
            border-radius: 2px;
            animation: loading-bounce 1.5s ease-in-out infinite;
        }
        
        .loading-bar:nth-child(1) { height: 20px; animation-delay: 0s; }
        .loading-bar:nth-child(2) { height: 30px; animation-delay: 0.1s; }
        .loading-bar:nth-child(3) { height: 25px; animation-delay: 0.2s; }
        .loading-bar:nth-child(4) { height: 35px; animation-delay: 0.3s; }
        .loading-bar:nth-child(5) { height: 28px; animation-delay: 0.4s; }
        
        @keyframes loading-bounce {
            0%, 100% { transform: scaleY(0.5); opacity: 0.7; }
            50% { transform: scaleY(1.2); opacity: 1; }
        }
        
        .time-display {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
            font-size: 12px;
            color: #718096;
            font-weight: 500;
        }
        
        .volume-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .volume-button {
            background: none;
            border: none;
            cursor: pointer;
            color: #718096;
            padding: 4px;
            border-radius: 4px;
            transition: color 0.2s ease;
        }
        
        .volume-button:hover {
            color: #4a5568;
        }
        
        .volume-slider {
            flex: 1;
            max-width: 100px;
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            outline: none;
            cursor: pointer;
            -webkit-appearance: none;
        }
        
        .volume-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            background: #fb923c;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .volume-slider::-moz-range-thumb {
            width: 16px;
            height: 16px;
            background: #fb923c;
            border-radius: 50%;
            cursor: pointer;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .loading {
            opacity: 0.6;
        }
        
        .error {
            color: #e53e3e;
            font-size: 12px;
            margin-top: 8px;
            padding: 8px 12px;
            background: #fed7d7;
            border: 1px solid #feb2b2;
            border-radius: 6px;
        }
        
        /* Responsive design */
        @media (max-width: 640px) {
            .embed-container {
                padding: 12px;
            }
            
            .embed-player {
                padding: 16px;
            }
            
            .episode-title {
                font-size: 16px;
            }
            
            .play-button {
                width: 48px;
                height: 48px;
            }
            
            .play-icon, .pause-icon {
                width: 16px;
                height: 16px;
            }
        }
        
        @media (max-width: 480px) {
            .player-controls {
                gap: 12px;
            }
            
            .volume-section {
                gap: 8px;
            }
            
            .volume-slider {
                max-width: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="embed-container">
        <div id="embed-player"></div>
    </div>

    <script>
        // Episode data from Laravel
        const episodeData = @json($episode);
        
        // Enhanced vanilla JS audio player matching main design
        class EmbedAudioPlayer {
            constructor(episode) {
                this.episode = episode;
                this.audio = null;
                this.isPlaying = false;
                this.currentTime = 0;
                this.duration = 0;
                this.volume = 80;
                this.loading = true;
                this.error = '';
                
                this.init();
            }
            
            init() {
                this.render();
                this.setupAudio();
                this.bindEvents();
                
                // Fallback: Enable play button after 3 seconds if still disabled
                setTimeout(() => {
                    const playButton = document.getElementById('playButton');
                    if (playButton && playButton.disabled) {
                        console.log('Fallback: Enabling play button after timeout');
                        playButton.disabled = false;
                        this.updateUI();
                    }
                }, 3000);
            }
            
            render() {
                const container = document.getElementById('embed-player');
                container.innerHTML = `
                    <div class="embed-player">
                        <!-- Episode Header -->
                        <div class="episode-header">
                            <h1 class="episode-title">{{ $episode['title'] }}</h1>
                            <button class="share-button" onclick="this.shareEpisode()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92S19.61 16.08 18 16.08z"/>
                                </svg>
                                Share
                            </button>
                        </div>
                        
                        <!-- Player Controls -->
                        <div class="player-controls">
                            <button class="play-button" id="playButton" disabled>
                                <svg class="play-icon" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                                <svg class="pause-icon" viewBox="0 0 24 24">
                                    <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                                </svg>
                            </button>
                            
                            <div class="time-info">
                                <span id="currentTime">0:00</span>
                                <span>/</span>
                                <span id="totalDuration">{{ $episode['duration'] }}</span>
                            </div>
                        </div>
                        
                        <!-- SoundCloud-like Waveform -->
                        <div class="waveform-section">
                            <div class="waveform-container" id="waveformContainer">
                                <div class="waveform-bars" id="waveformBars">
                                    <!-- Waveform bars will be generated by JavaScript -->
                                </div>
                                <div class="waveform-overlay" id="waveformOverlay">
                                    <div class="play-overlay" id="playOverlay">
                                        <div class="play-button-overlay">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="loading-overlay" id="loadingOverlay">
                                        <div class="loading-bars">
                                            <div class="loading-bar"></div>
                                            <div class="loading-bar"></div>
                                            <div class="loading-bar"></div>
                                            <div class="loading-bar"></div>
                                            <div class="loading-bar"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="time-display">
                                    <span id="currentTimeDisplay">0:00</span>
                                    <span id="durationDisplay">{{ $episode['duration'] }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Volume Section -->
                        <div class="volume-section">
                            <button class="volume-button" id="volumeButton">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                                </svg>
                            </button>
                            <input type="range" class="volume-slider" id="volumeSlider" min="0" max="100" value="80">
                            <span id="volumeDisplay">80</span>
                        </div>
                    </div>
                    
                    <audio id="audioElement" preload="metadata" style="display: none;">
                        <source src="${this.episode.url}" type="audio/mpeg">
                        <source src="${this.episode.url}" type="audio/mp4">
                        Your browser does not support the audio element.
                    </audio>
                `;
            }
            
            setupAudio() {
                this.audio = document.getElementById('audioElement');
                
                // Configure for progressive streaming
                this.audio.preload = 'metadata';
                this.audio.crossOrigin = 'anonymous';
                this.audio.volume = this.volume / 100;
                
                // Generate waveform bars
                this.generateWaveform();
                
                // Force load metadata immediately
                this.audio.load();
                
                // Audio event listeners
                this.audio.addEventListener('loadstart', () => {
                    this.loading = true;
                    this.updateUI();
                });
                
                this.audio.addEventListener('loadedmetadata', () => {
                    this.duration = this.audio.duration;
                    this.loading = false;
                    const playButton = document.getElementById('playButton');
                    if (playButton) {
                        playButton.disabled = false;
                    }
                    this.updateUI();
                });
                
                this.audio.addEventListener('timeupdate', () => {
                    this.currentTime = this.audio.currentTime;
                    this.updateProgress();
                    this.updateWaveform();
                });
                
                this.audio.addEventListener('ended', () => {
                    this.isPlaying = false;
                    this.currentTime = 0;
                    this.updateUI();
                });
                
                this.audio.addEventListener('error', (e) => {
                    this.error = 'Failed to load audio';
                    this.loading = false;
                    console.error('Audio error:', e);
                    
                    // Enable play button even on error for retry attempts
                    const playButton = document.getElementById('playButton');
                    if (playButton) {
                        playButton.disabled = false;
                    }
                    
                    this.updateUI();
                    
                    // Try to reload after a short delay
                    setTimeout(() => {
                        if (this.audio && this.error) {
                            console.log('Retrying audio load...');
                            this.audio.load();
                        }
                    }, 2000);
                });
                
                this.audio.addEventListener('canplay', () => {
                    this.loading = false;
                    const playButton = document.getElementById('playButton');
                    if (playButton) {
                        playButton.disabled = false;
                    }
                    this.updateUI();
                });
                
                this.audio.addEventListener('canplaythrough', () => {
                    this.loading = false;
                    const playButton = document.getElementById('playButton');
                    if (playButton) {
                        playButton.disabled = false;
                    }
                    this.updateUI();
                });
                
                this.audio.addEventListener('play', () => {
                    this.isPlaying = true;
                    this.updateUI();
                });
                
                this.audio.addEventListener('pause', () => {
                    this.isPlaying = false;
                    this.updateUI();
                });
            }
            
            bindEvents() {
                // Play/pause button
                document.getElementById('playButton').addEventListener('click', () => {
                    this.togglePlay();
                });
                
                // Waveform click for seeking
                document.getElementById('waveformContainer').addEventListener('click', (e) => {
                    if (!this.duration || !this.totalBars) return;
                    
                    const rect = e.currentTarget.getBoundingClientRect();
                    const percent = (e.clientX - rect.left) / rect.width;
                    this.seek(percent * this.duration);
                });
                
                // Waveform hover effects
                const waveformContainer = document.getElementById('waveformContainer');
                waveformContainer.addEventListener('mousemove', (e) => {
                    this.handleWaveformHover(e);
                });
                
                waveformContainer.addEventListener('mouseleave', () => {
                    this.clearWaveformHover();
                });
                
                // Volume controls
                document.getElementById('volumeSlider').addEventListener('input', (e) => {
                    this.setVolume(parseInt(e.target.value));
                });
                
                document.getElementById('volumeButton').addEventListener('click', () => {
                    this.toggleMute();
                });
                
                // Share button
                window.shareEpisode = () => {
                    if (navigator.share) {
                        navigator.share({
                            title: this.episode.title,
                            text: 'Listen to this episode from Tech Weekly Podcast',
                            url: window.location.href
                        });
                    } else {
                        // Fallback: copy to clipboard
                        navigator.clipboard.writeText(window.location.href).then(() => {
                            alert('Episode link copied to clipboard!');
                        });
                    }
                };
            }
            
            togglePlay() {
                // Clear any previous error when user tries to play
                if (this.error) {
                    this.error = '';
                    this.updateUI();
                }
                
                if (this.loading) {
                    console.log('Audio still loading, please wait...');
                    return;
                }
                
                if (this.isPlaying) {
                    this.pause();
                } else {
                    this.play();
                }
            }
            
            play() {
                if (this.audio && !this.loading) {
                    this.audio.play().then(() => {
                        this.isPlaying = true;
                        this.updateUI();
                    }).catch(e => {
                        console.error('Play failed:', e);
                        this.error = 'Playback failed';
                        this.updateUI();
                    });
                }
            }
            
            pause() {
                if (this.audio) {
                    this.audio.pause();
                    this.isPlaying = false;
                    this.updateUI();
                }
            }
            
            seek(time) {
                if (this.audio && this.duration > 0) {
                    this.audio.currentTime = Math.max(0, Math.min(time, this.duration));
                }
            }
            
            setVolume(volume) {
                this.volume = Math.max(0, Math.min(100, volume));
                if (this.audio) {
                    this.audio.volume = this.volume / 100;
                }
                
                const volumeDisplay = document.getElementById('volumeDisplay');
                const volumeSlider = document.getElementById('volumeSlider');
                
                if (volumeDisplay) {
                    volumeDisplay.textContent = this.volume;
                }
                
                if (volumeSlider) {
                    volumeSlider.value = this.volume;
                }
            }
            
            toggleMute() {
                if (this.audio) {
                    this.audio.muted = !this.audio.muted;
                    this.updateVolumeIcon();
                }
            }
            
            updateVolumeIcon() {
                const volumeButton = document.getElementById('volumeButton');
                if (!volumeButton) return;
                
                const isMuted = this.audio && this.audio.muted;
                const volume = isMuted ? 0 : this.volume;
                
                let iconPath;
                if (volume === 0) {
                    iconPath = 'M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L21.73 21 23 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z';
                } else if (volume < 50) {
                    iconPath = 'M18.5 12c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM5 9v6h4l5 5V4L9 9H5z';
                } else {
                    iconPath = 'M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z';
                }
                
                volumeButton.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="${iconPath}"/></svg>`;
            }
            
            formatTime(seconds) {
                if (isNaN(seconds)) return '0:00';
                
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = Math.floor(seconds % 60);
                return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
            }
            
            updateProgress() {
                const progressFill = document.getElementById('progressFill');
                const currentTimeDisplay = document.getElementById('currentTimeDisplay');
                const currentTime = document.getElementById('currentTime');
                
                if (progressFill && this.duration > 0) {
                    const percent = (this.currentTime / this.duration) * 100;
                    progressFill.style.width = `${percent}%`;
                }
                
                const formattedTime = this.formatTime(this.currentTime);
                if (currentTimeDisplay) {
                    currentTimeDisplay.textContent = formattedTime;
                }
                if (currentTime) {
                    currentTime.textContent = formattedTime;
                }
            }
            
            generateWaveform() {
                const waveformBars = document.getElementById('waveformBars');
                if (!waveformBars) return;
                
                const totalBars = 80;
                waveformBars.innerHTML = '';
                
                // Generate realistic waveform pattern
                for (let i = 0; i < totalBars; i++) {
                    const bar = document.createElement('div');
                    bar.className = 'waveform-bar';
                    
                    // Create realistic audio-like pattern
                    const baseHeight = 25 + Math.sin(i * 0.08) * 20;
                    const variation = Math.sin(i * 0.25) * 25 + Math.cos(i * 0.15) * 20;
                    const randomness = (Math.random() - 0.5) * 35;
                    const height = Math.max(15, Math.min(100, baseHeight + variation + randomness));
                    
                    bar.style.height = `${height}%`;
                    bar.style.width = '3px';
                    bar.dataset.index = i;
                    
                    waveformBars.appendChild(bar);
                }
                
                this.totalBars = totalBars;
            }
            
            updateWaveform() {
                if (!this.duration || !this.totalBars) return;
                
                const progress = (this.currentTime / this.duration) * 100;
                const activeBarIndex = Math.floor((progress / 100) * this.totalBars);
                
                const bars = document.querySelectorAll('.waveform-bar');
                bars.forEach((bar, index) => {
                    bar.classList.toggle('active', index <= activeBarIndex);
                    bar.classList.toggle('playing', this.isPlaying && index === activeBarIndex);
                });
                
                // Update loading overlay
                const loadingOverlay = document.getElementById('loadingOverlay');
                if (loadingOverlay) {
                    loadingOverlay.classList.toggle('active', this.loading);
                }
                
                // Update play overlay visibility
                const playOverlay = document.getElementById('playOverlay');
                if (playOverlay) {
                    playOverlay.style.display = this.isPlaying || this.loading ? 'none' : 'flex';
                }
            }
            
            handleWaveformHover(e) {
                const waveformContainer = document.getElementById('waveformContainer');
                if (!waveformContainer || !this.totalBars) return;
                
                const rect = waveformContainer.getBoundingClientRect();
                const hoverX = e.clientX - rect.left;
                const percentage = (hoverX / rect.width) * 100;
                const hoverIndex = Math.floor((percentage / 100) * this.totalBars);
                
                const bars = document.querySelectorAll('.waveform-bar');
                bars.forEach((bar, index) => {
                    if (index <= hoverIndex && !bar.classList.contains('active')) {
                        bar.style.background = 'linear-gradient(to top, #fed7aa 0%, #fdba74 100%)';
                    } else if (!bar.classList.contains('active')) {
                        bar.style.background = '';
                    }
                });
            }
            
            clearWaveformHover() {
                const bars = document.querySelectorAll('.waveform-bar');
                bars.forEach(bar => {
                    if (!bar.classList.contains('active')) {
                        bar.style.background = '';
                    }
                });
            }
            
            updateUI() {
                const playButton = document.getElementById('playButton');
                const container = document.querySelector('.embed-player');
                
                if (playButton) {
                    playButton.disabled = this.loading || !!this.error;
                    playButton.classList.toggle('playing', this.isPlaying);
                }
                
                if (container) {
                    container.classList.toggle('loading', this.loading);
                    container.classList.toggle('error', !!this.error);
                }
                
                this.updateVolumeIcon();
                this.updateWaveform();
                
                if (this.error) {
                    console.error('Player error:', this.error);
                    // Show error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error';
                    errorDiv.textContent = this.error;
                    container.appendChild(errorDiv);
                }
            }
        }
        
        // Initialize player when DOM is ready
        document.addEventListener('DOMContentLoaded', () => {
            new EmbedAudioPlayer(episodeData);
        });
    </script>
</body>
</html>