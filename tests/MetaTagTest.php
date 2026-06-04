<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Seo\MetaTag;

#[CoversClass(MetaTag::class)]
final class MetaTagTest extends TestCase
{
    #[Test]
    public function throwsOnInvalidAttributeType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid meta attribute type "invalid"');

        new MetaTag(attributeType: 'invalid', attributeValue: 'foo', content: 'bar');
    }

    #[Test]
    public function gettersReturnCorrectValues(): void
    {
        $tag = MetaTag::name(name: 'robots', content: 'noindex');

        $this->assertSame('name', $tag->getAttributeType());
        $this->assertSame('robots', $tag->getAttributeValue());
        $this->assertSame('noindex', $tag->getContent());
    }

    #[Test]
    public function nameFactorySetCorrectAttributeType(): void
    {
        $tag = MetaTag::name(name: 'description', content: 'Hello');

        $this->assertSame('name', $tag->getAttributeType());
        $this->assertSame('description', $tag->getAttributeValue());
    }

    #[Test]
    public function propertyFactorySetsCorrectAttributeType(): void
    {
        $tag = MetaTag::property(property: 'og:title', content: 'My title');

        $this->assertSame('property', $tag->getAttributeType());
        $this->assertSame('og:title', $tag->getAttributeValue());
    }

    #[Test]
    public function httpEquivFactorySetsCorrectAttributeType(): void
    {
        $tag = MetaTag::httpEquiv(httpEquiv: 'refresh', content: '30');

        $this->assertSame('http-equiv', $tag->getAttributeType());
        $this->assertSame('refresh', $tag->getAttributeValue());
    }

    /** @return Generator<string, array{string}> */
    public static function attributeTypeProvider(): Generator
    {
        yield 'name' => ['name'];
        yield 'property' => ['property'];
        yield 'http-equiv' => ['http-equiv'];
    }

    #[Test]
    #[DataProvider('attributeTypeProvider')]
    public function acceptsValidAttributeTypes(string $type): void
    {
        $tag = new MetaTag(attributeType: $type, attributeValue: 'foo', content: 'bar');

        $this->assertSame($type, $tag->getAttributeType());
    }
}
