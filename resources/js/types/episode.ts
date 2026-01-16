/**
 * Canonical Episode type
 *
 * This is the single source of truth for the Episode shape across the frontend.
 * All components and composables should import Episode from this file.
 */
export interface Episode {
    id: number;
    title: string;
    filename: string;
    url: string;
    /**
     * Duration can be either:
     * - A number (seconds) from the API when duration_seconds is available
     * - A string ("MM:SS" or "M:SS") from legacy JSON data
     */
    duration: number | string;
    /**
     * Canonical duration in seconds (preferred over duration string)
     * Available when the backend provides normalized duration
     */
    duration_seconds?: number | null;
    /**
     * Human-readable duration string (e.g., "7:11")
     * Computed from duration_seconds when available
     */
    duration_text?: string | null;
    file_size: string;
    format: string;
    published_date: string;
    description?: string | null;
    storage_disk?: string;
    created_at?: string;
    updated_at?: string;
}
