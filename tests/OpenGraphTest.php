<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Seo\OgImage;
use Rasuvaeff\Yii3Seo\OpenGraph;

#[CoversClass(OpenGraph::class)]
final class OpenGraphTest extends TestCase
{
    #[Test]
    public function gettersReturnValues(): void
    {
        $image = new OgImage(url: '/og.jpg');
        $og = new OpenGraph(
            title: 'T',
            description: 'D',
            type: 'article',
            url: 'https://example.com',
            siteName: 'S',
            locale: 'en_US',
            images: [$image],
        );

        $this->assertSame('T', $og->getTitle());
        $this->assertSame('D', $og->getDescription());
        $this->assertSame('article', $og->getType());
        $this->assertSame('https://example.com', $og->getUrl());
        $this->assertSame('S', $og->getSiteName());
        $this->assertSame('en_US', $og->getLocale());
        $this->assertSame([$image], $og->getImages());
    }

    #[Test]
    public function defaultsAreEmpty(): void
    {
        $og = new OpenGraph();

        $this->assertNull($og->getTitle());
        $this->assertNull($og->getDescription());
        $this->assertNull($og->getType());
        $this->assertNull($og->getUrl());
        $this->assertNull($og->getSiteName());
        $this->assertNull($og->getLocale());
        $this->assertSame([], $og->getImages());
    }

    #[Test]
    public function throwsOnEmptyType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OpenGraph type must not be empty');

        new OpenGraph(type: '');
    }

    #[Test]
    public function throwsOnEmptyUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OpenGraph URL must not be empty');

        new OpenGraph(url: '');
    }
}
