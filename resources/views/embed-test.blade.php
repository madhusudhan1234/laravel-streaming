<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Embed Player Testing - Tech Weekly</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #333;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            margin: 0 0 10px 0;
            color: #1a1a1a;
            font-size: 28px;
            font-weight: 700;
        }

        .header p {
            margin: 0;
            color: #666;
            font-size: 16px;
        }

        .controls {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .controls h2 {
            margin: 0 0 15px 0;
            color: #1a1a1a;
            font-size: 20px;
            font-weight: 600;
        }

        .control-group {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .control-group:last-child {
            margin-bottom: 0;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #ff6b35;
            color: white;
        }

        .btn-primary:hover {
            background: #e55a2b;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .size-selector {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .size-selector label {
            font-weight: 600;
            color: #333;
        }

        .size-selector select {
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: white;
        }

        .metrics {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .metrics h2 {
            margin: 0 0 15px 0;
            color: #1a1a1a;
            font-size: 20px;
            font-weight: 600;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .metric-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }

        .metric-value {
            font-size: 24px;
            font-weight: 700;
            color: #ff6b35;
            margin-bottom: 5px;
        }

        .metric-label {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }

        .episodes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .episode-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .episode-card:hover {
            transform: translateY(-2px);
        }

        .episode-header {
            margin-bottom: 15px;
        }

        .episode-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0 0 5px 0;
        }

        .episode-id {
            font-size: 14px;
            color: #666;
            font-family: 'SF Mono', Monaco, monospace;
        }

        .embed-container {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .embed-container.small {
            width: 300px;
            height: 120px;
        }

        .embed-container.medium {
            width: 100%;
            height: 120px;
        }

        .embed-container.large {
            width: 100%;
            height: 150px;
        }

        .embed-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .episode-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .performance-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .performance-indicator.loading {
            background: #ffc107;
            animation: pulse 1s infinite;
        }

        .performance-indicator.loaded {
            background: #28a745;
        }

        .performance-indicator.error {
            background: #dc3545;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .load-time {
            font-size: 12px;
            color: #666;
            font-family: 'SF Mono', Monaco, monospace;
            margin-left: auto;
        }

        .no-episodes {
            text-align: center;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .no-episodes h3 {
            color: #666;
            margin-bottom: 10px;
        }

        .no-episodes p {
            color: #999;
        }

        @media (max-width: 768px) {
            .episodes-grid {
                grid-template-columns: 1fr;
            }
            
            .control-group {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .embed-container.small {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎧 Embed Player Testing</h1>
            <p>Test and monitor the performance of all embedded audio players</p>
        </div>

        <div class="controls">
            <h2>Testing Controls</h2>
            
            <div class="control-group">
                <button class="btn btn-primary" onclick="playAllPlayers()">
                    ▶️ Play All Players
                </button>
                <button class="btn btn-secondary" onclick="pauseAllPlayers()">
                    ⏸️ Pause All Players
                </button>
                <button class="btn btn-danger" onclick="stopAllPlayers()">
                    ⏹️ Stop All Players
                </button>
                <button class="btn btn-secondary" onclick="refreshAllPlayers()">
                    🔄 Refresh All Players
                </button>
            </div>

            <div class="control-group">
                <div class="size-selector">
                    <label for="playerSize">Player Size:</label>
                    <select id="playerSize" onchange="changePlayerSize(this.value)">
                        <option value="medium">Medium (100% width)</option>
                        <option value="small">Small (300px width)</option>
                        <option value="large">Large (100% width, 150px height)</option>
                    </select>
                </div>
                <button class="btn btn-secondary" onclick="measurePerformance()">
                    📊 Measure Performance
                </button>
            </div>
        </div>

        <div class="metrics">
            <h2>Performance Metrics</h2>
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-value" id="totalPlayers">{{ count($episodes) }}</div>
                    <div class="metric-label">Total Players</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="loadedPlayers">0</div>
                    <div class="metric-label">Loaded Players</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="avgLoadTime">-</div>
                    <div class="metric-label">Avg Load Time (ms)</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="memoryUsage">-</div>
                    <div class="metric-label">Memory Usage (MB)</div>
                </div>
            </div>
        </div>

        @if(count($episodes) > 0)
            <div class="episodes-grid">
                @foreach($episodes as $episode)
                    <div class="episode-card" data-episode-id="{{ $episode->id }}">
                        <div class="episode-header">
                            <h3 class="episode-title">{{ $episode->title }}</h3>
                            <div class="episode-id">Episode ID: {{ $episode->id }}</div>
                        </div>

                        <div class="embed-container medium" id="embed-{{ $episode->id }}">
                            <iframe 
                                src="{{ url('/embed/' . $episode->id) }}" 
                                title="{{ $episode->title }}"
                                allow="autoplay"
                                loading="lazy"
                                onload="handleIframeLoad({{ $episode->id }})"
                                onerror="handleIframeError({{ $episode->id }})">
                            </iframe>
                        </div>

                        <div class="episode-actions">
                            <span class="performance-indicator loading" id="indicator-{{ $episode->id }}"></span>
                            <span>Status: <span id="status-{{ $episode->id }}">Loading...</span></span>
                            <span class="load-time" id="loadtime-{{ $episode->id }}">-</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="no-episodes">
                <h3>No Episodes Found</h3>
                <p>Please add some episodes to test the embed players.</p>
                <a href="{{ url('/dashboard/episodes') }}" class="btn btn-primary">Add Episodes</a>
            </div>
        @endif
    </div>

    <script>
        // Performance tracking
        const loadTimes = {};
        const loadStartTimes = {};
        let loadedCount = 0;

        // Initialize load start times
        document.addEventListener('DOMContentLoaded', function() {
            const episodes = @json($episodes);
            episodes.forEach(episode => {
                loadStartTimes[episode.id] = Date.now();
            });
            
            // Update metrics periodically
            setInterval(updateMetrics, 1000);
        });

        function handleIframeLoad(episodeId) {
            const loadTime = Date.now() - loadStartTimes[episodeId];
            loadTimes[episodeId] = loadTime;
            loadedCount++;

            // Update UI
            const indicator = document.getElementById(`indicator-${episodeId}`);
            const status = document.getElementById(`status-${episodeId}`);
            const loadTimeElement = document.getElementById(`loadtime-${episodeId}`);

            indicator.className = 'performance-indicator loaded';
            status.textContent = 'Loaded';
            loadTimeElement.textContent = `${loadTime}ms`;

            updateMetrics();
        }

        function handleIframeError(episodeId) {
            const indicator = document.getElementById(`indicator-${episodeId}`);
            const status = document.getElementById(`status-${episodeId}`);

            indicator.className = 'performance-indicator error';
            status.textContent = 'Error';

            updateMetrics();
        }

        function updateMetrics() {
            document.getElementById('loadedPlayers').textContent = loadedCount;
            
            // Calculate average load time
            const times = Object.values(loadTimes);
            if (times.length > 0) {
                const avgTime = Math.round(times.reduce((a, b) => a + b, 0) / times.length);
                document.getElementById('avgLoadTime').textContent = avgTime;
            }

            // Memory usage (if available)
            if (performance.memory) {
                const memoryMB = Math.round(performance.memory.usedJSHeapSize / 1024 / 1024);
                document.getElementById('memoryUsage').textContent = memoryMB;
            }
        }

        function changePlayerSize(size) {
            const containers = document.querySelectorAll('.embed-container');
            containers.forEach(container => {
                container.className = `embed-container ${size}`;
            });
        }

        function playAllPlayers() {
            const iframes = document.querySelectorAll('.embed-container iframe');
            iframes.forEach(iframe => {
                try {
                    // Try to send play message to iframe
                    iframe.contentWindow.postMessage({action: 'play'}, '*');
                } catch (e) {
                    console.log('Could not send play message to iframe:', e);
                }
            });
        }

        function pauseAllPlayers() {
            const iframes = document.querySelectorAll('.embed-container iframe');
            iframes.forEach(iframe => {
                try {
                    iframe.contentWindow.postMessage({action: 'pause'}, '*');
                } catch (e) {
                    console.log('Could not send pause message to iframe:', e);
                }
            });
        }

        function stopAllPlayers() {
            const iframes = document.querySelectorAll('.embed-container iframe');
            iframes.forEach(iframe => {
                try {
                    iframe.contentWindow.postMessage({action: 'stop'}, '*');
                } catch (e) {
                    console.log('Could not send stop message to iframe:', e);
                }
            });
        }

        function refreshAllPlayers() {
            const iframes = document.querySelectorAll('.embed-container iframe');
            iframes.forEach(iframe => {
                iframe.src = iframe.src;
            });
            
            // Reset metrics
            loadedCount = 0;
            Object.keys(loadTimes).forEach(key => delete loadTimes[key]);
            
            // Reset UI indicators
            const indicators = document.querySelectorAll('.performance-indicator');
            const statuses = document.querySelectorAll('[id^="status-"]');
            const loadTimeElements = document.querySelectorAll('[id^="loadtime-"]');
            
            indicators.forEach(indicator => {
                indicator.className = 'performance-indicator loading';
            });
            
            statuses.forEach(status => {
                status.textContent = 'Loading...';
            });
            
            loadTimeElements.forEach(element => {
                element.textContent = '-';
            });
            
            updateMetrics();
        }

        function measurePerformance() {
            console.log('Performance Measurement Results:');
            console.log('Load Times:', loadTimes);
            console.log('Average Load Time:', Object.values(loadTimes).reduce((a, b) => a + b, 0) / Object.values(loadTimes).length);
            console.log('Loaded Players:', loadedCount);
            
            if (performance.memory) {
                console.log('Memory Usage:', {
                    used: Math.round(performance.memory.usedJSHeapSize / 1024 / 1024) + ' MB',
                    total: Math.round(performance.memory.totalJSHeapSize / 1024 / 1024) + ' MB',
                    limit: Math.round(performance.memory.jsHeapSizeLimit / 1024 / 1024) + ' MB'
                });
            }
            
            alert('Performance data logged to console. Check browser developer tools.');
        }

        // Listen for messages from iframes (for future cross-frame communication)
        window.addEventListener('message', function(event) {
            // Handle messages from embed players if needed
            console.log('Message from embed player:', event.data);
        });
    </script>
</body>
</html>