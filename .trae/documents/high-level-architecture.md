# High-Level Architecture - Laravel Audio Streaming Platform

## 1. System Overview

This document outlines the high-level architecture of a professional audio streaming platform built with Laravel, Inertia.js, and Vue.js, featuring embeddable players and progressive streaming capabilities.

<div align="center">

```mermaid
flowchart TB
    %% User Layer
    subgraph User_Layer["User Layer"]
        U1[User Browser / Mobile Device]
        U2[External Website Visitor]
    end

    %% Frontend Layer
    subgraph Frontend_Layer["Frontend Layer — Vue.js + Inertia"]
        VUE[Vue.js SPA]
        INERTIA[Inertia.js Middleware]
        EMBED_PLAYER[Embed Player<br/>Lightweight Vue Component]
    end

    %% Backend Layer
    subgraph Backend_Layer["Backend Layer — Laravel"]
        LARAVEL[Laravel Application]
        EPISODE_CTRL[EpisodeController]
        EMBED_CTRL[EmbedController]
    end

    %% Data & Storage Layer
    subgraph Data_Layer["Data & Storage Layer"]
        META[JSON Episode Metadata]
        AUDIO_STORE[Audio Files Storage<br/>public/audios]
        FILESYSTEM[Laravel Filesystem]
    end

    %% Main Application Flows
    U1 --> VUE
    VUE --> INERTIA
    INERTIA --> LARAVEL

    LARAVEL --> EPISODE_CTRL
    LARAVEL --> EMBED_CTRL

    EPISODE_CTRL --> META
    EPISODE_CTRL --> AUDIO_STORE
    EMBED_CTRL --> META
    EMBED_CTRL --> AUDIO_STORE
    LARAVEL --> FILESYSTEM

    %% Embed flow to external websites
    U2 --> EMBED_PLAYER
    EMBED_PLAYER --> EMBED_CTRL
```

</div>

## 2. Backend API Architecture

### 2.1 Laravel Controllers Structure

<div align="center">

```mermaid
graph TD
    subgraph "Laravel Backend"
        ROUTER[Route Handler]
        
        subgraph "Controllers"
            EPISODE[EpisodeController]
            AUDIO[AudioStreamController]
            EMBED[EmbedController]
        end
        
        subgraph "Services"
            STREAM[Streaming Service]
            AUTH[Authentication]
            CACHE[Cache Service]
        end
        
        subgraph "Data Sources"
            DB[(MySQL)]
            REDIS[(Redis)]
            FILES[Audio Files]
            JSON[Episodes.json]
        end
    end
    
    ROUTER --> EPISODE
    ROUTER --> AUDIO
    ROUTER --> EMBED
    
    EPISODE --> DB
    EPISODE --> JSON
    EPISODE --> CACHE
    
    AUDIO --> FILES
    AUDIO --> STREAM
    
    EMBED --> JSON
    EMBED --> CACHE
```

</div>

### 2.2 API Endpoints Design

| Endpoint                    | Method | Controller                                 | Purpose                         |
| --------------------------- | ------ | ------------------------------------------ | ------------------------------- |
| `/`                         | GET    | EpisodeController\@index                   | Home page with episode list     |
| `/api/episodes`             | GET    | EpisodeController\@apiIndex                | Get all episodes (API)          |
| `/api/episodes/{id}`        | GET    | EpisodeController\@show                    | Get specific episode            |
| `/api/stream/{filename}`    | GET    | AudioStreamController\@stream              | Stream audio with range support |
| `/api/episodes/{id}/stream` | GET    | AudioStreamController\@getEpisodeStreamUrl | Get streaming URL for episode   |
| `/embed/{id}`               | GET    | EmbedController\@show                      | Embed player page               |
| `/api/embed/{id}/code`      | GET    | EmbedController\@generateEmbedCode         | Generate embed HTML code        |

### 2.3 Backend Data Flow

<div align="center">

```mermaid
sequenceDiagram
    participant Client
    participant Router
    participant Controller
    participant Service
    participant DataSource
    
    Client->>Router: HTTP Request
    Router->>Controller: Route to Controller
    Controller->>Service: Business Logic
    Service->>DataSource: Data Access
    DataSource-->>Service: Data Response
    Service-->>Controller: Processed Data
    Controller-->>Router: HTTP Response
    Router-->>Client: JSON/HTML Response
```

</div>

## 3. Frontend Architecture

### 3.1 Vue.js Component Hierarchy

<div align="center">

```mermaid
graph TD
    subgraph "Vue.js Frontend"
        APP[App.ts - Entry Point]
        
        subgraph "Layouts"
            APP_LAYOUT[AppLayout.vue]
            AUTH_LAYOUT[AuthLayout.vue]
            SETTINGS_LAYOUT[Settings Layout]
        end
        
        subgraph "Pages"
            HOME[Home.vue]
            DASHBOARD[Dashboard.vue]
            AUTH_PAGES[Auth Pages]
        end
        
        subgraph "Components"
            AUDIO_PLAYER[AudioPlayer.vue]
            EPISODE_LIST[EpisodeList.vue]
            WAVEFORM[WaveformPlayer.vue]
            EMBED_PLAYER[EmbedPlayer.vue]
        end
        
        subgraph "Composables"
            USE_AUDIO[useAudioPlayer.ts]
            USE_GLOBAL[useGlobalAudioManager.ts]
            USE_STREAMING[useAudioStreaming.ts]
        end
    end
    
    APP --> APP_LAYOUT
    APP --> AUTH_LAYOUT
    APP_LAYOUT --> HOME
    APP_LAYOUT --> DASHBOARD
    HOME --> AUDIO_PLAYER
    HOME --> EPISODE_LIST
    AUDIO_PLAYER --> WAVEFORM
    AUDIO_PLAYER --> USE_AUDIO
    EMBED_PLAYER --> USE_GLOBAL
    USE_AUDIO --> USE_STREAMING
```

</div>

### 3.2 State Management Pattern

<div align="center">

```mermaid
graph LR
    subgraph "Component State"
        REACTIVE[Reactive Data]
        COMPUTED[Computed Properties]
        METHODS[Component Methods]
    end
    
    subgraph "Composables"
        AUDIO_STATE[Audio State]
        GLOBAL_STATE[Global Manager]
        STREAMING_STATE[Streaming State]
    end
    
    subgraph "External APIs"
        LARAVEL_API[Laravel API]
        BROADCAST[BroadcastChannel]
    end
    
    REACTIVE --> COMPUTED
    COMPUTED --> METHODS
    METHODS --> AUDIO_STATE
    AUDIO_STATE --> GLOBAL_STATE
    GLOBAL_STATE --> BROADCAST
    STREAMING_STATE --> LARAVEL_API
```

</div>

### 3.3 Frontend Data Flow

<div align="center">

```mermaid
sequenceDiagram
    participant User
    participant Component
    participant Composable
    participant API
    participant Backend
    
    User->>Component: User Interaction
    Component->>Composable: State Update
    Composable->>API: HTTP Request
    API->>Backend: Laravel Route
    Backend-->>API: JSON Response
    API-->>Composable: Data Update
    Composable-->>Component: Reactive Update
    Component-->>User: UI Update
```

</div>

## 4. Embed System Architecture

### 4.1 Embed Player Integration

<div align="center">

```mermaid
graph TD
    subgraph "External Website"
        IFRAME[Iframe Container]
        EMBED_CODE[Generated Embed Code]
    end
    
    subgraph "Embed System"
        EMBED_ROUTE[/embed/{id}]
        EMBED_CONTROLLER[EmbedController]
        EMBED_VIEW[embed.blade.php]
        EMBED_PLAYER[EmbedPlayer.vue]
    end
    
    subgraph "Global Audio Manager"
        BROADCAST[BroadcastChannel API]
        GLOBAL_STATE[Global Audio State]
    end
    
    subgraph "Audio Streaming"
        STREAM_API[Streaming API]
        AUDIO_FILES[Audio Files]
    end
    
    EMBED_CODE --> IFRAME
    IFRAME --> EMBED_ROUTE
    EMBED_ROUTE --> EMBED_CONTROLLER
    EMBED_CONTROLLER --> EMBED_VIEW
    EMBED_VIEW --> EMBED_PLAYER
    EMBED_PLAYER --> BROADCAST
    EMBED_PLAYER --> STREAM_API
    STREAM_API --> AUDIO_FILES
```

</div>

### 4.2 Cross-Frame Communication

<div align="center">

```mermaid
sequenceDiagram
    participant MainApp
    participant EmbedPlayer1
    participant EmbedPlayer2
    participant BroadcastChannel
    
    MainApp->>BroadcastChannel: Play Episode A
    BroadcastChannel->>EmbedPlayer1: Pause Current
    BroadcastChannel->>EmbedPlayer2: Pause Current
    EmbedPlayer1->>BroadcastChannel: Play Episode B
    BroadcastChannel->>MainApp: Pause Current
    BroadcastChannel->>EmbedPlayer2: Pause Current
```

</div>

### 4.3 Embed Code Generation

```typescript
// Embed Code Structure
interface EmbedCode {
    embedUrl: string;          // /embed/{id}
    embedCode: string;         // <iframe> HTML
    episode: Episode;          // Episode data
    dimensions: {
        width: string;         // "100%"
        height: string;        // "120px"
    };
    features: {
        autoplay: boolean;     // false by default
        controls: boolean;     // true
        waveform: boolean;     // true
    };
}
```

## 5. API Design Patterns

### 5.1 RESTful API Structure

<div align="center">

```mermaid
graph TD
    subgraph "API Layers"
        ROUTES[Route Definitions]
        CONTROLLERS[Controller Layer]
        SERVICES[Service Layer]
        REPOSITORIES[Repository Layer]
    end
    
    subgraph "Response Patterns"
        JSON_API[JSON API Format]
        STREAMING[Streaming Response]
        HTML[HTML Response]
    end
    
    subgraph "Authentication"
        GUEST[Guest Access]
        AUTH[Authenticated]
        API_KEY[API Key (Future)]
    end
    
    ROUTES --> CONTROLLERS
    CONTROLLERS --> SERVICES
    SERVICES --> REPOSITORIES
    
    CONTROLLERS --> JSON_API
    CONTROLLERS --> STREAMING
    CONTROLLERS --> HTML
    
    ROUTES --> GUEST
    ROUTES --> AUTH
    ROUTES --> API_KEY
```

</div>

### 5.2 Audio Streaming API

<div align="center">

```mermaid
sequenceDiagram
    participant Client
    participant AudioController
    participant StreamService
    participant FileSystem
    
    Client->>AudioController: GET /api/stream/{filename}
    AudioController->>AudioController: Validate filename
    AudioController->>FileSystem: Check file exists
    FileSystem-->>AudioController: File info
    
    alt Range Request
        AudioController->>StreamService: Handle range request
        StreamService->>FileSystem: Read file chunk
        FileSystem-->>StreamService: File chunk
        StreamService-->>AudioController: Streamed chunk
        AudioController-->>Client: 206 Partial Content
    else Full Request
        AudioController->>StreamService: Serve full file
        StreamService->>FileSystem: Read full file
        FileSystem-->>StreamService: Full file
        StreamService-->>AudioController: Full stream
        AudioController-->>Client: 200 OK
    end
```

</div>

### 5.3 API Response Formats

```typescript
// Episode API Response
interface EpisodeResponse {
    id: number;
    title: string;
    filename: string;
    url: string;
    duration: string;
    published_date: string;
    description?: string;
}

// Streaming API Response
interface StreamResponse {
    episode: EpisodeResponse;
    stream_url: string;
    supports_range: boolean;
}

// Embed Code Response
interface EmbedResponse {
    embedCode: string;
    embedUrl: string;
    episode: EpisodeResponse;
}
```

## 6. Data Flow Architecture

### 6.1 Complete System Data Flow

<div align="center">

```mermaid
graph TB
    subgraph "User Interactions"
        WEB_USER[Web User]
        EMBED_USER[Embed User]
        API_USER[API User]
    end
    
    subgraph "Frontend Processing"
        VUE_APP[Vue Application]
        EMBED_PLAYER[Embed Player]
        COMPOSABLES[Composables]
    end
    
    subgraph "Backend Processing"
        LARAVEL[Laravel Backend]
        CONTROLLERS[Controllers]
        MIDDLEWARE[Middleware]
    end
    
    subgraph "Data Storage"
        MYSQL[(MySQL)]
        REDIS[(Redis)]
        FILES[Audio Files]
        JSON[Episodes JSON]
    end
    
    subgraph "External Services"
        CDN[CDN (Future)]
        ANALYTICS[Analytics (Future)]
    end
    
    WEB_USER --> VUE_APP
    EMBED_USER --> EMBED_PLAYER
    API_USER --> LARAVEL
    
    VUE_APP --> COMPOSABLES
    EMBED_PLAYER --> COMPOSABLES
    COMPOSABLES --> LARAVEL
    
    LARAVEL --> CONTROLLERS
    CONTROLLERS --> MIDDLEWARE
    MIDDLEWARE --> MYSQL
    MIDDLEWARE --> REDIS
    MIDDLEWARE --> FILES
    MIDDLEWARE --> JSON
    
    CONTROLLERS --> CDN
    CONTROLLERS --> ANALYTICS
```

</div>

### 6.2 Audio Streaming Data Flow

<div align="center">

```mermaid
sequenceDiagram
    participant Browser
    participant VueComponent
    participant Composable
    participant LaravelAPI
    participant AudioFile
    
    Browser->>VueComponent: User clicks play
    VueComponent->>Composable: initAudio(episode)
    Composable->>LaravelAPI: GET /api/episodes/{id}/stream
    LaravelAPI-->>Composable: Stream URL + metadata
    Composable->>LaravelAPI: GET /api/stream/{filename}
    LaravelAPI->>AudioFile: Read file with range support
    AudioFile-->>LaravelAPI: Audio chunks
    LaravelAPI-->>Composable: Streamed audio data
    Composable-->>VueComponent: Audio ready
    VueComponent-->>Browser: Audio plays
```

</div>

## 7. Integration Points

### 7.1 Frontend-Backend Integration

<div align="center">

```mermaid
graph LR
    subgraph "Frontend"
        INERTIA[Inertia.js]
        AXIOS[HTTP Client]
        COMPOSABLES[Composables]
    end
    
    subgraph "Backend"
        ROUTES[Laravel Routes]
        CONTROLLERS[Controllers]
        RESPONSES[JSON Responses]
    end
    
    INERTIA <--> ROUTES
    AXIOS <--> CONTROLLERS
    COMPOSABLES <--> RESPONSES
```

</div>

### 7.2 Embed Integration Points

<div align="center">

```mermaid
graph TD
    subgraph "Main Application"
        MAIN_PLAYER[Main Audio Player]
        GLOBAL_MANAGER[Global Audio Manager]
    end
    
    subgraph "Embed Players"
        EMBED1[Embed Player 1]
        EMBED2[Embed Player 2]
        EMBED3[Embed Player N]
    end
    
    subgraph "Communication Layer"
        BROADCAST[BroadcastChannel API]
        EVENTS[Custom Events]
    end
    
    MAIN_PLAYER --> GLOBAL_MANAGER
    GLOBAL_MANAGER --> BROADCAST
    BROADCAST --> EMBED1
    BROADCAST --> EMBED2
    BROADCAST --> EMBED3
    
    EMBED1 --> EVENTS
    EMBED2 --> EVENTS
    EMBED3 --> EVENTS
    EVENTS --> BROADCAST
```

</div>

### 7.3 Development Integration

<div align="center">

```mermaid
graph TD
    subgraph "Development Environment"
        DOCKER[Docker Compose]
        VITE[Vite Dev Server]
        LARAVEL_DEV[Laravel Development]
    end
    
    subgraph "Build Process"
        TYPESCRIPT[TypeScript Compilation]
        VUE_SFC[Vue SFC Processing]
        ASSET_BUILD[Asset Building]
    end
    
    subgraph "Production"
        NGINX[Nginx Proxy]
        PHP_FPM[PHP-FPM]
        STATIC_ASSETS[Static Assets]
    end
    
    DOCKER --> VITE
    DOCKER --> LARAVEL_DEV
    VITE --> TYPESCRIPT
    VITE --> VUE_SFC
    VITE --> ASSET_BUILD
    
    ASSET_BUILD --> STATIC_ASSETS
    LARAVEL_DEV --> PHP_FPM
    NGINX --> PHP_FPM
    NGINX --> STATIC_ASSETS
```

</div>

## 8. Security & Performance Considerations

### 8.1 Security Architecture

* **Input Validation**: All file paths validated to prevent directory traversal

* **CORS Configuration**: Proper cross-origin headers for embed functionality

* **Authentication**: Laravel Fortify for user authentication

* **File Access Control**: Audio files served through controlled endpoints

* **XSS Protection**: Vue.js template escaping and CSP headers

### 8.2 Performance Optimizations

* **Progressive Streaming**: Audio starts playing before full download

* **HTTP Range Requests**: Efficient seeking and bandwidth usage

* **Redis Caching**: Episode metadata and user sessions cached

* **Asset Optimization**: Vite for optimized frontend builds

* **Nginx Proxy**: Static file serving and request optimization

## 9. Scalability Considerations

### 9.1 Horizontal Scaling Points

* **Load Balancer**: Multiple Laravel instances behind load balancer

* **CDN Integration**: Audio files served from CDN for global distribution

* **Database Scaling**: Read replicas for episode data

* **Cache Scaling**: Redis cluster for distributed caching

* **Microservices**: Audio processing as separate service

### 9.2 Future Architecture Enhancements

* **API Gateway**: Centralized API management and rate limiting

* **Message Queue**: Background processing for audio uploads

* **Search Service**: Elasticsearch for episode search functionality

* **Analytics Service**: Real-time streaming analytics

* **Mobile Apps**: Native mobile applications using same API

