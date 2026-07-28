<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

/**
 * Fully merged logical metadata produced by {@see MetadataResolver}.
 *
 * Crawler-facing URLs remain in their configured form and are resolved against
 * `metadataBase` by the rendering adapter. This keeps resolution independent of
 * a particular output format.
 *
 * @api
 */
final readonly class ResolvedMetadata
{
    /**
     * @param list<string> $keywords
     * @param list<Author> $authors
     * @param list<JsonLd> $jsonLd
     * @param list<MetaTag> $other
     */
    public function __construct(
        private ?string $metadataBase = null,
        private string $title = '',
        private ?string $description = null,
        private array $keywords = [],
        private array $authors = [],
        private ?string $applicationName = null,
        private ?string $generator = null,
        private ?string $creator = null,
        private ?string $publisher = null,
        private ?string $themeColor = null,
        private ?string $colorScheme = null,
        private ?Robots $robots = null,
        private ?Alternates $alternates = null,
        private ?OpenGraph $openGraph = null,
        private ?TwitterCard $twitter = null,
        private ?Icons $icons = null,
        private ?string $manifest = null,
        private ?Verification $verification = null,
        private array $jsonLd = [],
        private array $other = [],
    ) {}

    public function getMetadataBase(): ?string
    {
        return $this->metadataBase;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /** @return list<string> */
    public function getKeywords(): array
    {
        return $this->keywords;
    }

    /** @return list<Author> */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    public function getApplicationName(): ?string
    {
        return $this->applicationName;
    }

    public function getGenerator(): ?string
    {
        return $this->generator;
    }

    public function getCreator(): ?string
    {
        return $this->creator;
    }

    public function getPublisher(): ?string
    {
        return $this->publisher;
    }

    public function getThemeColor(): ?string
    {
        return $this->themeColor;
    }

    public function getColorScheme(): ?string
    {
        return $this->colorScheme;
    }

    public function getRobots(): ?Robots
    {
        return $this->robots;
    }

    public function getAlternates(): ?Alternates
    {
        return $this->alternates;
    }

    public function getOpenGraph(): ?OpenGraph
    {
        return $this->openGraph;
    }

    public function getTwitter(): ?TwitterCard
    {
        return $this->twitter;
    }

    public function getIcons(): ?Icons
    {
        return $this->icons;
    }

    public function getManifest(): ?string
    {
        return $this->manifest;
    }

    public function getVerification(): ?Verification
    {
        return $this->verification;
    }

    /** @return list<JsonLd> */
    public function getJsonLd(): array
    {
        return $this->jsonLd;
    }

    /** @return list<MetaTag> */
    public function getOther(): array
    {
        return $this->other;
    }
}
