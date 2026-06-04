<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

/**
 * Site-wide metadata defaults merged into every page's {@see Metadata}. Provide
 * a single instance via the `rasuvaeff/yii3-seo` `defaults` DI parameter.
 *
 * `metadataBase` is the absolute origin used to resolve relative crawler-facing
 * URLs (canonical, hreflang, og:image, og:url, twitter:image).
 *
 * @api
 */
final readonly class MetadataDefaults
{
    private ?Title $title;

    /** @var list<JsonLd> */
    private array $jsonLd;

    /** @var list<MetaTag> */
    private array $other;

    /**
     * @param list<JsonLd> $jsonLd
     * @param list<MetaTag> $other
     */
    public function __construct(
        private ?string $metadataBase = null,
        string|Title|null $title = null,
        private ?string $applicationName = null,
        private ?string $generator = null,
        private ?string $themeColor = null,
        private ?string $colorScheme = null,
        private ?Robots $robots = null,
        private ?OpenGraph $openGraph = null,
        private ?TwitterCard $twitter = null,
        private ?Icons $icons = null,
        private ?Verification $verification = null,
        array $jsonLd = [],
        array $other = [],
    ) {
        if ($metadataBase !== null && filter_var($metadataBase, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException("Invalid metadataBase URL \"{$metadataBase}\"");
        }

        $this->title = is_string($title) ? Title::of($title) : $title;
        $this->jsonLd = $jsonLd;
        $this->other = $other;
    }

    public function getMetadataBase(): ?string
    {
        return $this->metadataBase;
    }

    public function getTitle(): ?Title
    {
        return $this->title;
    }

    public function getApplicationName(): ?string
    {
        return $this->applicationName;
    }

    public function getGenerator(): ?string
    {
        return $this->generator;
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
