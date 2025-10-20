/**
 * Global Audio Manager
 * 
 * Ensures only one audio player plays at a time across all embeds and main application.
 * Uses BroadcastChannel API for cross-frame communication.
 */

interface AudioManagerMessage {
    type: 'PLAY' | 'PAUSE' | 'STOP';
    playerId: string;
    episodeId?: number;
    timestamp: number;
}

class GlobalAudioManager {
    private static instance: GlobalAudioManager;
    private channel: BroadcastChannel;
    private currentPlayerId: string | null = null;
    private listeners: Map<string, () => void> = new Map();

    private constructor() {
        // Create BroadcastChannel for cross-frame communication
        this.channel = new BroadcastChannel('audio-player-sync');
        this.setupMessageListener();
    }

    public static getInstance(): GlobalAudioManager {
        if (!GlobalAudioManager.instance) {
            GlobalAudioManager.instance = new GlobalAudioManager();
        }
        return GlobalAudioManager.instance;
    }

    /**
     * Register a player with the global manager
     */
    public registerPlayer(playerId: string, pauseCallback: () => void): void {
        this.listeners.set(playerId, pauseCallback);
    }

    /**
     * Unregister a player from the global manager
     */
    public unregisterPlayer(playerId: string): void {
        this.listeners.delete(playerId);
        if (this.currentPlayerId === playerId) {
            this.currentPlayerId = null;
        }
    }

    /**
     * Notify that a player started playing
     */
    public notifyPlay(playerId: string, episodeId?: number): void {
        // Pause all other players locally
        this.pauseOtherPlayers(playerId);
        
        // Set current player
        this.currentPlayerId = playerId;

        // Broadcast to other frames/windows
        this.broadcastMessage({
            type: 'PLAY',
            playerId,
            episodeId,
            timestamp: Date.now()
        });
    }

    /**
     * Notify that a player paused
     */
    public notifyPause(playerId: string): void {
        if (this.currentPlayerId === playerId) {
            this.currentPlayerId = null;
        }

        this.broadcastMessage({
            type: 'PAUSE',
            playerId,
            timestamp: Date.now()
        });
    }

    /**
     * Notify that a player stopped
     */
    public notifyStop(playerId: string): void {
        if (this.currentPlayerId === playerId) {
            this.currentPlayerId = null;
        }

        this.broadcastMessage({
            type: 'STOP',
            playerId,
            timestamp: Date.now()
        });
    }

    /**
     * Check if a specific player is currently playing
     */
    public isCurrentPlayer(playerId: string): boolean {
        return this.currentPlayerId === playerId;
    }

    /**
     * Get the currently playing player ID
     */
    public getCurrentPlayerId(): string | null {
        return this.currentPlayerId;
    }

    /**
     * Pause all other players except the specified one
     */
    private pauseOtherPlayers(exceptPlayerId: string): void {
        this.listeners.forEach((pauseCallback, playerId) => {
            if (playerId !== exceptPlayerId) {
                pauseCallback();
            }
        });
    }

    /**
     * Setup message listener for cross-frame communication
     */
    private setupMessageListener(): void {
        this.channel.addEventListener('message', (event: MessageEvent<AudioManagerMessage>) => {
            const message = event.data;
            
            // Ignore messages from the same frame (prevent loops)
            if (message.playerId && this.listeners.has(message.playerId)) {
                return;
            }

            switch (message.type) {
                case 'PLAY':
                    // Pause all local players when another frame starts playing
                    this.pauseOtherPlayers('');
                    this.currentPlayerId = null; // Clear local current player
                    break;
                
                case 'PAUSE':
                case 'STOP':
                    // No action needed for pause/stop from other frames
                    break;
            }
        });
    }

    /**
     * Broadcast message to other frames
     */
    private broadcastMessage(message: AudioManagerMessage): void {
        try {
            this.channel.postMessage(message);
        } catch (error) {
            console.warn('Failed to broadcast audio manager message:', error);
        }
    }

    /**
     * Cleanup resources
     */
    public destroy(): void {
        this.channel.close();
        this.listeners.clear();
        this.currentPlayerId = null;
    }
}

/**
 * Composable for using the Global Audio Manager
 */
export function useGlobalAudioManager() {
    const manager = GlobalAudioManager.getInstance();

    /**
     * Generate a unique player ID
     */
    const generatePlayerId = (): string => {
        return `player-${Math.random().toString(36).substr(2, 9)}-${Date.now()}`;
    };

    /**
     * Register a player and get management functions
     */
    const registerPlayer = (pauseCallback: () => void) => {
        const playerId = generatePlayerId();
        
        manager.registerPlayer(playerId, pauseCallback);

        const notifyPlay = (episodeId?: number) => {
            manager.notifyPlay(playerId, episodeId);
        };

        const notifyPause = () => {
            manager.notifyPause(playerId);
        };

        const notifyStop = () => {
            manager.notifyStop(playerId);
        };

        const isCurrentPlayer = () => {
            return manager.isCurrentPlayer(playerId);
        };

        const unregister = () => {
            manager.unregisterPlayer(playerId);
        };

        return {
            playerId,
            notifyPlay,
            notifyPause,
            notifyStop,
            isCurrentPlayer,
            unregister
        };
    };

    return {
        registerPlayer,
        getCurrentPlayerId: () => manager.getCurrentPlayerId()
    };
}

export default GlobalAudioManager;