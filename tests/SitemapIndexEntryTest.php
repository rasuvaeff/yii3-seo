<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\SitemapIndexEntry;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(SitemapIndexEntry::class)]
final class SitemapIndexEntryTest
{
    public function exposesLocationAndLastModified(): void
    {
        $lastModified = new DateTimeImmutable('2026-07-29T10:00:00+00:00');
        $entry = new SitemapIndexEntry('/sitemap-1.xml', $lastModified);

        Assert::same($entry->getLoc(), '/sitemap-1.xml');
        Assert::same($entry->getLastModified(), $lastModified);
    }

    public function lastModifiedIsOptional(): void
    {
        Assert::null((new SitemapIndexEntry('/sitemap-1.xml'))->getLastModified());
    }

    public function throwsOnEmptyLocation(): void
    {
        try {
            new SitemapIndexEntry('');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Sitemap index entry URL must not be empty');
        }
    }
}
