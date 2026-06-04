<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

/**
 * @api
 */
final readonly class MetaTag
{
    private const array VALID_ATTRIBUTE_TYPES = ['name', 'property', 'http-equiv'];

    public function __construct(
        private string $attributeType,
        private string $attributeValue,
        private string $content,
    ) {
        if (!in_array($attributeType, self::VALID_ATTRIBUTE_TYPES, strict: true)) {
            throw new InvalidArgumentException("Invalid meta attribute type \"{$attributeType}\"");
        }
    }

    public static function name(string $name, string $content): self
    {
        return new self(attributeType: 'name', attributeValue: $name, content: $content);
    }

    public static function property(string $property, string $content): self
    {
        return new self(attributeType: 'property', attributeValue: $property, content: $content);
    }

    public static function httpEquiv(string $httpEquiv, string $content): self
    {
        return new self(attributeType: 'http-equiv', attributeValue: $httpEquiv, content: $content);
    }

    public function getAttributeType(): string
    {
        return $this->attributeType;
    }

    public function getAttributeValue(): string
    {
        return $this->attributeValue;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
