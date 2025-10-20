# Laravel Audio Streaming Platform - High-Level Architecture

## 1. System Overview

This is a professional audio streaming platform built with Laravel + Inertia.js + Vue.js, designed to provide SoundCloud-like functionality with embeddable players. The application runs entirely in Docker containers for consistent development and deployment.

### Core Capabilities
- **Progressive Audio Streaming**: Instant playback with HTTP range request support
- **Embeddable Players**: Iframe-based players for external websites
- **Global Audio Management**: Cross-frame audio control using BroadcastChannel API
- **Professional UI**: SoundCloud-inspired responsive design
- **Real-time Features**: Live episode updates and streaming controls

## 2. Overall System Architecture

```mermaid
graph TB
    subgraph "Client Layer"
        Browser[Web Browser]
        Embed[Embedded Players]
    end
    
    subgraph "Load Balancer"
        Nginx[Nginx Reverse Proxy]
    end
    
    subgraph "Application Layer"
        Laravel[Laravel Application<br/>PHP 8.4 + FPM]
        Vite[Vite Dev Server<br/>Node.js]
    end
    
    subgraph "Data Layer"
        MySQL[(MySQL 8.0<br/>Database)]
        Redis[(Redis<br/>Cache & Sessions)]
        Files[Audio Files<br/>Static Storage]
        JSON[Episodes Metadata<br/>JSON Storage)]
    end
    
    subgraph "Development Tools"
        MailHog[MailHog<br/>Email Testing]
    end
    
    Browser --> Nginx
    Embed --> Nginx
    Nginx --> Laravel
    Nginx --> Vite
    Laravel --> MySQL
    Laravel --> Redis
    Laravel --> Files
    Laravel --> JSON
    Laravel --> MailHog
```

## 3. Docker Container Architecture

### 3.1 Container Services

| Service | Image | Purpose | Ports |
|---------|-------|---------|-------|
| **app** | Custom PHP 8.4-FPM | Laravel application backend | Internal |
| **nginx** | nginx:alpine | Web server & reverse proxy | 80:80 |
| **mysql** | mysql:8.0 | Primary database | 3306:3306 |
| **redis** | redis:alpine | Cache & session storage | 6379:6379 |
| **node** | node:current-alpine | Vite development server | 5179:5179, 5180:5180 |
| **mailhog** | mailhog/mailhog | Email testing service | 8025:8025 |

### 3.2 Container Communication

```mermaid
graph LR
    subgraph "Docker Network: lifeandmessage"
        App[app container]
        Nginx[nginx container]
        MySQL[mysql container]
        Redis[redis container]
        Node[node container]
        Mail[mailhog container]
    end
    
    Nginx --> App
    App --> MySQL
    App --> Redis
    App --> Mail
    Node -.-> App
```

## 4. Backend Architecture (Laravel)

### 4.1 Controller Layer

```mermaid
graph TD
    Request[HTTP Request] --> Router[Laravel Router]
    Router --> EC[EpisodeController]
    Router --> ASC[AudioStreamController]
    Router --> EMC[EmbedController]
    Router --> Auth[Auth Controllers]
    
    EC --> |Episodes API| JSON[episodes.json]
    ASC --> |Stream Audio| Files[Audio Files]
    EMC --> |Embed Players| Views[Blade Views]
    Auth --> |User Management| DB[(MySQL)]
```

### 4.2 Key Controllers

| Controller | Responsibility | Key Methods |
|------------|----------------|-------------|
| **EpisodeController** | Episode management & API | `index()`, `show()`, `api()` |
| **AudioStreamController** | Audio streaming with range requests | `stream()`, `handleRangeRequest()` |
| **EmbedController** | Embeddable player generation | `show()`, `generateEmbedCode()` |
| **Auth Controllers** | User authentication (Fortify) | Login, Register, 2FA |

### 4.3 Audio Streaming Infrastructure

```mermaid
sequenceDiagram
    participant Client
    participant Nginx
    participant Laravel
    participant Storage
    
    Client->>Nginx: GET /api/stream/episode.m4a
    Nginx->>Laravel: Forward request
    Laravel->>Storage: Check file exists
    Storage-->>Laravel: File metadata
    
    alt Range Request
        Client->>Laravel: Range: bytes=0-1023
        Laravel->>Storage: Read partial content
        Storage-->>Laravel: Chunk data
        Laravel-->>Client: 206 Partial Content
    else Full Request
        Laravel->>Storage: Read full file
        Storage-->>Laravel: Complete file
        Laravel-->>Client: 200 OK + Stream
    end
```

## 5. Frontend Architecture (Vue.js + Inertia.js)

### 5.1 Application Structure

```mermaid
graph TD
    subgraph "Frontend Layer"
        App[app.ts<br/>Main Entry Point]
        Inertia[Inertia.js<br/>SPA Router]
        
        subgraph "Pages"
            Home[Home.vue]
            Dashboard[Dashboard.vue]
            Auth[Auth Pages]
        end
        
        subgraph "Components"
            AudioPlayer[AudioPlayer.vue]
            EmbedPlayer[EmbedPlayer.vue]
            WaveformPlayer[WaveformPlayer.vue]
            EpisodeList[EpisodeList.vue]
        end
        
        subgraph "Composables"
            useAudioPlayer[useAudioPlayer.ts]
            useGlobalAudioManager[useGlobalAudioManager.ts]
            useAudioStreaming[useAudioStreaming.ts]
        end
        
        subgraph "Layouts"
            AppLayout[AppLayout.vue]
            AuthLayout[AuthLayout.vue]
        end
    end
    
    App --> Inertia
    Inertia --> Pages
    Pages --> Components
    Components --> Composables
    Pages --> Layouts
```

### 5.2 Audio Management System

```mermaid
graph TB
    subgraph "Global Audio Manager"
        GAM[useGlobalAudioManager]
        BC[BroadcastChannel API]
    end
    
    subgraph "Main Player"
        AP1[AudioPlayer.vue]
        UAP1[useAudioPlayer.ts]
    end
    
    subgraph "Embed Player 1"
        EP1[EmbedPlayer.vue]
        UAP2[useAudioPlayer.ts]
    end
    
    subgraph "Embed Player N"
        EPN[EmbedPlayer.vue]
        UAPN[useAudioPlayer.ts]
    end
    
    GAM --> BC
    BC --> UAP1
    BC --> UAP2
    BC --> UAPN
    
    UAP1 --> AP1
    UAP2 --> EP1
    UAPN --> EPN
```

## 6. Data Architecture

### 6.1 Data Storage Strategy

```mermaid
graph TD
    subgraph "Persistent Storage"
        MySQL[(MySQL Database)]
        AudioFiles[Audio Files<br/>public/audios/]
        EpisodeJSON[episodes.json<br/>Metadata Storage]
    end
    
    subgraph "Cache Layer"
        Redis[(Redis Cache)]
        Browser[Browser Cache]
    end
    
    subgraph "Application"
        Laravel[Laravel Backend]
        Vue[Vue Frontend]
    end
    
    Laravel --> MySQL
    Laravel --> AudioFiles
    Laravel --> EpisodeJSON
    Laravel --> Redis
    Vue --> Browser
    Vue --> Laravel
```

### 6.2 Episode Data Model

```json
{
  "episodes": [
    {
      "id": 1,
      "title": "Episode 1 - Tech Weekly",
      "filename": "first-episode.m4a",
      "url": "/audios/first-episode.m4a",
      "duration": "45:30",
      "description": "Weekly tech discussion",
      "published_at": "2024-01-15"
    }
  ]
}
```

## 7. Progressive Streaming Implementation

### 7.1 Streaming Flow

```mermaid
sequenceDiagram
    participant User
    participant Vue
    participant Laravel
    participant AudioFile
    
    User->>Vue: Click Play
    Vue->>Vue: initAudio() with preload='none'
    Vue->>Laravel: GET /api/stream/episode.m4a
    Laravel->>AudioFile: Check file exists
    
    loop Progressive Loading
        Vue->>Laravel: Range Request (bytes=X-Y)
        Laravel->>AudioFile: Read chunk
        AudioFile-->>Laravel: Audio chunk
        Laravel-->>Vue: 206 Partial Content
        Vue->>User: Stream audio chunk
    end
    
    User->>Vue: Seek to position
    Vue->>Laravel: Range Request (bytes=Z-)
    Laravel-->>Vue: Resume from position
```

### 7.2 Audio Player Configuration

```typescript
// useAudioPlayer.ts - Progressive streaming setup
const initAudio = (src: string) => {
  audioElement.value = new Audio()
  audioElement.value.preload = 'none'        // Load only on demand
  audioElement.value.crossOrigin = 'anonymous' // CORS support
  audioElement.value.src = src
  
  // Event listeners for streaming states
  audioElement.value.addEventListener('loadstart', () => {
    state.isLoading = true
  })
  
  audioElement.value.addEventListener('progress', () => {
    // Update buffered ranges for smart seeking
    updateBufferedRanges()
  })
}
```

## 8. Embeddable Player System

### 8.1 Embed Architecture

```mermaid
graph TD
    subgraph "External Website"
        ExtPage[External Page]
        Iframe[Iframe Element]
    end
    
    subgraph "Streaming Platform"
        EmbedRoute[/embed/{id}]
        EmbedController[EmbedController]
        EmbedView[embed.blade.php]
        EmbedPlayer[EmbedPlayer.vue]
    end
    
    subgraph "Global Audio Control"
        BroadcastChannel[BroadcastChannel API]
        AudioManager[Global Audio Manager]
    end
    
    ExtPage --> Iframe
    Iframe --> EmbedRoute
    EmbedRoute --> EmbedController
    EmbedController --> EmbedView
    EmbedView --> EmbedPlayer
    EmbedPlayer --> BroadcastChannel
    BroadcastChannel --> AudioManager
```

### 8.2 Cross-Frame Communication

```javascript
// Global Audio Manager - Cross-frame control
const channel = new BroadcastChannel('audio-control')

// When any player starts
channel.postMessage({
  type: 'AUDIO_STARTED',
  playerId: 'player-123',
  episodeId: 1
})

// All other players receive and pause
channel.addEventListener('message', (event) => {
  if (event.data.type === 'AUDIO_STARTED' && 
      event.data.playerId !== currentPlayerId) {
    pauseCurrentAudio()
  }
})
```

## 9. Development Workflow

### 9.1 Development Environment

```mermaid
graph LR
    subgraph "Development Setup"
        Docker[Docker Compose]
        Laravel[Laravel Backend]
        Vite[Vite Dev Server]
        HMR[Hot Module Reload]
    end
    
    subgraph "Development Tools"
        Nginx[Nginx Proxy]
        MySQL[MySQL DB]
        Redis[Redis Cache]
        MailHog[Email Testing]
    end
    
    Docker --> Laravel
    Docker --> Vite
    Docker --> Nginx
    Docker --> MySQL
    Docker --> Redis
    Docker --> MailHog
    
    Vite --> HMR
    HMR --> Browser[Browser Auto-Reload]
```

### 9.2 Development Commands

```bash
# Start development environment
docker compose up -d

# Frontend development (with HMR)
docker compose exec node npm run dev

# Backend development
docker compose exec app php artisan serve

# Database operations
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

## 10. Security & Performance

### 10.1 Security Measures

- **CORS Configuration**: Proper cross-origin headers for audio streaming
- **File Validation**: Basename validation to prevent directory traversal
- **Authentication**: Laravel Fortify for secure user management
- **Input Sanitization**: XSS protection for embed code generation

### 10.2 Performance Optimizations

- **Progressive Streaming**: Instant playback without full download
- **HTTP Range Requests**: Efficient seeking and bandwidth usage
- **Redis Caching**: Session and application data caching
- **Asset Optimization**: Vite bundling and code splitting
- **Nginx Proxy**: Static file serving and request optimization

## 11. Deployment Architecture

### 11.1 Production Considerations

```mermaid
graph TB
    subgraph "Production Environment"
        LB[Load Balancer]
        App1[App Instance 1]
        App2[App Instance 2]
        AppN[App Instance N]
        
        subgraph "Data Layer"
            ProdDB[(Production MySQL)]
            ProdRedis[(Production Redis)]
            CDN[CDN for Audio Files]
        end
    end
    
    LB --> App1
    LB --> App2
    LB --> AppN
    
    App1 --> ProdDB
    App1 --> ProdRedis
    App1 --> CDN
    
    App2 --> ProdDB
    App2 --> ProdRedis
    App2 --> CDN
```

### 11.2 Scalability Features

- **Horizontal Scaling**: Multiple Laravel instances behind load balancer
- **CDN Integration**: Audio files served from CDN for global performance
- **Database Optimization**: MySQL with proper indexing and query optimization
- **Cache Strategy**: Redis for session management and application caching
- **Asset Delivery**: Optimized static asset delivery through CDN

## 12. Technology Stack Summary

### Backend Stack
- **Framework**: Laravel 11.x
- **Runtime**: PHP 8.4 with FPM
- **Database**: MySQL 8.0
- **Cache**: Redis
- **Authentication**: Laravel Fortify
- **Web Server**: Nginx (Alpine)

### Frontend Stack
- **Framework**: Vue.js 3.x (Composition API)
- **SPA Solution**: Inertia.js
- **Language**: TypeScript
- **Build Tool**: Vite
- **Styling**: Tailwind CSS
- **Runtime**: Node.js (Alpine)

### Infrastructure
- **Containerization**: Docker & Docker Compose
- **Development**: Hot Module Replacement (HMR)
- **Email Testing**: MailHog
- **Audio Streaming**: Progressive streaming with HTTP range requests
- **Cross-Frame Communication**: BroadcastChannel API

This architecture provides a robust, scalable foundation for a professional audio streaming platform comparable to SoundCloud and Spotify, with modern development practices and deployment-ready containerization.