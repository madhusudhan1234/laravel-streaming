<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\EmbedController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class EmbedControllerTest extends TestCase
{
    public function test_build_embed_code_uses_url_and_escapes_title_from_array(): void
    {
        $controller = new EmbedController;
        $method = new ReflectionMethod(EmbedController::class, 'buildEmbedCode');
        $method->setAccessible(true);

        $embedUrl = 'http://localhost/embed/1';
        $episode = ['title' => 'A <Test> & "Episode"'];

        $html = $method->invoke($controller, $embedUrl, $episode);

        $this->assertStringContainsString('src="http://localhost/embed/1"', $html);
        $this->assertStringContainsString('title="A &lt;Test&gt; &amp; &quot;Episode&quot;"', $html);
        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringContainsString('allow="autoplay"', $html);
    }

    public function test_build_embed_code_uses_title_from_model_like_object(): void
    {
        $controller = new EmbedController;
        $method = new ReflectionMethod(EmbedController::class, 'buildEmbedCode');
        $method->setAccessible(true);

        $episode = new class
        {
            public string $title = 'Plain Title';
        };

        $html = $method->invoke($controller, 'http://localhost/embed/2', $episode);

        $this->assertStringContainsString('title="Plain Title"', $html);
    }
}
