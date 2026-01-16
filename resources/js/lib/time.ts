/**
 * Time formatting utilities
 */

/**
 * Format seconds into a human-readable time string (M:SS or H:MM:SS)
 */
export function formatTime(seconds: number): string {
    if (isNaN(seconds) || seconds < 0) return '0:00';

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);

    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    return `${minutes}:${secs.toString().padStart(2, '0')}`;
}

/**
 * Parse a duration string (MM:SS or H:MM:SS) into seconds
 */
export function parseTime(timeStr: string): number | null {
    if (!timeStr || typeof timeStr !== 'string') return null;

    const parts = timeStr.split(':').map(Number);

    if (parts.some(isNaN)) return null;

    if (parts.length === 2) {
        const [minutes, seconds] = parts;
        return minutes * 60 + seconds;
    }

    if (parts.length === 3) {
        const [hours, minutes, seconds] = parts;
        return hours * 3600 + minutes * 60 + seconds;
    }

    return null;
}
