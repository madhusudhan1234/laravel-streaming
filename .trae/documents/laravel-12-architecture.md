# Laravel 12 Audio Streaming Platform - Complete Architecture

## 1. System Overview

This is a professional audio streaming platform built with Laravel 12, featuring SoundCloud-like functionality with embeddable players, progressive streaming, and modern web technologies deployed on a single server.

### Key Features
- **Progressive Audio Streaming** with HTTP range request support
- **Embeddable Players** for external website integration
- **Global Audio Manager** ensuring single-player control across frames
- **Waveform Visualization** and professional UI design
- **Single Server Deployment** with file-based storage
- **Production-ready** with automated deployment tools

```mermaid
graph TB
    subgraph "Client Layer"
        A[Web Browser]
        B[External Websites with Embeds]
    end
    
    subgraph "Single Server Infrastructure"
        C[Nginx Web Server]
        D[Laravel 12 Application]
        E[PHP-FPM Process]
        F[Node.js/Vite Build Tools]
    end
    
    subgraph "File-Based Storage"
        G[SQLite Database]
        H[Episodes JSON Data]
        I[Audio Files Directory]
        J[File-Based Cache]
    end
    
    subgraph "Static Assets"
        K[Built CSS/JS Assets]
        L[Public Audio Files]
    end
    
    A --> C
    B --> C
    C --> D
    D --> E
    D --> G
    D --> H
    D --> I
    D --> J
    F --> K
    C --> L
    
    style D fill:#ff6b6b
    style C fill:#4ecdc4
    style G fill:#45b7d1
    style H fill:#96ceb4
```

## 2. Laravel 12 Framework Architecture

### 2.1 Framework Specifications
- **Laravel Version**: 12.0+ (latest major release)
- **PHP Version**: 8.2+ minimum requirement
- **Architecture Pattern**: MVC with modern enhancements
- **Key Dependencies**:
  - `inertiajs/inertia-laravel: ^2.0` - SPA functionality
  - `laravel/fortify: ^1.30` - Authentication system
  - `laravel/wayfinder: ^0.1.9` - Advanced routing

### 2.2 Laravel 12 New Features Utilized
- **Enhanced Performance**: Improved query optimization and caching
- **Modern PHP Features**: PHP 8.2+ type declarations and attributes
- **Improved Inertia Integration**: Better SSR and hydration support
- **Advanced Routing**: Wayfinder for complex route management

```mermaid
graph TD
    subgraph "Laravel 12 Core"
        A[HTTP Kernel]
        B[Service Container]
        C[Eloquent ORM]
        D[Blade Templates]
    end
    
    subgraph "Modern Extensions"
        E[Inertia.js 2.0]
        F[Laravel Fortify]
        G[Laravel Wayfinder]
    end
    
    subgraph "Application Layer"
        H[Controllers]
        I[Models]
        J[Middleware]
        K[Service Providers]
    end
    
    A --> H
    B --> I
    C --> I
    E --> H
    F --> H
    G --> H
    H --> I
    H --> J
    B --> K
```

## 3. Application Structure

### 3.1 Backend Architecture

#### Controllers
```
app/Http/Controllers/
├── AudioStreamController.php    # HTTP range requests, progressive streaming
├── EpisodeController.php        # Episode management and API
├── EmbedController.php          # Embed player generation
├── Auth/                        # Authentication controllers
└── Settings/                    # User settings management
```

#### Key Controller Responsibilities

| Controller | Purpose | Key Methods |
|------------|---------|-------------|
| **AudioStreamController** | Audio streaming with range support | `stream()`, `handleRangeRequest()`, `getEpisodeStreamUrl()` |
| **EpisodeController** | Episode data management | `index()`, `show()`, `api()` |
| **EmbedController** | Embed player functionality | `show()`, `generateEmbedCode()` |

#### Models & Data Management
```
app/Models/
└── User.php                     # User authentication and management

database/
├── data/episodes.json           # Episode metadata storage
├── migrations/                  # Database schema
└── seeders/                     # Development data
```

### 3.2 Frontend Architecture

#### Vue.js 3 + TypeScript Structure
```
resources/js/
├── app.ts                       # Main application entry
├── ssr.ts                       # Server-side rendering
├── components/
│   ├── AudioPlayer.vue          # Main audio player component
│   ├── EmbedPlayer.vue          # Embeddable player
│   ├── WaveformPlayer.vue       # Waveform visualization
│   ├── EpisodeList.vue          # Episode listing
│   └── ui/                      # Reusable UI components
├── composables/
│   ├── useAudioPlayer.ts        # Audio playback logic
│   ├── useGlobalAudioManager.ts # Cross-frame audio control
│   └── useAudioStreaming.ts     # Streaming functionality
├── pages/
│   ├── Home.vue                 # Main homepage
│   ├── Dashboard.vue            # User dashboard
│   └── auth/                    # Authentication pages
└── layouts/
    ├── AppLayout.vue            # Main application layout
    └── AuthLayout.vue           # Authentication layout
```

#### Component Architecture
```mermaid
graph TD
    subgraph "Page Level"
        A[Home.vue]
        B[Dashboard.vue]
    end
    
    subgraph "Layout Level"
        C[AppLayout.vue]
        D[AuthLayout.vue]
    end
    
    subgraph "Component Level"
        E[AudioPlayer.vue]
        F[EmbedPlayer.vue]
        G[WaveformPlayer.vue]
        H[EpisodeList.vue]
    end
    
    subgraph "Composable Logic"
        I[useAudioPlayer]
        J[useGlobalAudioManager]
        K[useAudioStreaming]
    end
    
    A --> C
    B --> C
    C --> E
    C --> H
    E --> I
    F --> I
    F --> J
    E --> K
    G --> I
```

## 4. Single Server Infrastructure

### 4.1 Server Architecture
```yaml
# Single server deployment configuration
Server Components:
  nginx:        # Web server and reverse proxy
  php-fpm:      # Laravel 12 application (PHP 8.2+)
  sqlite:       # Lightweight database storage
  file-cache:   # File-based caching system
  node:         # Build tools (Vite for production builds)
```

### 4.2 Server Process Communication
```mermaid
graph LR
    subgraph "External Access"
        A[Port 80/443] --> B[Nginx Web Server]
    end
    
    subgraph "Server Processes"
        B --> C[PHP-FPM Process]
        C --> D[Laravel Application]
        D --> E[SQLite Database]
        D --> F[File Cache System]
        D --> G[Episodes JSON]
        D --> H[Audio Files]
    end
    
    subgraph "Build Process"
        I[Node.js/Vite] --> J[Static Assets]
        B --> J
    end
    
    style B fill:#4ecdc4
    style D fill:#ff6b6b
    style E fill:#45b7d1
    style F fill:#96ceb4
```

### 4.3 File System Structure
- **Application Code**: `/var/www/laravel-streaming/` (application root)
- **SQLite Database**: `/var/www/laravel-streaming/database/database.sqlite`
- **Audio Files**: `/var/www/laravel-streaming/public/audios/`
- **Episodes Data**: `/var/www/laravel-streaming/storage/app/episodes.json`
- **File Cache**: `/var/www/laravel-streaming/storage/framework/cache/`

## 5. Audio Streaming System

### 5.1 Progressive Streaming Architecture
```mermaid
sequenceDiagram
    participant Browser
    participant Nginx
    participant Laravel
    participant AudioFile
    
    Browser->>Nginx: Request audio with Range header
    Nginx->>Laravel: Forward to AudioStreamController
    Laravel->>AudioFile: Read file chunk (bytes=start-end)
    AudioFile-->>Laravel: Return audio chunk
    Laravel-->>Nginx: Stream response (206 Partial Content)
    Nginx-->>Browser: Deliver audio chunk
    
    Note over Browser: Audio starts playing immediately
    
    Browser->>Nginx: Request next chunk (seeking)
    Nginx->>Laravel: Range request for new position
    Laravel->>AudioFile: Read from new position
    AudioFile-->>Laravel: Return chunk
    Laravel-->>Nginx: Stream response
    Nginx-->>Browser: Deliver chunk
```

### 5.2 Audio Management Features

#### HTTP Range Request Support
```php
// AudioStreamController implementation
private function handleRangeRequest($filePath, $fileSize, $mimeType, $range)
{
    // Parse range header (e.g., "bytes=0-1023")
    // Validate range boundaries
    // Stream partial content with 206 status
    // Support seeking and progressive loading
}
```

#### Global Audio Manager
```typescript
// useGlobalAudioManager.ts
export function useGlobalAudioManager() {
    const channel = new BroadcastChannel('audio-control');
    
    // Ensure only one player active across all frames/tabs
    const pauseOtherPlayers = (currentPlayerId: string) => {
        channel.postMessage({
            type: 'pause-others',
            playerId: currentPlayerId
        });
    };
}
```

## 6. Database & Data Architecture

### 6.1 Data Storage Strategy
```mermaid
graph TD
    subgraph "File-Based Storage"
        A[SQLite Database]
        B[Episodes JSON File]
        C[Audio Files Directory]
    end
    
    subgraph "Cache Layer"
        D[File-Based Cache]
        E[Browser Cache]
    end
    
    subgraph "Application Data"
        F[User Authentication]
        G[Episode Metadata]
        H[Audio Streams]
    end
    
    F --> A
    G --> B
    H --> C
    A --> D
    B --> D
    C --> E
```

### 6.2 Data Models

#### Episode Data Structure
```json
{
  "episodes": [
    {
      "id": 1,
      "title": "Episode 1 - Madhu Sudhan Subedi Tech Weekly",
      "filename": "first-episode.m4a",
      "url": "/audios/first-episode.m4a",
      "duration": "45:30",
      "description": "Weekly tech discussion..."
    }
  ]
}
```

#### User Model (Laravel)
```php
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;
    
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['email_verified_at' => 'datetime'];
}
```

## 7. Embed System Architecture

### 7.1 Embed Player Implementation
```mermaid
graph TD
    subgraph "External Website"
        A[Website Page]
        B[Iframe Embed]
    end
    
    subgraph "Embed Infrastructure"
        C[EmbedController]
        D[embed.blade.php]
        E[EmbedPlayer.vue]
    end
    
    subgraph "Audio Control"
        F[BroadcastChannel API]
        G[Global Audio Manager]
    end
    
    A --> B
    B --> C
    C --> D
    D --> E
    E --> F
    F --> G
    
    style B fill:#ffd93d
    style E fill:#6bcf7f
```

### 7.2 Cross-Frame Communication
```typescript
// Cross-frame audio control implementation
const audioChannel = new BroadcastChannel('audio-control');

// Listen for global pause commands
audioChannel.addEventListener('message', (event) => {
    if (event.data.type === 'pause-others' && 
        event.data.playerId !== currentPlayerId) {
        pauseCurrentPlayer();
    }
});
```

## 8. Development Workflow

### 8.1 Development Environment Setup
```bash
# Start development environment on single server
sudo systemctl start nginx
sudo systemctl start php8.2-fpm

# Services running:
# - Laravel app (PHP 8.2+ FPM)
# - Nginx web server (port 80/443)
# - SQLite database (file-based)
# - File-based caching
# - Node.js for build tools
```

### 8.2 Development Workflow
```mermaid
graph LR
    subgraph "Development Process"
        A[Code Changes] --> B[File Save]
        B --> C[Browser Reload]
        C --> D[Testing]
        D --> E[Git Commit]
    end
    
    subgraph "Backend Development"
        F[Laravel Changes] --> G[PHP-FPM Reload]
        G --> H[SQLite Migration]
        H --> I[API Testing]
    end
    
    subgraph "Frontend Development"
        J[Vue.js Changes] --> K[TypeScript Compile]
        K --> L[Vite Build]
        L --> M[Browser Update]
    end
```

### 8.3 Build Process
```json
{
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "build:ssr": "vite build && vite build --ssr",
    "format": "prettier --write resources/",
    "lint": "eslint . --fix"
  }
}
```

## 9. Production Deployment

### 9.1 Deployment Architecture
```mermaid
graph TD
    subgraph "Deployment Pipeline"
        A[Git Repository] --> B[Deployer Tool]
        B --> C[Production Server]
    end
    
    subgraph "Single Server Environment"
        C --> D[Nginx Web Server]
        C --> E[PHP-FPM Process]
        C --> F[SQLite Database]
        C --> G[File-Based Cache]
    end
    
    subgraph "Asset Management"
        H[Vite Build] --> I[Static Assets]
        I --> J[Public Directory]
    end
    
    B --> H
    C --> I
```

### 9.2 Deployer Configuration
```php
// deploy.yml - Automated deployment setup
config:
  repository: 'https://github.com/madhusudhan1234/laravel-streaming.git'
  shared_files: ['.env']
  shared_dirs: ['storage', 'public/audios']
  
tasks:
  - deploy:prepare
  - deploy:vendors
  - artisan:migrate
  - artisan:config:cache
  - npm:install
  - npm:run:build
  - deploy:publish
```

## 10. Security & Performance

### 10.1 Security Measures
- **Input Validation**: Filename sanitization for audio streaming
- **CORS Configuration**: Controlled cross-origin access for embeds
- **Authentication**: Laravel Fortify with 2FA support
- **File Access Control**: Restricted audio file access patterns

### 10.2 Performance Optimizations
- **Progressive Streaming**: Instant audio playback without full download
- **File-Based Caching**: Session and application data caching using filesystem
- **Nginx Proxy**: Static file serving and request optimization
- **Asset Optimization**: Vite build process with code splitting

## 11. Scalability Considerations

### 11.1 Single Server Scaling
```mermaid
graph TD
    subgraph "Current Single Server"
        A[Nginx Web Server]
        B[Laravel Application]
        C[SQLite Database]
        D[File-Based Cache]
        E[Audio Files Storage]
    end
    
    subgraph "Future Scaling Options"
        F[Load Balancer]
        G[Multiple App Servers]
        H[Shared Database]
        I[CDN for Audio Files]
    end
    
    A --> B
    B --> C
    B --> D
    B --> E
    
    style A fill:#4ecdc4
    style B fill:#ff6b6b
    style C fill:#45b7d1
    style F fill:#ffd93d,stroke-dasharray: 5 5
    style G fill:#ffd93d,stroke-dasharray: 5 5
    style H fill:#ffd93d,stroke-dasharray: 5 5
    style I fill:#ffd93d,stroke-dasharray: 5 5
```

### 11.2 Future Enhancements
- **CDN Integration**: Global audio file distribution
- **Microservices**: Separate audio processing service
- **Real-time Features**: WebSocket integration for live streaming
- **Analytics**: User behavior and streaming analytics
- **Mobile Apps**: React Native or Flutter applications

## 12. Technology Stack Summary

### 12.1 Backend Stack
- **Framework**: Laravel 12.0+
- **Language**: PHP 8.2+
- **Database**: SQLite (file-based)
- **Cache**: File-based caching
- **Web Server**: Nginx
- **Process Manager**: PHP-FPM

### 12.2 Frontend Stack
- **Framework**: Vue.js 3 with Composition API
- **Language**: TypeScript
- **SPA Solution**: Inertia.js 2.0
- **Build Tool**: Vite
- **Styling**: Tailwind CSS 4.0+
- **UI Components**: Shadcn/ui Vue

### 12.3 Development Tools
- **Containerization**: Docker Compose
- **Development Server**: Vite with HMR
- **Code Quality**: ESLint, Prettier, Laravel Pint
- **Testing**: PHPUnit, Laravel Testing
- **Deployment**: Deployer 7.5+

This Laravel 12 audio streaming platform represents a modern, scalable architecture that combines the latest web technologies to deliver a professional audio streaming experience comparable to industry leaders like SoundCloud and Spotify.