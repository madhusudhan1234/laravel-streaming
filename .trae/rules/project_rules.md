# Laravel + Inertia.js + Vue.js Docker Project Rules

## 1. Project Overview
This is a Laravel application with Inertia.js and Vue.js frontend, running in Docker containers. The application serves an audio streaming platform with embeddable players.

## 2. Development Environment

### 2.1 Docker Setup
- **Primary Development**: Use `docker compose up -d` to start all services
- **Application Access**: Main UI accessible at `http://localhost` (port 80)
- **Vite Dev Server**: Frontend development server at `http://localhost:5173`
- **Database**: MySQL accessible at `localhost:3306`
- **Mail Testing**: MailHog at `http://localhost:8025`

### 2.2 Container Services
```yaml
- app: PHP 8.4-FPM Laravel application
- nginx: Web server proxy
- mysql: Database server
- redis: Cache and session storage
- node: Node.js for Vite development server
- mailhog: Email testing
```

### 2.3 Development Commands
```bash
# Start development environment
docker compose up -d

# Stop environment
docker compose down

# View logs
docker compose logs -f [service_name]

# Execute Laravel commands
docker compose exec app php artisan [command]

# Execute Node commands
docker compose exec node npm [command]
```

## 3. Technology Stack

### 3.1 Backend
- **Framework**: Laravel 11.x
- **PHP Version**: 8.4
- **Database**: MySQL 8.0
- **Cache**: Redis
- **Authentication**: Laravel Fortify

### 3.2 Frontend
- **Framework**: Vue.js 3.x with Composition API
- **SPA Solution**: Inertia.js
- **Build Tool**: Vite
- **Styling**: Tailwind CSS
- **Language**: TypeScript
- **Components**: Single File Components (.vue)

## 4. File Structure & Organization

### 4.1 Laravel Structure
```
app/
├── Http/Controllers/     # API and web controllers
├── Models/              # Eloquent models
└── Providers/           # Service providers

resources/
├── js/                  # Vue.js application
│   ├── app.ts          # Main application entry
│   ├── ssr.ts          # Server-side rendering
│   ├── components/     # Reusable Vue components
│   ├── pages/          # Inertia.js pages
│   ├── layouts/        # Page layouts
│   ├── composables/    # Vue composables
│   ├── types/          # TypeScript definitions
│   └── lib/            # Utility functions
├── css/                # Stylesheets
└── views/              # Blade templates (minimal)

routes/
├── web.php             # Web routes
├── auth.php            # Authentication routes
└── settings.php        # Settings routes
```

### 4.2 Frontend Organization
- **Pages**: Use PascalCase (e.g., `HomePage.vue`, `EpisodeDetail.vue`)
- **Components**: Use PascalCase with descriptive names (e.g., `AudioPlayer.vue`)
- **Composables**: Use camelCase with `use` prefix (e.g., `useAudioPlayer.ts`)
- **Types**: Use PascalCase interfaces (e.g., `Episode.ts`, `AudioState.ts`)

## 5. Development Workflow

### 5.1 Starting Development
1. Run `docker compose up -d` to start all containers
2. Access application at `http://localhost`
3. Vite dev server provides hot reloading at `http://localhost:5173`
4. Make changes to Vue components or Laravel code
5. Changes auto-reload in browser

### 5.2 Laravel Development
```bash
# Run migrations
docker compose exec app php artisan migrate

# Create migration
docker compose exec app php artisan make:migration create_episodes_table

# Create controller
docker compose exec app php artisan make:controller EpisodeController

# Clear cache
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan view:clear
```

### 5.3 Frontend Development
```bash
# Install dependencies
docker compose exec node npm install

# Run development server
docker compose exec node npm run dev

# Build for production
docker compose exec node npm run build

# Type checking
docker compose exec node npm run type-check
```

## 6. Code Standards & Conventions

### 6.1 Laravel Conventions
- **Controllers**: Use resource controllers where appropriate
- **Models**: Use Eloquent relationships and accessors/mutators
- **Routes**: Group related routes and use route model binding
- **Validation**: Use Form Request classes for complex validation

### 6.2 Vue.js Conventions
- **Composition API**: Prefer Composition API over Options API
- **TypeScript**: Use strict typing for props, emits, and reactive data
- **Component Props**: Define with TypeScript interfaces
- **Event Handling**: Use descriptive event names

### 6.3 Inertia.js Patterns
- **Page Components**: Place in `resources/js/pages/`
- **Shared Data**: Use Inertia's shared data for global state
- **Form Handling**: Use Inertia's form helper for submissions
- **Navigation**: Use Inertia's router for SPA navigation

## 7. Asset Management

### 7.1 Vite Configuration
- **Entry Points**: `resources/js/app.ts` and `resources/js/ssr.ts`
- **Hot Reloading**: Enabled for development
- **Build Output**: `public/build/` directory
- **Asset Processing**: Handles TypeScript, Vue SFC, and CSS

### 7.2 Static Assets
- **Audio Files**: Store in `public/audios/`
- **Images**: Store in `public/images/` or use Vite's asset handling
- **Icons**: Use Heroicons or similar icon library

## 8. Database & Data Management

### 8.1 Database Setup
- **Connection**: MySQL via Docker container
- **Migrations**: Version control database schema
- **Seeders**: Use for development data
- **Models**: Follow Laravel naming conventions

### 8.2 Data Storage
- **Episode Metadata**: JSON file in `storage/app/episodes.json`
- **Audio Files**: Static files in `public/audios/`
- **User Data**: MySQL database tables

## 9. Testing

### 9.1 Backend Testing
```bash
# Run PHP tests
docker compose exec app php artisan test

# Run specific test
docker compose exec app php artisan test --filter=EpisodeTest

# Generate test coverage
docker compose exec app php artisan test --coverage
```

### 9.2 Frontend Testing
```bash
# Run frontend tests (when configured)
docker compose exec node npm run test

# Run type checking
docker compose exec node npm run type-check
```

## 10. Code Quality

### 10.1 PHP Code Quality
- **Laravel Pint**: Automatic code formatting
- **PHPStan**: Static analysis (if configured)
- **PHP CS Fixer**: Code style fixing

### 10.2 Frontend Code Quality
- **ESLint**: JavaScript/TypeScript linting
- **Prettier**: Code formatting
- **TypeScript**: Strict type checking

### 10.3 Quality Commands
```bash
# Format PHP code
docker compose exec app ./vendor/bin/pint

# Lint frontend code
docker compose exec node npm run lint

# Format frontend code
docker compose exec node npm run format
```

## 11. Environment Configuration

### 11.1 Environment Files
- **`.env`**: Local development configuration
- **`.env.example`**: Template for environment variables
- **Docker Environment**: Set via `compose.yml`

### 11.2 Key Environment Variables
```env
APP_NAME=Laravel
APP_ENV=local
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=laravel_streaming
DB_USERNAME=root
DB_PASSWORD=password

VITE_APP_NAME="${APP_NAME}"
```

## 12. Deployment & Production

### 12.1 Production Build
```bash
# Build frontend assets
docker compose exec node npm run build

# Optimize Laravel
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

### 12.2 Production Considerations
- **Asset Compilation**: Use `npm run build` for optimized assets
- **Caching**: Enable Laravel caching in production
- **Environment**: Set `APP_ENV=production`
- **Debug**: Disable debug mode in production

## 13. Troubleshooting

### 13.1 Common Issues
- **Port Conflicts**: Ensure ports 80, 3306, 5173 are available
- **Permission Issues**: Check Docker volume permissions
- **Node Modules**: Use Docker volume for `node_modules` to avoid platform issues
- **Cache Issues**: Clear Laravel and browser caches

### 13.2 Debug Commands
```bash
# Check container status
docker compose ps

# View container logs
docker compose logs -f app
docker compose logs -f node

# Access container shell
docker compose exec app bash
docker compose exec node sh

# Check Laravel configuration
docker compose exec app php artisan config:show
```

## 14. Audio Streaming Specific Rules

### 14.1 Audio File Management
- **Location**: All audio files in `public/audios/`
- **Formats**: Support MP3 and M4A formats
- **Naming**: Use descriptive filenames (e.g., `first-episode.m4a`)
- **Metadata**: Store in JSON file for easy management

### 14.2 Episode Data Structure
```json
{
  "episodes": [
    {
      "id": 1,
      "title": "Episode 1 - Madhu Sudhan Subedi Tech Weekly",
      "filename": "first-episode.m4a",
      "url": "/audios/first-episode.m4a"
    }
  ]
}
```

### 14.3 Embed Feature
- **Lightweight Player**: Minimal HTML/CSS/JS for embedding
- **Iframe Support**: Allow embedding via iframe
- **Responsive**: Ensure embed players work on all devices
- **Cross-Origin**: Configure CORS for external embedding

This document serves as the comprehensive guide for developing and maintaining the Laravel + Inertia.js + Vue.js audio streaming application in Docker environment.