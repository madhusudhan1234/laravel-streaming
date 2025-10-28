<?php

namespace Tests\Unit;

use App\Http\Controllers\EmbedController;
use App\Models\Episode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmbedControllerTest extends TestCase
{
    use RefreshDatabase;

    private EmbedController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new EmbedController;

        // Create test episodes in database
        $this->createTestEpisodes();
    }

    private function createTestEpisodes()
    {
        Episode::create([
            'id' => 1,
            'title' => 'Test Episode 1',
            'description' => 'Test description 1',
            'filename' => 'test-episode-1.mp3',
            'url' => '/audios/test-episode-1.mp3',
            'file_size' => '1000 KB',
            'format' => 'mp3',
            'published_date' => '2024-01-01',
            'duration' => 5.5,
        ]);

        Episode::create([
            'id' => 2,
            'title' => 'Test Episode 2 <script>alert("xss")</script>',
            'description' => 'Test description 2',
            'filename' => 'test-episode-2.m4a',
            'url' => '/audios/test-episode-2.m4a',
            'file_size' => '2 MB',
            'format' => 'm4a',
            'published_date' => '2024-01-02',
            'duration' => 10.25,
        ]);
    }

    public function test_show_returns_embed_view_for_existing_episode()
    {
        $response = $this->controller->show(1);

        $this->assertEquals('embed', $response->name());
        $this->assertArrayHasKey('episode', $response->getData());

        $episode = $response->getData()['episode'];
        $this->assertEquals(1, $episode->id);
        $this->assertEquals('Test Episode 1', $episode->title);
    }

    public function test_show_returns_404_for_non_existent_episode()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Episode not found');

        $this->controller->show(999);
    }

    public function test_generate_embed_code_returns_json_response()
    {
        $response = $this->controller->generateEmbedCode(1);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('embedCode', $data);
        $this->assertArrayHasKey('embedUrl', $data);
        $this->assertArrayHasKey('episode', $data);

        $this->assertStringContainsString('<iframe', $data['embedCode']);
        $this->assertStringContainsString('frameborder="0"', $data['embedCode']);
        $this->assertStringContainsString('allow="autoplay"', $data['embedCode']);
    }

    public function test_generate_embed_code_escapes_html_in_title()
    {
        $response = $this->controller->generateEmbedCode(2);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $embedCode = $data['embedCode'];

        // Check that HTML is escaped in the title attribute
        $this->assertStringContainsString('&lt;script&gt;', $embedCode);
        $this->assertStringNotContainsString('<script>', $embedCode);
    }

    public function test_generate_embed_code_returns_404_for_non_existent_episode()
    {
        $response = $this->controller->generateEmbedCode(999);

        $this->assertEquals(404, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Episode not found', $data['error']);
    }

    public function test_embed_endpoint_returns_view()
    {
        $response = $this->get('/embed/1');

        $response->assertStatus(200);
        $response->assertViewIs('embed');
        $response->assertViewHas('episode');
    }

    public function test_embed_endpoint_returns_404_for_invalid_episode()
    {
        $response = $this->get('/embed/999');

        $response->assertStatus(404);
    }

    public function test_embed_code_generation_endpoint()
    {
        $response = $this->get('/api/embed/1/code');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'embedCode',
            'embedUrl',
            'episode' => ['id', 'title', 'filename', 'url'],
        ]);
    }

    public function test_embed_code_generation_endpoint_returns_404_for_invalid_id()
    {
        $response = $this->get('/api/embed/999/code');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Episode not found']);
    }

    public function test_embed_code_contains_proper_iframe_attributes()
    {
        $response = $this->controller->generateEmbedCode(1);
        $data = json_decode($response->getContent(), true);
        $embedCode = $data['embedCode'];

        $this->assertStringContainsString('width="100%"', $embedCode);
        $this->assertStringContainsString('height="120"', $embedCode);
        $this->assertStringContainsString('frameborder="0"', $embedCode);
        $this->assertStringContainsString('allow="autoplay"', $embedCode);
        $this->assertStringContainsString('title="Test Episode 1"', $embedCode);
    }

    public function test_embed_url_is_properly_formatted()
    {
        $response = $this->controller->generateEmbedCode(1);
        $data = json_decode($response->getContent(), true);

        $this->assertStringContainsString('/embed/1', $data['embedUrl']);
        $this->assertStringStartsWith('http', $data['embedUrl']);
    }
}
