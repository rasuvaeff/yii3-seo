<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * A single `<sitemap>` entry of a {@see SitemapIndex}. The location may be
 * relative and is resolved against `metadataBase` at render time.
 *
 * @api
 */
final readonly class SitemapIndexEntry
{
    public function __construct(
        private string $loc,
        private ?DateTimeImmutable $lastModified = null,
    ) {
        if ($loc === '') {
            throw new InvalidArgumentException('Sitemap index entry URL must not be empty');
        }
    }

    public function getLoc(): string
    {
        return $this->loc;
    }

    public function getLastModified(): ?DateTimeImmutable
    {
        return $this->lastModified;
    }
}
