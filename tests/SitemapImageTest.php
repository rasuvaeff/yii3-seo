<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\SitemapImage;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(SitemapImage::class)]
final class SitemapImageTest
{
    public function keepsTheLocationAsGiven(): void
    {
        Assert::same((new SitemapImage('/images/a.png'))->getLoc(), '/images/a.png');
    }

    public function throwsOnEmptyLocation(): void
    {
        try {
            new SitemapImage('');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Sitemap image URL must not be empty');
        }
    }
}
