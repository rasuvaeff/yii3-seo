<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use DateTimeImmutable;
use Rasuvaeff\Yii3Seo\SitemapIndex;
use Rasuvaeff\Yii3Seo\SitemapIndexEntry;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(SitemapIndex::class)]
final class SitemapIndexTest
{
    public function wrapsEntriesIntoASitemapIndexDocument(): void
    {
        $index = new SitemapIndex(
            [
                new SitemapIndexEntry('/sitemap-1.xml', new DateTimeImmutable('2026-07-29T10:00:00+00:00')),
                new SitemapIndexEntry('/sitemap-2.xml'),
            ],
            'https://example.com',
        );

        Assert::same(
            implode('', iterator_to_array($index->toChunks())),
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
            . '<sitemap><loc>https://example.com/sitemap-1.xml</loc>'
            . "<lastmod>2026-07-29T10:00:00+00:00</lastmod></sitemap>\n"
            . "<sitemap><loc>https://example.com/sitemap-2.xml</loc></sitemap>\n"
            . "</sitemapindex>\n",
        );
    }

    public function anEmptyIndexIsStillAValidDocument(): void
    {
        $chunks = iterator_to_array((new SitemapIndex([]))->toChunks());

        Assert::same(count($chunks), 2);
        Assert::string($chunks[0])->contains('<sitemapindex');
        Assert::same($chunks[1], "</sitemapindex>\n");
    }

    public function emitsOneChunkPerEntryPlusTheEnvelope(): void
    {
        $index = new SitemapIndex(
            [new SitemapIndexEntry('/sitemap-1.xml'), new SitemapIndexEntry('/sitemap-2.xml')],
            'https://example.com',
        );

        Assert::same(count(iterator_to_array($index->toChunks())), 4);
    }
}
