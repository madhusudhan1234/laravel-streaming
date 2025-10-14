# Laravel Streaming App Deployment Guide

This project uses [Deployer](https://deployer.org/) for automated deployment. Deployer is a deployment tool written in PHP with support for popular frameworks out of the box.

## Prerequisites

- PHP 8.4+ on your local machine
- Composer installed
- SSH access to your production server
- Node.js and npm on the production server
- Web server (Nginx/Apache) configured on production server
- MySQL database configured on production server

## Installation

Deployer is already installed as a dev dependency. If you need to install it manually:

```bash
composer require deployer/deployer --dev
```

## Configuration

### 1. Update deploy.php

Edit the `deploy.php` file in the project root and update the following settings:

```php
// Update repository URL
set('repository', 'git@github.com:yourusername/laravel-streaming.git');

// Update production host settings
host('production')
    ->set('hostname', 'your-production-server.com')
    ->set('remote_user', 'your-deploy-user')
    ->set('deploy_path', '/var/www/laravel-streaming')
    ->set('branch', 'main');
```

### 2. Server Setup

On your production server, ensure the following:

1. **Create deployment directory:**
   ```bash
   sudo mkdir -p /var/www/laravel-streaming
   sudo chown deploy:deploy /var/www/laravel-streaming
   ```

2. **Install required software:**
   ```bash
   # PHP 8.4, Composer, Node.js, npm, MySQL, Nginx/Apache
   # Configure web server to point to /var/www/laravel-streaming/current/public
   ```

3. **Set up SSH key authentication** for the deploy user

### 3. Environment Configuration

Create a `.env` file in the shared directory on your server:

```bash
# On production server
mkdir -p /var/www/laravel-streaming/shared
cp .env.example /var/www/laravel-streaming/shared/.env
# Edit the .env file with production settings
```

## Deployment Commands

### First Deployment

For the first deployment, you may need to run some setup tasks:

```bash
# Deploy the application
./vendor/bin/dep deploy production

# If you need to run migrations separately
./vendor/bin/dep laravel:migrate production
```

### Regular Deployments

```bash
# Deploy to production
./vendor/bin/dep deploy production

# Deploy to staging (if configured)
./vendor/bin/dep deploy staging
```

### Rollback

If something goes wrong, you can rollback to the previous release:

```bash
./vendor/bin/dep rollback production
```

## Deployment Process

The deployment process includes the following steps:

1. **Prepare deployment** - Creates new release directory
2. **Install Composer dependencies** - Runs `composer install --no-dev --optimize-autoloader`
3. **Install Node.js dependencies** - Runs `npm ci --production=false`
4. **Build frontend assets** - Runs `npm run build` to compile Vue.js/Inertia.js assets
5. **Setup episodes data** - Ensures episodes.json is in shared directory
6. **Setup audio directory** - Creates and sets permissions for audio files
7. **Run database migrations** - Executes pending migrations
8. **Create storage symlink** - Links storage directory
9. **Optimize Laravel** - Caches config, routes, and views
10. **Publish release** - Switches symlink to new release
11. **Restart services** - Restarts queue workers and PHP-FPM

## Shared Files and Directories

The following files and directories are shared between deployments:

### Shared Files:
- `.env` - Environment configuration

### Shared Directories:
- `storage/` - Application storage (logs, cache, sessions)
- `public/audios/` - Audio files for episodes
- `database/data/` - Episodes metadata (episodes.json)

## Custom Tasks

### Episodes Setup
Ensures the episodes data directory exists and copies episodes.json if needed:
```bash
./vendor/bin/dep episodes:setup production
```

### Audio Setup
Creates and sets proper permissions for the audio directory:
```bash
./vendor/bin/dep audios:setup production
```

### Laravel Optimization
Caches configuration, routes, and views:
```bash
./vendor/bin/dep laravel:optimize production
```

## Troubleshooting

### Common Issues

1. **Permission errors:**
   ```bash
   # Fix storage permissions
   ./vendor/bin/dep artisan:storage:link production
   ```

2. **Frontend build fails:**
   ```bash
   # Check Node.js version on server
   # Ensure npm dependencies are installed
   ./vendor/bin/dep npm:install production
   ./vendor/bin/dep npm:build production
   ```

3. **Database connection issues:**
   - Verify `.env` file in shared directory
   - Check database credentials and host

4. **Audio files not accessible:**
   ```bash
   # Check audio directory permissions
   ./vendor/bin/dep audios:setup production
   ```

### Logs

Check deployment logs and application logs:

```bash
# Deployer logs are shown during deployment
# Laravel logs on server:
tail -f /var/www/laravel-streaming/shared/storage/logs/laravel.log
```

## Security Considerations

1. **Use SSH keys** instead of passwords for authentication
2. **Limit deploy user permissions** - only what's needed for deployment
3. **Keep .env file secure** with proper file permissions (600)
4. **Use HTTPS** for production deployment
5. **Regular security updates** for server packages

## Production Checklist

Before deploying to production:

- [ ] Update repository URL in deploy.php
- [ ] Configure production host settings
- [ ] Set up production server with required software
- [ ] Create and configure .env file on server
- [ ] Set up SSL certificate
- [ ] Configure web server (Nginx/Apache)
- [ ] Test database connection
- [ ] Upload audio files to shared/public/audios/
- [ ] Verify episodes.json is in shared/database/data/
- [ ] Run first deployment
- [ ] Test application functionality
- [ ] Set up monitoring and backups

## Additional Resources

- [Deployer Documentation](https://deployer.org/docs/7.x)
- [Laravel Deployment Best Practices](https://laravel.com/docs/deployment)
- [Inertia.js Deployment](https://inertiajs.com/server-side-setup)