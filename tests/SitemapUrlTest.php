<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\ChangeFrequency;
use Rasuvaeff\Yii3Seo\SitemapImage;
use Rasuvaeff\Yii3Seo\SitemapUrl;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(SitemapUrl::class)]
final class SitemapUrlTest
{
    public function exposesEveryField(): void
    {
        $lastModified = new DateTimeImmutable('2026-07-29T10:00:00+00:00');
        $image = new SitemapImage('/i.png');

        $url = new SitemapUrl(
            loc: '/products/1',
            lastModified: $lastModified,
            changeFrequency: ChangeFrequency::Daily,
            priority: 0.8,
            images: [$image],
            alternates: ['de-DE' => '/de/produkte/1'],
        );

        Assert::same($url->getLoc(), '/products/1');
        Assert::same($url->getLastModified(), $lastModified);
        Assert::same($url->getChangeFrequency(), ChangeFrequency::Daily);
        Assert::same($url->getPriority(), 0.8);
        Assert::same($url->getImages(), [$image]);
        Assert::same($url->getAlternates(), ['de-DE' => '/de/produkte/1']);
    }

    public function defaultsEverythingButTheLocation(): void
    {
        $url = new SitemapUrl('/');

        Assert::null($url->getLastModified());
        Assert::null($url->getChangeFrequency());
        Assert::null($url->getPriority());
        Assert::same($url->getImages(), []);
        Assert::same($url->getAlternates(), []);
    }

    public function reindexesImagesToAList(): void
    {
        $image = new SitemapImage('/i.png');

        Assert::same((new SitemapUrl('/', images: [7 => $image]))->getImages(), [$image]);
    }

    public function throwsOnEmptyLocation(): void
    {
        try {
            new SitemapUrl('');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Sitemap URL must not be empty');
        }
    }

    #[DataProvider('boundaryPriorityProvider')]
    public function acceptsThePriorityBoundaries(float $priority): void
    {
        Assert::same((new SitemapUrl('/', priority: $priority))->getPriority(), $priority);
    }

    #[DataProvider('invalidPriorityProvider')]
    public function throwsOnPriorityOutsideTheProtocolRange(float $priority): void
    {
        try {
            new SitemapUrl('/', priority: $priority);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Invalid sitemap priority');
        }
    }

    public function throwsOnInvalidAlternateLocale(): void
    {
        try {
            new SitemapUrl('/', alternates: ['de_DE' => '/de']);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Invalid hreflang locale "de_DE"');
        }
    }

    public function throwsOnEmptyAlternateUrl(): void
    {
        try {
            new SitemapUrl('/', alternates: ['de' => '']);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Alternate URL for "de" must not be empty');
        }
    }

    /** @return iterable<string, array{float}> */
    public static function boundaryPriorityProvider(): iterable
    {
        yield 'lowest' => [0.0];
        yield 'highest' => [1.0];
    }

    /** @return iterable<string, array{float}> */
    public static function invalidPriorityProvider(): iterable
    {
        yield 'below zero' => [-0.1];
        yield 'above one' => [1.1];
    }
}
