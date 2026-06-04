<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Seo\Icon;

#[CoversClass(Icon::class)]
final class IconTest extends TestCase
{
    #[Test]
    public function gettersReturnValues(): void
    {
        $icon = new Icon(rel: 'icon', url: '/favicon.ico', sizes: '32x32', type: 'image/x-icon');

        $this->assertSame('icon', $icon->getRel());
        $this->assertSame('/favicon.ico', $icon->getUrl());
        $this->assertSame('32x32', $icon->getSizes());
        $this->assertSame('image/x-icon', $icon->getType());
    }

    #[Test]
    public function optionalFieldsDefaultToNull(): void
    {
        $icon = new Icon(rel: 'icon', url: '/favicon.ico');

        $this->assertNull($icon->getSizes());
        $this->assertNull($icon->getType());
    }

    #[Test]
    public function throwsOnEmptyRel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Icon rel must not be empty');

        new Icon(rel: '', url: '/favicon.ico');
    }

    #[Test]
    public function throwsOnEmptyUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Icon URL must not be empty');

        new Icon(rel: 'icon', url: '');
    }
}
