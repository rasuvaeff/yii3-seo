<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\SitemapLimits;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(SitemapLimits::class)]
final class SitemapLimitsTest
{
    public function defaultsToTheProtocolLimits(): void
    {
        $limits = new SitemapLimits();

        Assert::same($limits->getMaxUrls(), 50_000);
        Assert::same($limits->getMaxBytes(), 52_428_800);
    }

    public function acceptsStricterLimits(): void
    {
        $limits = new SitemapLimits(maxUrls: 10, maxBytes: 1_024);

        Assert::same($limits->getMaxUrls(), 10);
        Assert::same($limits->getMaxBytes(), 1_024);
    }

    public function acceptsTheSmallestUsableLimits(): void
    {
        $limits = new SitemapLimits(maxUrls: 1, maxBytes: 1);

        Assert::same($limits->getMaxUrls(), 1);
        Assert::same($limits->getMaxBytes(), 1);
    }

    #[DataProvider('invalidUrlLimitProvider')]
    public function throwsOnAnUnusableUrlLimit(int $maxUrls): void
    {
        try {
            new SitemapLimits(maxUrls: $maxUrls);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains("Invalid sitemap URL limit \"{$maxUrls}\"");
        }
    }

    #[DataProvider('invalidByteLimitProvider')]
    public function throwsOnAnUnusableByteLimit(int $maxBytes): void
    {
        try {
            new SitemapLimits(maxBytes: $maxBytes);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains("Invalid sitemap byte limit \"{$maxBytes}\"");
        }
    }

    /** @return iterable<string, array{int}> */
    public static function invalidUrlLimitProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
        yield 'above the protocol limit' => [50_001];
    }

    /** @return iterable<string, array{int}> */
    public static function invalidByteLimitProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
        yield 'above the protocol limit' => [52_428_801];
    }
}
