<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * A single `<url>` entry of an XML sitemap.
 *
 * `loc`, image locations and alternate URLs may be relative: they are resolved
 * against `metadataBase` when the document is rendered, exactly as crawler-facing
 * head metadata is. A sitemap must not contain relative URLs, so rendering a
 * relative location without a configured base throws.
 *
 * @api
 */
final readonly class SitemapUrl
{
    /** @var list<SitemapImage> */
    private array $images;

    /** @var array<string, string> */
    private array $alternates;

    /**
     * @param array<array-key, SitemapImage> $images
     * @param array<string, string> $alternates locale => url
     */
    public function __construct(
        private string $loc,
        private ?DateTimeImmutable $lastModified = null,
        private ?ChangeFrequency $changeFrequency = null,
        private ?float $priority = null,
        array $images = [],
        array $alternates = [],
    ) {
        if ($loc === '') {
            throw new InvalidArgumentException('Sitemap URL must not be empty');
        }

        if ($priority !== null && ($priority < 0.0 || $priority > 1.0)) {
            throw new InvalidArgumentException("Invalid sitemap priority \"{$priority}\"");
        }

        foreach ($alternates as $locale => $url) {
            HreflangLocale::assert($locale);

            if ($url === '') {
                throw new InvalidArgumentException("Alternate URL for \"{$locale}\" must not be empty");
            }
        }

        $this->images = array_values($images);
        $this->alternates = $alternates;
    }

    public function getLoc(): string
    {
        return $this->loc;
    }

    public function getLastModified(): ?DateTimeImmutable
    {
        return $this->lastModified;
    }

    public function getChangeFrequency(): ?ChangeFrequency
    {
        return $this->changeFrequency;
    }

    public function getPriority(): ?float
    {
        return $this->priority;
    }

    /** @return list<SitemapImage> */
    public function getImages(): array
    {
        return $this->images;
    }

    /** @return array<string, string> */
    public function getAlternates(): array
    {
        return $this->alternates;
    }
}
