<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

/**
 * Declarative description of a page's SEO metadata, modelled after the Next.js
 * Metadata API. All fields are optional; site-wide values come from
 * {@see MetadataDefaults} and are merged in by {@see SeoInjection}.
 *
 * @api
 */
final readonly class Metadata
{
    private ?Title $title;

    /** @var list<string> */
    private array $keywords;

    /** @var list<Author> */
    private array $authors;

    /** @var list<JsonLd> */
    private array $jsonLd;

    /** @var list<MetaTag> */
    private array $other;

    /**
     * @param list<string> $keywords
     * @param list<Author> $authors
     * @param list<JsonLd> $jsonLd
     * @param list<MetaTag> $other
     */
    public function __construct(
        string|Title|null $title = null,
        private ?string $description = null,
        array $keywords = [],
        array $authors = [],
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
        array $jsonLd = [],
        array $other = [],
    ) {
        $this->title = is_string($title) ? Title::of($title) : $title;
        $this->keywords = $keywords;
        $this->authors = $authors;
        $this->jsonLd = $jsonLd;
        $this->other = $other;
    }

    public function getTitle(): ?Title
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
