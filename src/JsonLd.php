<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use JsonException;
use RuntimeException;

/**
 * @api
 */
final readonly class JsonLd
{
    /** @param array<string, mixed> $data */
    public function __construct(private array $data) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(data: $data);
    }

    /** @return array<string, mixed> */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Encodes the block with `JSON_HEX_TAG`, so the result can never terminate
     * the surrounding `<script>` element.
     */
    public function toJson(): string
    {
        try {
            return json_encode(
                value: $this->data,
                flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $e) {
            throw new RuntimeException("Failed to encode JSON-LD data: {$e->getMessage()}", $e->getCode(), previous: $e);
        }
    }

    public function toHtml(): string
    {
        $json = $this->toJson();

        return "<script type=\"application/ld+json\">\n{$json}\n</script>";
    }
}
