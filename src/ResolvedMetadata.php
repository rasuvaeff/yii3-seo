<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

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

    /**
     * Exports the resolved metadata as a normalized array for JSON APIs, SPA
     * payloads, preview tooling and debugging.
     *
     * Crawler-facing URLs (canonical, hreflang, `og:url`, images) are resolved
     * against `metadataBase` exactly as the HTML renderer resolves them, so a
     * relative URL without a configured base throws the same exception it would
     * throw while rendering. Icon and manifest URLs are exported as configured.
     *
     * `null` values and empty collections are omitted, so the array carries the
     * same information as the rendered head. The title is always present.
     *
     * @throws InvalidArgumentException if a crawler-facing URL cannot be
     * resolved against `metadataBase`
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $resolver = new UrlResolver($this->metadataBase);

        return self::withoutEmpty([
            'metadataBase' => $this->metadataBase,
            'title' => $this->title,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'authors' => array_map($this->authorToArray(...), $this->authors),
            'applicationName' => $this->applicationName,
            'generator' => $this->generator,
            'creator' => $this->creator,
            'publisher' => $this->publisher,
            'themeColor' => $this->themeColor,
            'colorScheme' => $this->colorScheme,
            'robots' => $this->robots instanceof \Rasuvaeff\Yii3Seo\Robots ? $this->robotsToArray($this->robots) : null,
            'alternates' => $this->alternates instanceof \Rasuvaeff\Yii3Seo\Alternates
                ? $this->alternatesToArray($this->alternates, $resolver)
                : null,
            'openGraph' => $this->openGraph instanceof \Rasuvaeff\Yii3Seo\OpenGraph
                ? $this->openGraphToArray($this->openGraph, $resolver)
                : null,
            'twitter' => $this->twitter instanceof \Rasuvaeff\Yii3Seo\TwitterCard
                ? $this->twitterToArray($this->twitter, $resolver)
                : null,
            'icons' => $this->icons instanceof \Rasuvaeff\Yii3Seo\Icons ? array_map($this->iconToArray(...), $this->icons->all()) : [],
            'manifest' => $this->manifest,
            'verification' => $this->verification instanceof \Rasuvaeff\Yii3Seo\Verification
                ? $this->verificationToArray($this->verification)
                : null,
            'jsonLd' => array_map(static fn(JsonLd $block): array => $block->getData(), $this->jsonLd),
            'other' => array_map($this->metaTagToArray(...), $this->other),
        ]);
    }

    /** @return array<string, mixed> */
    private function authorToArray(Author $author): array
    {
        return self::withoutEmpty([
            'name' => $author->getName(),
            'url' => $author->getUrl(),
        ]);
    }

    /** @return array<string, mixed> */
    private function robotsToArray(Robots $robots): array
    {
        return self::withoutEmpty([
            'directives' => $robots->getDirectives(),
            'googleBot' => $robots->getGoogleBotDirectives(),
        ]);
    }

    /** @return array<string, mixed> */
    private function alternatesToArray(Alternates $alternates, UrlResolver $resolver): array
    {
        $canonical = $alternates->getCanonical();

        return self::withoutEmpty([
            'canonical' => $canonical === null ? null : $resolver->resolve($canonical),
            'languages' => array_map(
                $resolver->resolve(...),
                $alternates->getLanguages(),
            ),
        ]);
    }

    /** @return array<string, mixed> */
    private function openGraphToArray(OpenGraph $openGraph, UrlResolver $resolver): array
    {
        $url = $openGraph->getUrl();

        return self::withoutEmpty([
            'title' => $openGraph->getTitle(),
            'description' => $openGraph->getDescription(),
            'type' => $openGraph->getType() ?? 'website',
            'url' => $url === null ? null : $resolver->resolve($url),
            'siteName' => $openGraph->getSiteName(),
            'locale' => $openGraph->getLocale(),
            'images' => array_map(
                static fn(OgImage $image): array => self::ogImageToArray($image, $resolver),
                $openGraph->getImages(),
            ),
        ]);
    }

    /** @return array<string, mixed> */
    private static function ogImageToArray(OgImage $image, UrlResolver $resolver): array
    {
        return self::withoutEmpty([
            'url' => $resolver->resolve($image->getUrl()),
            'width' => $image->getWidth(),
            'height' => $image->getHeight(),
            'alt' => $image->getAlt(),
            'type' => $image->getType(),
        ]);
    }

    /** @return array<string, mixed> */
    private function twitterToArray(TwitterCard $twitter, UrlResolver $resolver): array
    {
        return self::withoutEmpty([
            'card' => $twitter->getCard() ?? 'summary_large_image',
            'site' => $twitter->getSite(),
            'creator' => $twitter->getCreator(),
            'title' => $twitter->getTitle(),
            'description' => $twitter->getDescription(),
            'images' => array_map(
                $resolver->resolve(...),
                $twitter->getImages(),
            ),
        ]);
    }

    /** @return array<string, mixed> */
    private function iconToArray(Icon $icon): array
    {
        return self::withoutEmpty([
            'rel' => $icon->getRel(),
            'url' => $icon->getUrl(),
            'sizes' => $icon->getSizes(),
            'type' => $icon->getType(),
        ]);
    }

    /** @return array<string, mixed> */
    private function verificationToArray(Verification $verification): array
    {
        return self::withoutEmpty([
            'google' => $verification->getGoogle(),
            'yandex' => $verification->getYandex(),
            'bing' => $verification->getBing(),
            'other' => $verification->getOther(),
        ]);
    }

    /** @return array<string, mixed> */
    private function metaTagToArray(MetaTag $metaTag): array
    {
        return [
            'attributeType' => $metaTag->getAttributeType(),
            'attributeValue' => $metaTag->getAttributeValue(),
            'content' => $metaTag->getContent(),
        ];
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private static function withoutEmpty(array $values): array
    {
        return array_filter($values, static fn(mixed $value): bool => $value !== null && $value !== []);
    }
}
