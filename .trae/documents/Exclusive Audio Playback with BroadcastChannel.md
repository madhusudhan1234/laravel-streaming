## Overview
Implement SoundCloud‑style exclusive playback across all players, including embedded iframes on the same origin, using the BroadcastChannel API. When one player starts, others pause. Leverage and unify the existing GlobalAudioManager.

## Current State
- Global manager already exists: useGlobalAudioManager and GlobalAudioManager with BroadcastChannel 'audio-player-sync' [useGlobalAudioManager.ts](file:///Users/madsub/Documents/Work/Own/laravel-streaming/resources/js/composables/useGlobalAudioManager.ts).
- Embed player uses it: calls notifyPlay/notifyPause/notifyStop and unregisters on unmount [EmbedPlayer.vue](file:///Users/madsub/Documents/Work/Own/laravel-streaming/resources/js/components/EmbedPlayer.vue#L248-L256, L292-L306, L431-L437).
- Main player path: Home page uses useAudioPlayer (which integrates the manager) [useAudioPlayer.ts](file:///Users/madsub/Documents/Work/Own/laravel-streaming/resources/js/composables/useAudioPlayer.ts#L29-L37, L107-L117). The standalone AudioPlayer.vue uses useAudioStreaming and currently does not broadcast.

## Implementation Plan
### 1) Unify all players to use GlobalAudioManager
- Ensure both embedded players and any in‑app players notify on play/pause/stop.
- Option A (minimal change): switch AudioPlayer.vue to use useAudioPlayer instead of useAudioStreaming.
- Option B (keep useAudioStreaming): add audioManager integration (notifyPlay/notifyPause/notifyStop) in useAudioStreaming event handlers.

### 2) Broadcast on play; pause others
- On play: post { type: 'PLAY', playerId, episodeId, timestamp } over BroadcastChannel.
- On receipt from other frames: pause all local players (already in setupMessageListener).
- Locally: pauseOtherPlayers except current (already implemented).

### 3) Unique player identity
- Generate a per‑instance ID in registerPlayer; keep per‑component pause callback registered.
- Use episode id in the message for optional telemetry.

### 4) Cleanup
- On component unmount: call audioManager.unregister() (already in useAudioPlayer and EmbedPlayer).
- Channel lifecycle: keep BroadcastChannel open per frame (singleton). Optionally add auto‑close when listeners.size === 0, or close on window beforeunload to prevent leaks.

### 5) Safety & UX polish
- Debounce rapid play toggles to avoid thrash on broadcast.
- Ignore self‑origin messages via local listener map (already present).
- Handle autoplay attempts gracefully (already done in EmbedPlayer).

### 6) Validation
- Create a simple test page rendering two players; start one and confirm the other pauses (same frame).
- Open the embed in two iframes on the same origin; start one and confirm the other pauses.

## Files to Adjust
- AudioPlayer.vue: switch to useAudioPlayer or wire in audioManager notifications.
- useAudioStreaming.ts (if Option B): inject notifyPlay/Pause/Stop into event handlers.
- Optionally: enhance GlobalAudioManager to close BroadcastChannel when no listeners or on beforeunload.

## Deliverable
- Exclusive playback across all players and embeds using BroadcastChannel, with clean unregistration on unmount and consistent behavior matching SoundCloud.

Please confirm Option A (switch AudioPlayer.vue) or Option B (augment useAudioStreaming) and I’ll implement immediately.