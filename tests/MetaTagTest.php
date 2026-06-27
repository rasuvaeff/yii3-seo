<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use Generator;
use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\MetaTag;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(MetaTag::class)]
final class MetaTagTest
{
    public function throwsOnInvalidAttributeType(): void
    {
        try {
            new MetaTag(attributeType: 'invalid', attributeValue: 'foo', content: 'bar');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Invalid meta attribute type "invalid"');
        }
    }

    public function gettersReturnCorrectValues(): void
    {
        $tag = MetaTag::name(name: 'robots', content: 'noindex');

        Assert::same($tag->getAttributeType(), 'name');
        Assert::same($tag->getAttributeValue(), 'robots');
        Assert::same($tag->getContent(), 'noindex');
    }

    public function nameFactorySetCorrectAttributeType(): void
    {
        $tag = MetaTag::name(name: 'description', content: 'Hello');

        Assert::same($tag->getAttributeType(), 'name');
        Assert::same($tag->getAttributeValue(), 'description');
    }

    public function propertyFactorySetsCorrectAttributeType(): void
    {
        $tag = MetaTag::property(property: 'og:title', content: 'My title');

        Assert::same($tag->getAttributeType(), 'property');
        Assert::same($tag->getAttributeValue(), 'og:title');
    }

    public function httpEquivFactorySetsCorrectAttributeType(): void
    {
        $tag = MetaTag::httpEquiv(httpEquiv: 'refresh', content: '30');

        Assert::same($tag->getAttributeType(), 'http-equiv');
        Assert::same($tag->getAttributeValue(), 'refresh');
    }

    /** @return Generator<string, array{string}> */
    public static function attributeTypeProvider(): Generator
    {
        yield 'name' => ['name'];
        yield 'property' => ['property'];
        yield 'http-equiv' => ['http-equiv'];
    }

    #[DataProvider('attributeTypeProvider')]
    public function acceptsValidAttributeTypes(string $type): void
    {
        $tag = new MetaTag(attributeType: $type, attributeValue: 'foo', content: 'bar');

        Assert::same($tag->getAttributeType(), $type);
    }
}
