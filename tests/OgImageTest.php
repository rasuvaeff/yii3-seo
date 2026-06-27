<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\OgImage;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(OgImage::class)]
final class OgImageTest
{
    public function gettersReturnValues(): void
    {
        $image = new OgImage(url: '/og.jpg', width: 1200, height: 630, alt: 'Alt', type: 'image/jpeg');

        Assert::same($image->getUrl(), '/og.jpg');
        Assert::same($image->getWidth(), 1200);
        Assert::same($image->getHeight(), 630);
        Assert::same($image->getAlt(), 'Alt');
        Assert::same($image->getType(), 'image/jpeg');
    }

    public function optionalFieldsDefaultToNull(): void
    {
        $image = new OgImage(url: '/og.jpg');

        Assert::null($image->getWidth());
        Assert::null($image->getHeight());
        Assert::null($image->getAlt());
        Assert::null($image->getType());
    }

    public function throwsOnEmptyUrl(): void
    {
        try {
            new OgImage(url: '');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('OpenGraph image URL must not be empty');
        }
    }

    #[DataProvider('nonPositiveProvider')]
    public function throwsOnNonPositiveWidth(int $value): void
    {
        try {
            new OgImage(url: '/og.jpg', width: $value);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains("OpenGraph image width must be positive, got {$value}");
        }
    }

    #[DataProvider('nonPositiveProvider')]
    public function throwsOnNonPositiveHeight(int $value): void
    {
        try {
            new OgImage(url: '/og.jpg', height: $value);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains("OpenGraph image height must be positive, got {$value}");
        }
    }

    /** @return iterable<string, array{int}> */
    public static function nonPositiveProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-10];
    }
}
