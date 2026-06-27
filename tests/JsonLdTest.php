<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use Rasuvaeff\Yii3Seo\JsonLd;
use RuntimeException;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(JsonLd::class)]
final class JsonLdTest
{
    public function rendersScriptTag(): void
    {
        $jsonLd = JsonLd::fromArray(['@type' => 'WebPage', 'name' => 'Home']);
        $html = $jsonLd->toHtml();

        Assert::true(str_starts_with($html, '<script type="application/ld+json">'));
        Assert::true(str_ends_with($html, '</script>'));
    }

    public function outputIsDeterministic(): void
    {
        $data = ['@type' => 'WebPage', 'name' => 'Home', 'url' => 'https://example.com'];
        $jsonLd = JsonLd::fromArray($data);

        Assert::same($jsonLd->toHtml(), $jsonLd->toHtml());
    }

    public function escapesClosingScriptTag(): void
    {
        $jsonLd = JsonLd::fromArray(['name' => '</script><script>alert(1)</script>']);
        $html = $jsonLd->toHtml();

        Assert::string($html)->notContains('</script><script>');
    }

    public function encodesUnicodeWithoutEscaping(): void
    {
        $jsonLd = JsonLd::fromArray(['name' => 'Привет']);
        $html = $jsonLd->toHtml();

        Assert::string($html)->contains('Привет');
    }

    public function doesNotEscapeForwardSlashes(): void
    {
        $jsonLd = JsonLd::fromArray(['url' => 'https://example.com/page']);
        $html = $jsonLd->toHtml();

        Assert::string($html)->contains('https://example.com/page');
    }

    public function fromArrayFactoryWorks(): void
    {
        $data = ['@type' => 'Article'];
        $jsonLd = JsonLd::fromArray($data);

        Assert::same($jsonLd->getData(), $data);
    }

    public function getDataReturnsOriginal(): void
    {
        $data = ['@context' => 'https://schema.org', '@type' => 'Organization'];
        $jsonLd = new JsonLd(data: $data);

        Assert::same($jsonLd->getData(), $data);
    }

    public function throwsOnNonSerializableData(): void
    {
        $data = [];
        $data['value'] = &$data;

        try {
            JsonLd::fromArray($data)->toHtml();
            Assert::fail('Expected RuntimeException');
        } catch (RuntimeException $e) {
            Assert::string($e->getMessage())->contains('Failed to encode JSON-LD data');
        }
    }
}
