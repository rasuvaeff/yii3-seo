<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Seo\JsonLd;
use RuntimeException;

#[CoversClass(JsonLd::class)]
final class JsonLdTest extends TestCase
{
    #[Test]
    public function rendersScriptTag(): void
    {
        $jsonLd = JsonLd::fromArray(['@type' => 'WebPage', 'name' => 'Home']);
        $html = $jsonLd->toHtml();

        $this->assertStringStartsWith('<script type="application/ld+json">', $html);
        $this->assertStringEndsWith('</script>', $html);
    }

    #[Test]
    public function outputIsDeterministic(): void
    {
        $data = ['@type' => 'WebPage', 'name' => 'Home', 'url' => 'https://example.com'];
        $jsonLd = JsonLd::fromArray($data);

        $this->assertSame($jsonLd->toHtml(), $jsonLd->toHtml());
    }

    #[Test]
    public function escapesClosingScriptTag(): void
    {
        $jsonLd = JsonLd::fromArray(['name' => '</script><script>alert(1)</script>']);
        $html = $jsonLd->toHtml();

        $this->assertStringNotContainsString('</script><script>', $html);
    }

    #[Test]
    public function encodesUnicodeWithoutEscaping(): void
    {
        $jsonLd = JsonLd::fromArray(['name' => 'Привет']);
        $html = $jsonLd->toHtml();

        $this->assertStringContainsString('Привет', $html);
    }

    #[Test]
    public function doesNotEscapeForwardSlashes(): void
    {
        $jsonLd = JsonLd::fromArray(['url' => 'https://example.com/page']);
        $html = $jsonLd->toHtml();

        $this->assertStringContainsString('https://example.com/page', $html);
    }

    #[Test]
    public function fromArrayFactoryWorks(): void
    {
        $data = ['@type' => 'Article'];
        $jsonLd = JsonLd::fromArray($data);

        $this->assertSame($data, $jsonLd->getData());
    }

    #[Test]
    public function getDataReturnsOriginal(): void
    {
        $data = ['@context' => 'https://schema.org', '@type' => 'Organization'];
        $jsonLd = new JsonLd(data: $data);

        $this->assertSame($data, $jsonLd->getData());
    }

    #[Test]
    public function throwsOnNonSerializableData(): void
    {
        $data = [];
        $data['value'] = &$data;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to encode JSON-LD data');

        JsonLd::fromArray($data)->toHtml();
    }
}
