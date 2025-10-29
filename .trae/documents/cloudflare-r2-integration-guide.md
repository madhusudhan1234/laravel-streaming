# Cloudflare R2 Integration Guide for Laravel Streaming Application

## 1. Overview

This document outlines the integration of Cloudflare R2 object storage with the Laravel streaming application to replace local audio file storage with cloud-based storage. This migration will improve scalability, reduce server storage requirements, and provide better global content delivery.

## 2. Benefits of Cloudflare R2 Integration

### 2.1 Cost Efficiency
- **No Egress Fees**: Unlike AWS S3, R2 doesn't charge for data transfer out
- **Competitive Storage Pricing**: $0.015 per GB/month for storage
- **Free Tier**: 10 GB storage and 1 million Class A operations per month

### 2.2 Performance Benefits
- **Global CDN**: Automatic integration with Cloudflare's global network
- **Edge Caching**: Audio files cached at edge locations worldwide
- **Reduced Server Load**: Offload file serving from application server

### 2.3 Scalability
- **Unlimited Storage**: No server disk space limitations
- **High Availability**: Built-in redundancy and reliability
- **Bandwidth Scaling**: Handle traffic spikes without server impact

## 3. Laravel Filesystem Configuration

### 3.1 Install Required Package

```bash
composer require league/flysystem-aws-s3-v3
```

### 3.2 Update config/filesystems.php

Add R2 disk configuration:

```php
'disks' => [
    // ... existing disks

    'r2' => [
        'driver' => 's3',
        'key' => env('R2_ACCESS_KEY_ID'),
        'secret' => env('R2_SECRET_ACCESS_KEY'),
        'region' => env('R2_DEFAULT_REGION', 'auto'),
        'bucket' => env('R2_BUCKET'),
        'endpoint' => env('R2_ENDPOINT'),
        'use_path_style_endpoint' => true,
        'throw' => false,
        'visibility' => 'public',
        'url' => env('R2_PUBLIC_URL'),
    ],
],
```

### 3.3 Set Default Disk (Optional)

```php
'default' => env('FILESYSTEM_DISK', 'r2'),
```

## 4. Environment Configuration

### 4.1 Required Environment Variables

Add to your `.env` file:

```env
# Cloudflare R2 Configuration
R2_ACCESS_KEY_ID=your_r2_access_key_id
R2_SECRET_ACCESS_KEY=your_r2_secret_access_key
R2_DEFAULT_REGION=auto
R2_BUCKET=your-audio-bucket-name
R2_ENDPOINT=https://your-account-id.r2.cloudflarestorage.com
R2_PUBLIC_URL=https://your-custom-domain.com

# Optional: Set R2 as default filesystem
FILESYSTEM_DISK=r2
```

### 4.2 Cloudflare R2 Setup Steps

1. **Create R2 Bucket**:
   - Log into Cloudflare Dashboard
   - Navigate to R2 Object Storage
   - Create a new bucket (e.g., `laravel-streaming-audio`)

2. **Generate API Tokens**:
   - Go to "Manage R2 API tokens"
   - Create token with "Object Read & Write" permissions
   - Note down Access Key ID and Secret Access Key

3. **Configure Custom Domain** (Recommended):
   - Set up a custom domain for your R2 bucket
   - Configure DNS CNAME record
   - Enable public access if needed

## 5. Code Changes for Episode Upload

### 5.1 Updated EpisodeController.php

Replace the current file upload logic with R2 storage:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEpisodeRequest;
use App\Http\Requests\UpdateEpisodeRequest;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EpisodeController extends Controller
{
    /**
     * Convert bytes to human readable format
     */
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    // ... existing methods ...

    /**
     * Store a new episode with R2 upload
     */
    public function store(StoreEpisodeRequest $request)
    {
        try {
            $audioFile = $request->file('audio_file');
            
            // Generate unique filename
            $filename = time() . '_' . $audioFile->getClientOriginalName();
            $filePath = 'audios/' . $filename;

            Log::info('Attempting to upload file to R2', [
                'original_name' => $audioFile->getClientOriginalName(),
                'filename' => $filename,
                'file_size' => $audioFile->getSize(),
                'mime_type' => $audioFile->getMimeType(),
            ]);

            // Upload to R2
            $uploadSuccess = Storage::disk('r2')->put($filePath, file_get_contents($audioFile), 'public');

            if (!$uploadSuccess) {
                Log::error('Failed to upload file to R2', ['filename' => $filename]);
                return redirect()->back()->withErrors(['error' => 'Failed to upload audio file to cloud storage']);
            }

            // Get file info
            $fileSize = $audioFile->getSize();
            $format = $audioFile->getClientOriginalExtension();

            // Extract duration using getID3 (from temporary file)
            $duration = null;
            try {
                $getID3 = new \getID3;
                $fileInfo = $getID3->analyze($audioFile->getPathname());
                if (isset($fileInfo['playtime_seconds'])) {
                    $duration = round($fileInfo['playtime_seconds'] / 60, 2);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to extract audio duration: ' . $e->getMessage());
            }

            // Generate public URL
            $publicUrl = Storage::disk('r2')->url($filePath);

            // Create episode
            $episode = Episode::create([
                'title' => $request->title,
                'description' => $request->description,
                'filename' => $filename,
                'url' => $publicUrl,
                'file_size' => $this->formatFileSize($fileSize),
                'format' => $format,
                'published_date' => $request->published_date,
                'duration' => $duration,
                'storage_path' => $filePath, // Store R2 path for future reference
            ]);

            Log::info('Episode created successfully with R2 storage', ['episode_id' => $episode->id]);

            return redirect()->route('episodes.dashboard')->with('success', 'Episode created successfully');

        } catch (\Exception $e) {
            Log::error('Exception during episode creation', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Clean up uploaded file if episode creation fails
            if (isset($filePath)) {
                Storage::disk('r2')->delete($filePath);
            }

            return redirect()->back()->withErrors(['error' => 'Error creating episode: ' . $e->getMessage()]);
        }
    }

    /**
     * Update an existing episode with R2 support
     */
    public function update(UpdateEpisodeRequest $request, Episode $episode)
    {
        try {
            $updateData = [
                'title' => $request->title,
                'description' => $request->description,
                'published_date' => $request->published_date,
            ];

            // Handle file upload if new file is provided
            if ($request->hasFile('audio_file')) {
                // Delete old file from R2
                if ($episode->storage_path) {
                    Storage::disk('r2')->delete($episode->storage_path);
                }

                // Upload new file
                $audioFile = $request->file('audio_file');
                $filename = time() . '_' . $audioFile->getClientOriginalName();
                $filePath = 'audios/' . $filename;

                $uploadSuccess = Storage::disk('r2')->put($filePath, file_get_contents($audioFile), 'public');

                if (!$uploadSuccess) {
                    return redirect()->back()->withErrors(['error' => 'Failed to upload audio file to cloud storage']);
                }

                // Get file info
                $fileSize = $audioFile->getSize();
                $format = $audioFile->getClientOriginalExtension();

                // Extract duration using getID3
                $duration = null;
                try {
                    $getID3 = new \getID3;
                    $fileInfo = $getID3->analyze($audioFile->getPathname());
                    if (isset($fileInfo['playtime_seconds'])) {
                        $duration = round($fileInfo['playtime_seconds'] / 60, 2);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to extract audio duration: ' . $e->getMessage());
                }

                $updateData['filename'] = $filename;
                $updateData['url'] = Storage::disk('r2')->url($filePath);
                $updateData['file_size'] = $this->formatFileSize($fileSize);
                $updateData['format'] = $format;
                $updateData['duration'] = $duration;
                $updateData['storage_path'] = $filePath;
            }

            $episode->update($updateData);

            return redirect()->route('episodes.dashboard')->with('success', 'Episode updated successfully');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Error updating episode: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete an episode and its R2 file
     */
    public function destroy(Episode $episode)
    {
        try {
            // Delete audio file from R2
            if ($episode->storage_path) {
                Storage::disk('r2')->delete($episode->storage_path);
            }

            // Delete episode record
            $episode->delete();

            return response()->json([
                'message' => 'Episode deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting episode: ' . $e->getMessage(),
            ], 500);
        }
    }
}
```

### 5.2 Database Migration for Storage Path

Create a migration to add the `storage_path` column:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->string('storage_path')->nullable()->after('url');
        });
    }

    public function down()
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn('storage_path');
        });
    }
};
```

### 5.3 Update Episode Model

Add the new field to the fillable array:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Episode extends Model
{
    protected $fillable = [
        'title',
        'description',
        'filename',
        'url',
        'file_size',
        'format',
        'published_date',
        'duration',
        'storage_path', // Add this field
    ];

    // ... existing model code ...
}
```

## 6. Migration Strategy from Local Storage

### 6.1 Migration Command

Create an Artisan command to migrate existing files:

```php
<?php

namespace App\Console\Commands;

use App\Models\Episode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateAudioToR2 extends Command
{
    protected $signature = 'audio:migrate-to-r2 {--dry-run : Show what would be migrated without actually doing it}';
    protected $description = 'Migrate existing audio files from local storage to Cloudflare R2';

    public function handle()
    {
        $episodes = Episode::whereNull('storage_path')->get();
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No files will be actually migrated');
        }

        $this->info("Found {$episodes->count()} episodes to migrate");

        foreach ($episodes as $episode) {
            $localPath = public_path('audios/' . $episode->filename);
            
            if (!file_exists($localPath)) {
                $this->warn("Local file not found: {$localPath}");
                continue;
            }

            $r2Path = 'audios/' . $episode->filename;
            
            if ($dryRun) {
                $this->line("Would migrate: {$episode->filename}");
                continue;
            }

            try {
                // Upload to R2
                $success = Storage::disk('r2')->put($r2Path, file_get_contents($localPath), 'public');
                
                if ($success) {
                    // Update episode record
                    $episode->update([
                        'url' => Storage::disk('r2')->url($r2Path),
                        'storage_path' => $r2Path,
                    ]);
                    
                    $this->info("Migrated: {$episode->filename}");
                    
                    // Optionally delete local file after successful migration
                    // unlink($localPath);
                } else {
                    $this->error("Failed to migrate: {$episode->filename}");
                }
            } catch (\Exception $e) {
                $this->error("Error migrating {$episode->filename}: " . $e->getMessage());
            }
        }

        $this->info('Migration completed');
    }
}
```

### 6.2 Migration Steps

1. **Test Migration**:
   ```bash
   php artisan audio:migrate-to-r2 --dry-run
   ```

2. **Run Migration**:
   ```bash
   php artisan audio:migrate-to-r2
   ```

3. **Verify Migration**:
   - Check R2 bucket for uploaded files
   - Test audio playback from new URLs
   - Verify database records are updated

## 7. URL Generation and Access Patterns

### 7.1 Public URL Configuration

Configure R2 for public access:

```php
// In your controller or service
$publicUrl = Storage::disk('r2')->url('audios/filename.mp3');

// With custom domain
$customUrl = str_replace(
    config('filesystems.disks.r2.endpoint'),
    config('filesystems.disks.r2.url'),
    $publicUrl
);
```

### 7.2 Signed URLs (Optional)

For private content, use signed URLs:

```php
$signedUrl = Storage::disk('r2')->temporaryUrl(
    'audios/filename.mp3',
    now()->addHours(1)
);
```

## 8. Security Considerations

### 8.1 Access Control

- **Bucket Permissions**: Configure appropriate bucket policies
- **API Token Scope**: Use minimal required permissions
- **Environment Security**: Secure storage of credentials

### 8.2 Content Security

```php
// Validate file types before upload
$allowedMimeTypes = ['audio/mpeg', 'audio/mp4', 'audio/wav'];
if (!in_array($audioFile->getMimeType(), $allowedMimeTypes)) {
    throw new \Exception('Invalid file type');
}

// Validate file size
$maxSize = 100 * 1024 * 1024; // 100MB
if ($audioFile->getSize() > $maxSize) {
    throw new \Exception('File too large');
}
```

### 8.3 CORS Configuration

Configure CORS in R2 bucket settings:

```json
{
  "AllowedOrigins": ["https://yourdomain.com"],
  "AllowedMethods": ["GET", "HEAD"],
  "AllowedHeaders": ["*"],
  "MaxAgeSeconds": 3600
}
```

## 9. Performance Optimization

### 9.1 CDN Integration

- **Automatic CDN**: R2 integrates with Cloudflare's global network
- **Cache Headers**: Set appropriate cache headers for audio files
- **Compression**: Enable Brotli/Gzip compression where applicable

### 9.2 Lazy Loading

Implement lazy loading for audio files:

```javascript
// In your Vue components
const loadAudio = async (episodeId) => {
  const response = await fetch(`/api/episodes/${episodeId}/audio-url`);
  const { url } = await response.json();
  return url;
};
```

### 9.3 Preloading Strategy

```php
// Preload metadata without full file download
$audioElement = '<audio preload="metadata" src="' . $episode->url . '">';
```

## 10. Monitoring and Logging

### 10.1 Upload Monitoring

```php
// Add comprehensive logging
Log::info('R2 Upload Started', [
    'filename' => $filename,
    'size' => $fileSize,
    'user_id' => auth()->id(),
]);

Log::info('R2 Upload Completed', [
    'filename' => $filename,
    'url' => $publicUrl,
    'duration' => $uploadDuration,
]);
```

### 10.2 Error Handling

```php
try {
    $uploadSuccess = Storage::disk('r2')->put($filePath, $fileContent, 'public');
} catch (\Exception $e) {
    Log::error('R2 Upload Failed', [
        'filename' => $filename,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    // Fallback to local storage or show user-friendly error
    throw new \Exception('Cloud storage temporarily unavailable. Please try again later.');
}
```

## 11. Testing

### 11.1 Unit Tests

Update existing tests to work with R2:

```php
public function test_episode_upload_to_r2()
{
    Storage::fake('r2');
    
    $file = UploadedFile::fake()->create('test-audio.mp3', 1000, 'audio/mpeg');
    
    $response = $this->post('/episodes', [
        'title' => 'Test Episode',
        'audio_file' => $file,
    ]);
    
    Storage::disk('r2')->assertExists('audios/' . $file->hashName());
    $this->assertDatabaseHas('episodes', ['title' => 'Test Episode']);
}
```

### 11.2 Integration Tests

Test with actual R2 connection in staging environment.

## 12. Deployment Considerations

### 12.1 Environment Variables

Ensure all R2 credentials are properly set in production:

```bash
# In your deployment script
echo "R2_ACCESS_KEY_ID=${R2_ACCESS_KEY_ID}" >> .env
echo "R2_SECRET_ACCESS_KEY=${R2_SECRET_ACCESS_KEY}" >> .env
echo "R2_BUCKET=${R2_BUCKET}" >> .env
echo "R2_ENDPOINT=${R2_ENDPOINT}" >> .env
echo "R2_PUBLIC_URL=${R2_PUBLIC_URL}" >> .env
```

### 12.2 Rollback Strategy

Keep local files as backup during initial deployment:

```php
// In migration command, add option to keep local files
if (!$this->option('delete-local')) {
    $this->info('Local files preserved for rollback');
}
```

## 13. Cost Estimation

### 13.1 Storage Costs

- **Storage**: $0.015 per GB/month
- **Class A Operations**: $4.50 per million (uploads)
- **Class B Operations**: $0.36 per million (downloads)

### 13.2 Example Calculation

For 1000 episodes averaging 50MB each:
- Storage: 50GB × $0.015 = $0.75/month
- Uploads: 1000 × $4.50/1M = $0.0045
- Downloads: 10K/month × $0.36/1M = $0.0036

**Total**: ~$0.76/month for storage + minimal operation costs

## 14. Conclusion

Integrating Cloudflare R2 with your Laravel streaming application provides significant benefits in terms of cost, performance, and scalability. The migration can be done gradually with minimal downtime, and the implementation maintains compatibility with your existing codebase while adding cloud storage capabilities.

Follow the steps outlined in this document to successfully migrate from local audio storage to Cloudflare R2, ensuring your application is ready for scale and improved global performance.