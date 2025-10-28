<?php

namespace Tests\Unit;

use App\Http\Controllers\AudioStreamController;
use App\Models\Episode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AudioStreamControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new AudioStreamController;

        // Create test audio file
        $this->createTestAudioFile();

        // Create test episodes in database
        $this->createTestEpisodes();
    }

    protected function tearDown(): void
    {
        // Clean up test audio file
        $testAudioPath = public_path('audios/test-audio.mp3');
        if (file_exists($testAudioPath)) {
            unlink($testAudioPath);
        }

        parent::tearDown();
    }

    private function createTestAudioFile()
    {
        $audioDir = public_path('audios');
        if (! is_dir($audioDir)) {
            mkdir($audioDir, 0755, true);
        }

        // Create a simple test audio file (just some bytes)
        file_put_contents(public_path('audios/test-audio.mp3'), 'test audio content');
    }

    private function createTestEpisodes()
    {
        Episode::create([
            'id' => 1,
            'title' => 'Test Episode 1',
            'filename' => 'test-audio.mp3',
            'url' => '/audios/test-audio.mp3',
            'description' => 'Test episode description',
            'published_date' => now(),
            'duration' => '5.0',
            'file_size' => '1 KB',
            'format' => 'mp3',
        ]);
    }

    public function test_stream_returns_404_for_non_existent_file()
    {
        $request = Request::create('/api/stream/non-existent.mp3');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Audio file not found');

        $this->controller->stream($request, 'non-existent.mp3');
    }

    public function test_stream_returns_streamed_response_for_existing_file()
    {
        $request = Request::create('/api/stream/test-audio.mp3');

        $response = $this->controller->stream($request, 'test-audio.mp3');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('audio/mpeg', $response->headers->get('Content-Type'));
        $this->assertEquals('bytes', $response->headers->get('Accept-Ranges'));
    }

    public function test_stream_handles_range_requests()
    {
        $request = Request::create('/api/stream/test-audio.mp3');
        $request->headers->set('Range', 'bytes=0-10');

        $response = $this->controller->stream($request, 'test-audio.mp3');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(206, $response->getStatusCode());
        $this->assertEquals('audio/mpeg', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('bytes 0-10/', $response->headers->get('Content-Range'));
    }

    public function test_stream_validates_filename_for_directory_traversal()
    {
        $request = Request::create('/api/stream/../../../etc/passwd');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Audio file not found');

        $this->controller->stream($request, '../../../etc/passwd');
    }

    public function test_get_episode_stream_url_returns_episode_data()
    {
        $response = $this->controller->getEpisodeStreamUrl(1);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('episode', $data);
        $this->assertArrayHasKey('stream_url', $data);
        $this->assertArrayHasKey('supports_range', $data);
        $this->assertTrue($data['supports_range']);
        $this->assertEquals(1, $data['episode']['id']);
    }

    public function test_get_episode_stream_url_returns_404_for_non_existent_episode()
    {
        $response = $this->controller->getEpisodeStreamUrl(999);

        $this->assertEquals(404, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Episode not found', $data['error']);
    }

    public function test_stream_endpoint_with_existing_file()
    {
        $response = $this->get('/api/stream/test-audio.mp3');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'audio/mpeg');
        $response->assertHeader('Accept-Ranges', 'bytes');
    }

    public function test_stream_endpoint_with_range_header()
    {
        $response = $this->get('/api/stream/test-audio.mp3', [
            'Range' => 'bytes=0-10',
        ]);

        $response->assertStatus(206);
        $response->assertHeader('Content-Type', 'audio/mpeg');
    }

    public function test_episode_stream_url_endpoint()
    {
        $response = $this->get('/api/episodes/1/stream');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'episode' => ['id', 'title', 'filename', 'url'],
            'stream_url',
            'supports_range',
        ]);
    }

    public function test_episode_stream_url_endpoint_returns_404_for_invalid_id()
    {
        $response = $this->get('/api/episodes/999/stream');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Episode not found']);
    }
}
