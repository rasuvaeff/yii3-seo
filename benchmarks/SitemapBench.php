<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Benchmarks;

use DateTimeImmutable;
use Generator;
use Rasuvaeff\Yii3Seo\ChangeFrequency;
use Rasuvaeff\Yii3Seo\Sitemap;
use Rasuvaeff\Yii3Seo\SitemapImage;
use Rasuvaeff\Yii3Seo\SitemapUrl;
use Testo\Bench;

final class SitemapBench
{
    private const int URL_COUNT = 1_000;

    #[Bench(
        callables: [
            'rich' => [self::class, 'renderRich'],
        ],
        calls: 10,
        iterations: 5,
    )]
    public static function renderMinimal(): int
    {
        return self::render(self::minimalUrls());
    }

    public static function renderRich(): int
    {
        return self::render(self::richUrls());
    }

    /** @param iterable<SitemapUrl> $urls */
    private static function render(iterable $urls): int
    {
        $bytes = 0;

        foreach ((new Sitemap($urls, 'https://example.com'))->toChunks() as $chunk) {
            $bytes += strlen($chunk);
        }

        return $bytes;
    }

    /** @return Generator<int, SitemapUrl> */
    private static function minimalUrls(): Generator
    {
        for ($i = 0; $i < self::URL_COUNT; ++$i) {
            yield new SitemapUrl("/products/{$i}");
        }
    }

    /** @return Generator<int, SitemapUrl> */
    private static function richUrls(): Generator
    {
        $lastModified = new DateTimeImmutable('2026-07-29T10:00:00+00:00');

        for ($i = 0; $i < self::URL_COUNT; ++$i) {
            yield new SitemapUrl(
                loc: "/products/{$i}",
                lastModified: $lastModified,
                changeFrequency: ChangeFrequency::Weekly,
                priority: 0.8,
                images: [new SitemapImage("/images/{$i}.jpg")],
                alternates: ['en' => "/products/{$i}", 'de' => "/de/produkte/{$i}"],
            );
        }
    }
}
