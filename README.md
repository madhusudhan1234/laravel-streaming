# Laravel Audio Streaming Platform

A modern audio streaming platform built with Laravel, Inertia.js, and Vue.js, featuring embeddable audio players and a clean, responsive interface.

- [https://weekly.madhusudhansubedi.com.np](https://weekly.madhusudhansubedi.com.np)
- [https://madhusudhansubedi.com.np/weekly](https://madhusudhansubedi.com.np/weekly)

## 🎵 Project Overview

This application is a full-featured audio streaming platform that allows users to:
- Stream audio episodes with a beautiful, responsive interface
- Embed audio players on external websites
- Manage episode metadata and audio files
- Enjoy a SoundCloud-inspired waveform visualization
- Access content through both web interface and embeddable widgets

## 🚀 Technology Stack

### Backend
- **Laravel 12.x** - PHP web application framework
- **PHP 8.4** - Server-side scripting language
- **MySQL 8.0** - Database management system
- **Redis** - Caching and session storage
- **Laravel Fortify** - Authentication scaffolding

### Frontend
- **Vue.js 3.x** - Progressive JavaScript framework with Composition API
- **Inertia.js** - Modern monolith approach for SPAs
- **TypeScript** - Typed JavaScript for better development experience
- **Tailwind CSS** - Utility-first CSS framework
- **Vite** - Fast build tool and development server

### Infrastructure
- **Docker & Docker Compose** - Containerized development environment
- **Nginx** - Web server and reverse proxy
- **MailHog** - Email testing tool for development

## 📋 Prerequisites

Before you begin, ensure you have the following installed on your system:

- **Docker** (version 20.10 or higher)
- **Docker Compose** (version 2.0 or higher)
- **Git** (for cloning the repository)

### System Requirements
- **macOS**, **Linux**, or **Windows** with WSL2
- **Minimum 4GB RAM** (8GB recommended)
- **At least 2GB free disk space**

## 🛠️ Installation & Setup

### 1. Clone the Repository

```bash
git clone <repository-url>
cd laravel-streaming
```

### 2. Environment Configuration

Copy the example environment file and configure it:

```bash
cp .env.example .env
```

The default `.env` configuration should work for Docker development, but you can customize these key variables:

```env
APP_NAME="Laravel Streaming"
APP_ENV=local
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=laravel_streaming
DB_USERNAME=root
DB_PASSWORD=password

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

VITE_APP_NAME="${APP_NAME}"
```

### 3. Build and Start Docker Containers

```bash
# Build and start all services
docker compose up -d

# View logs (optional)
docker compose logs -f
```

This will start the following services:
- **app**: PHP 8.4-FPM Laravel application
- **nginx**: Web server (accessible at http://localhost)
- **mysql**: Database server
- **redis**: Cache and session storage
- **node**: Node.js for Vite development server (http://localhost:5173)
- **mailhog**: Email testing interface (http://localhost:8025)

### 4. Install Dependencies

```bash
# Install PHP dependencies
docker compose exec app composer install

# Install Node.js dependencies
docker compose exec node npm install
```

### 5. Generate Application Key

```bash
docker compose exec app php artisan key:generate
```

### 6. Run Database Migrations

```bash
docker compose exec app php artisan migrate
```

### 7. Start the Development Server

```bash
# Start Vite development server for hot reloading
docker compose exec node npm run dev
```

### 8. Access the Application

- **Main Application**: http://localhost
- **Vite Dev Server**: http://localhost:5173 (for hot reloading)
- **MailHog**: http://localhost:8025 (email testing)
- **Database**: localhost:3306 (external access)

## 🏗️ Project Structure

```
laravel-streaming/
├── app/                          # Laravel application logic
│   ├── Http/Controllers/         # API and web controllers
│   ├── Models/                   # Eloquent models
│   └── Providers/               # Service providers
├── resources/
│   ├── js/                      # Vue.js frontend application
│   │   ├── app.ts              # Main application entry point
│   │   ├── ssr.ts              # Server-side rendering
│   │   ├── components/         # Reusable Vue components
│   │   ├── pages/              # Inertia.js page components
│   │   ├── layouts/            # Page layout components
│   │   ├── composables/        # Vue composition functions
│   │   ├── types/              # TypeScript type definitions
│   │   └── lib/                # Utility functions
│   ├── css/                    # Stylesheets
│   └── views/                  # Blade templates
├── routes/                     # Application routes
├── public/
│   └── audios/                 # Audio files storage
├── storage/                    # File storage and logs
├── docker/                     # Docker configuration
└── compose.yml                 # Docker Compose configuration
```

## ✨ Features

### Core Features
- **Audio Streaming**: High-quality audio playback with progress tracking
- **Waveform Visualization**: SoundCloud-inspired animated waveforms
- **Responsive Design**: Works seamlessly on desktop and mobile devices
- **Episode Management**: Easy management of audio episodes and metadata

### Embed Functionality
- **Embeddable Players**: Lightweight iframe-based audio players
- **Customizable**: Clean, white-themed design that fits any website
- **Cross-Origin Support**: Proper CORS configuration for external embedding
- **Responsive Embeds**: Embed players adapt to container sizes

### Technical Features
- **SPA Experience**: Fast navigation with Inertia.js
- **TypeScript Support**: Full type safety in frontend development
- **Hot Module Replacement**: Instant updates during development
- **Docker Development**: Consistent development environment

## 📖 Usage Examples

### Basic Development Workflow

```bash
# Start the development environment
docker compose up -d

# View application logs
docker compose logs -f app

# Run Laravel commands
docker compose exec app php artisan migrate
docker compose exec app php artisan cache:clear

# Run Node.js commands
docker compose exec node npm run dev
docker compose exec node npm run build

# Access container shells
docker compose exec app bash
docker compose exec node sh
```

### Adding New Episodes

1. Place audio files in `public/audios/` directory
2. Update episode metadata in your database or JSON configuration
3. The application will automatically serve the new episodes

### Embedding Audio Players

Use the embed functionality to add players to external websites:

```html
<iframe 
  src="http://your-domain.com/embed/1" 
  width="100%" 
  height="120" 
  frameborder="0"
  allow="autoplay">
</iframe>
```

## 🐳 Docker Services

### Application Services

| Service | Purpose | Port | Access |
|---------|---------|------|--------|
| **app** | PHP-FPM Laravel application | - | Internal |
| **nginx** | Web server and reverse proxy | 80 | http://localhost |
| **mysql** | Database server | 3306 | localhost:3306 |
| **redis** | Cache and session storage | 6379 | Internal |
| **node** | Vite development server | 5173 | http://localhost:5173 |
| **mailhog** | Email testing interface | 8025 | http://localhost:8025 |

### Service Management

```bash
# Start all services
docker compose up -d

# Stop all services
docker compose down

# Restart a specific service
docker compose restart app

# View service status
docker compose ps

# View service logs
docker compose logs -f [service_name]
```

## 🔧 Development Commands

### Laravel Commands

```bash
# Database operations
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:rollback
docker compose exec app php artisan db:seed

# Cache management
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan view:clear

# Code generation
docker compose exec app php artisan make:controller EpisodeController
docker compose exec app php artisan make:model Episode
docker compose exec app php artisan make:migration create_episodes_table
```

### Frontend Commands

```bash
# Development
docker compose exec node npm run dev          # Start Vite dev server
docker compose exec node npm run build        # Build for production
docker compose exec node npm run type-check   # TypeScript checking

# Code quality
docker compose exec node npm run lint         # ESLint checking
docker compose exec node npm run format       # Prettier formatting
```

### Testing Commands

```bash
# Backend tests
docker compose exec app php artisan test
docker compose exec app php artisan test --coverage

# Frontend tests (when configured)
docker compose exec node npm run test
```

## 🐛 Troubleshooting

### Common Issues

#### Port Conflicts
**Problem**: Ports 80, 3306, or 5173 are already in use.
**Solution**: 
```bash
# Check what's using the ports
lsof -i :80
lsof -i :3306
lsof -i :5173

# Stop conflicting services or modify ports in compose.yml
```

#### Permission Issues
**Problem**: File permission errors in Docker containers.
**Solution**:
```bash
# Fix storage permissions
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
```

#### Node Modules Issues
**Problem**: Node modules not working across different platforms.
**Solution**:
```bash
# Remove and reinstall node modules
docker compose exec node rm -rf node_modules package-lock.json
docker compose exec node npm install
```

#### Database Connection Issues
**Problem**: Cannot connect to MySQL database.
**Solution**:
```bash
# Check if MySQL container is running
docker compose ps mysql

# Check MySQL logs
docker compose logs mysql

# Restart MySQL service
docker compose restart mysql
```

#### Asset Loading Issues
**Problem**: CSS/JS assets not loading properly.
**Solution**:
```bash
# Clear Laravel caches
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear

# Rebuild frontend assets
docker compose exec node npm run build
```

### Debug Commands

```bash
# Check container status
docker compose ps

# Access container logs
docker compose logs -f app
docker compose logs -f node
docker compose logs -f mysql

# Access container shell for debugging
docker compose exec app bash
docker compose exec node sh

# Check Laravel configuration
docker compose exec app php artisan config:show
docker compose exec app php artisan route:list
```

### Performance Issues

If you experience slow performance:

1. **Increase Docker resources**: Allocate more CPU and RAM to Docker
2. **Use Docker volumes**: Ensure `node_modules` is in a Docker volume
3. **Clear caches**: Regularly clear Laravel and browser caches
4. **Check logs**: Monitor container logs for errors or warnings

## 🤝 Contributing

We welcome contributions to improve the Laravel Audio Streaming Platform!

### Development Setup

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Follow the installation steps above
4. Make your changes
5. Run tests: `docker compose exec app php artisan test`
6. Commit your changes: `git commit -m 'Add amazing feature'`
7. Push to the branch: `git push origin feature/amazing-feature`
8. Open a Pull Request

### Code Standards

- **PHP**: Follow PSR-12 coding standards
- **JavaScript/TypeScript**: Use ESLint and Prettier configurations
- **Vue.js**: Use Composition API and TypeScript
- **CSS**: Use Tailwind CSS utility classes

### Code Quality Tools

```bash
# PHP code formatting
docker compose exec app ./vendor/bin/pint

# Frontend linting and formatting
docker compose exec node npm run lint
docker compose exec node npm run format

# Type checking
docker compose exec node npm run type-check
```

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🆘 Support

If you encounter any issues or have questions:

1. Check the [Troubleshooting](#-troubleshooting) section
2. Search existing issues in the repository
3. Create a new issue with detailed information about your problem
4. Include your environment details and error logs

## 🙏 Acknowledgments

- Laravel community for the excellent framework
- Vue.js team for the reactive frontend framework
- Inertia.js for bridging Laravel and Vue.js seamlessly
- Docker community for containerization tools

---

**Happy Streaming! 🎵**
