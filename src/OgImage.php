<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

/**
 * URL may be absolute or relative; relative URLs are resolved against
 * `MetadataDefaults::metadataBase` at render time.
 *
 * @api
 */
final readonly class OgImage
{
    public function __construct(
        private string $url,
        private ?int $width = null,
        private ?int $height = null,
        private ?string $alt = null,
        private ?string $type = null,
    ) {
        if ($url === '') {
            throw new InvalidArgumentException('OpenGraph image URL must not be empty');
        }

        if ($width !== null && $width <= 0) {
            throw new InvalidArgumentException("OpenGraph image width must be positive, got {$width}");
        }

        if ($height !== null && $height <= 0) {
            throw new InvalidArgumentException("OpenGraph image height must be positive, got {$height}");
        }
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function getType(): ?string
    {
        return $this->type;
    }
}
