# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

All commands run inside Docker containers. The dev environment is `docker compose up -d`.

### Backend (PHP/Laravel)
```bash
docker compose exec app php artisan test                            # run all tests
docker compose exec app php artisan test --filter=EpisodeModelTest  # run single test
docker compose exec app php artisan migrate
docker compose exec app php artisan cache:clear
docker compose exec app ./vendor/bin/pint                           # PHP code formatting
```

### Frontend (Node/Vite)
```bash
docker compose exec node npm run dev          # Vite dev server (HMR)
docker compose exec node npm run build        # production build
docker compose exec node npm run type-check   # vue-tsc type checking
docker compose exec node npm run lint         # ESLint (auto-fix)
docker compose exec node npm run format       # Prettier
```

### Composer scripts (local, without Docker)
```bash
composer dev       # starts Laravel server + queue + pail + Vite concurrently
composer test      # clears config then runs artisan test
```

## Architecture

**Stack:** Laravel 12 + PHP 8.4 · Inertia.js v2 · Vue 3 (Composition API + TypeScript) · Tailwind CSS 4 · Vite 7 · MySQL + Redis

### Data flow
Episodes are stored in two ways:
1. **Production:** JSON files pushed to a GitHub repo (via `AppendEpisode` / `DeleteEpisodeFromGithub` jobs), then synced into Redis (`episodes:all` key) by `SyncEpisodesToRedis`. The `EpisodeController` reads from Redis first, falls back to the MySQL `episodes` table.
2. **Testing:** Uses the MySQL `episodes` table directly (in-memory SQLite via `phpunit.xml`). The `app()->environment('testing')` guard switches between the two paths throughout the controllers.

### Audio storage
Upload target is determined at runtime: Cloudflare R2 (`Storage::disk('r2')`) if configured, otherwise `public/audios/`. The `AudioStreamController` serves audio with HTTP range request support for seeking.

### Embed system
`/embed/{id}` serves a standalone lightweight page (`EmbedPlayer.vue`) that can be iframed on external sites. Multiple embed players coordinate exclusive playback via the browser's `BroadcastChannel` API — when one starts playing, all others pause.

### Routing
- Public routes: `routes/web.php` (home, embed, API endpoints for episodes and audio streaming)
- Admin routes: `routes/admin.php` — scoped to a separate domain (`config('domains.admin')`), protected by `auth` + `verified` middleware. Handles episode CRUD and sync.
- Auth routes: `routes/auth.php` (Fortify + Socialite)
- Settings routes: `routes/settings.php`

### Frontend structure
```
resources/js/
├── app.ts / ssr.ts        # Inertia entry points
├── pages/                 # Inertia page components (PascalCase)
├── components/            # Reusable Vue components (PascalCase)
├── composables/           # useXxx.ts — audio player logic lives here
├── layouts/               # AppLayout, AuthLayout
└── types/                 # TypeScript interfaces
```

Key composables: `useAudioPlayer.ts`, `useGlobalAudioManager.ts`, `useAudioStreaming.ts`.

### Jobs (Laravel Horizon queue)
- `AppendEpisode` — writes episode JSON to GitHub
- `DeleteEpisodeFromGithub` — removes episode JSON from GitHub
- `SyncEpisodesToRedis` — fetches all GitHub episode JSONs and refreshes the `episodes:all` Redis key

### Testing notes
- Only `tests/Unit/` exists (no Feature tests). `phpunit.xml` uses in-memory SQLite and the `testing` environment.
- Tests bypass GitHub/Redis logic via the `app()->environment('testing')` guard.
