<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

/**
 * A single `<link>` icon. URL is emitted as-is (browser-resolved), so relative
 * paths are allowed and not validated against `metadataBase`.
 *
 * @api
 */
final readonly class Icon
{
    public function __construct(
        private string $rel,
        private string $url,
        private ?string $sizes = null,
        private ?string $type = null,
    ) {
        if ($rel === '') {
            throw new InvalidArgumentException('Icon rel must not be empty');
        }

        if ($url === '') {
            throw new InvalidArgumentException('Icon URL must not be empty');
        }
    }

    public function getRel(): string
    {
        return $this->rel;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getSizes(): ?string
    {
        return $this->sizes;
    }

    public function getType(): ?string
    {
        return $this->type;
    }
}
