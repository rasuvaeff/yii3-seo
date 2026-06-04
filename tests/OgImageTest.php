<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Seo\OgImage;

#[CoversClass(OgImage::class)]
final class OgImageTest extends TestCase
{
    #[Test]
    public function gettersReturnValues(): void
    {
        $image = new OgImage(url: '/og.jpg', width: 1200, height: 630, alt: 'Alt', type: 'image/jpeg');

        $this->assertSame('/og.jpg', $image->getUrl());
        $this->assertSame(1200, $image->getWidth());
        $this->assertSame(630, $image->getHeight());
        $this->assertSame('Alt', $image->getAlt());
        $this->assertSame('image/jpeg', $image->getType());
    }

    #[Test]
    public function optionalFieldsDefaultToNull(): void
    {
        $image = new OgImage(url: '/og.jpg');

        $this->assertNull($image->getWidth());
        $this->assertNull($image->getHeight());
        $this->assertNull($image->getAlt());
        $this->assertNull($image->getType());
    }

    #[Test]
    public function throwsOnEmptyUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OpenGraph image URL must not be empty');

        new OgImage(url: '');
    }

    #[Test]
    #[DataProvider('nonPositiveProvider')]
    public function throwsOnNonPositiveWidth(int $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("OpenGraph image width must be positive, got {$value}");

        new OgImage(url: '/og.jpg', width: $value);
    }

    #[Test]
    #[DataProvider('nonPositiveProvider')]
    public function throwsOnNonPositiveHeight(int $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("OpenGraph image height must be positive, got {$value}");

        new OgImage(url: '/og.jpg', height: $value);
    }

    /** @return iterable<string, array{int}> */
    public static function nonPositiveProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-10];
    }
}
