<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

/**
 * Applies site defaults and social-metadata fallbacks to page metadata.
 *
 * @api
 */
final readonly class MetadataResolver
{
    public function resolve(
        ?Metadata $metadata = null,
        MetadataDefaults $defaults = new MetadataDefaults(),
    ): ResolvedMetadata {
        $title = $this->resolveTitle($metadata?->getTitle(), $defaults->getTitle());
        $description = $metadata?->getDescription();
        $openGraph = $this->resolveOpenGraph(
            page: $metadata?->getOpenGraph(),
            defaults: $defaults->getOpenGraph(),
            title: $title,
            description: $description,
        );

        return new ResolvedMetadata(
            metadataBase: $defaults->getMetadataBase(),
            title: $title,
            description: $description,
            keywords: $metadata?->getKeywords() ?? [],
            authors: $metadata?->getAuthors() ?? [],
            applicationName: $metadata?->getApplicationName() ?? $defaults->getApplicationName(),
            generator: $metadata?->getGenerator() ?? $defaults->getGenerator(),
            creator: $metadata?->getCreator(),
            publisher: $metadata?->getPublisher(),
            themeColor: $metadata?->getThemeColor() ?? $defaults->getThemeColor(),
            colorScheme: $metadata?->getColorScheme() ?? $defaults->getColorScheme(),
            robots: $metadata?->getRobots() ?? $defaults->getRobots(),
            alternates: $metadata?->getAlternates(),
            openGraph: $openGraph,
            twitter: $this->resolveTwitter(
                page: $metadata?->getTwitter(),
                defaults: $defaults->getTwitter(),
                openGraph: $openGraph,
                title: $title,
                description: $description,
            ),
            icons: $metadata?->getIcons() ?? $defaults->getIcons(),
            manifest: $metadata?->getManifest(),
            verification: $metadata?->getVerification() ?? $defaults->getVerification(),
            jsonLd: [...$defaults->getJsonLd(), ...($metadata?->getJsonLd() ?? [])],
            other: [...$defaults->getOther(), ...($metadata?->getOther() ?? [])],
        );
    }

    private function resolveTitle(?Title $page, ?Title $defaults): string
    {
        if (!$page instanceof Title) {
            return $defaults?->getDefault() ?? '';
        }

        $value = $page->getValue() ?? '';

        if ($page->isAbsolute()) {
            return $value;
        }

        $template = $defaults?->getTemplate();

        return $template !== null ? str_replace('%s', $value, $template) : $value;
    }

    private function resolveOpenGraph(
        ?OpenGraph $page,
        ?OpenGraph $defaults,
        string $title,
        ?string $description,
    ): ?OpenGraph {
        if (!$page instanceof OpenGraph && !$defaults instanceof OpenGraph) {
            return null;
        }

        return new OpenGraph(
            title: $page?->getTitle() ?? $defaults?->getTitle() ?? ($title !== '' ? $title : null),
            description: $page?->getDescription() ?? $defaults?->getDescription() ?? $description,
            type: $page?->getType() ?? $defaults?->getType() ?? 'website',
            url: $page?->getUrl() ?? $defaults?->getUrl(),
            siteName: $page?->getSiteName() ?? $defaults?->getSiteName(),
            locale: $page?->getLocale() ?? $defaults?->getLocale(),
            images: ($page instanceof OpenGraph && $page->getImages() !== [])
                ? $page->getImages()
                : ($defaults?->getImages() ?? []),
        );
    }

    private function resolveTwitter(
        ?TwitterCard $page,
        ?TwitterCard $defaults,
        ?OpenGraph $openGraph,
        string $title,
        ?string $description,
    ): ?TwitterCard {
        if (!$page instanceof TwitterCard && !$defaults instanceof TwitterCard) {
            return null;
        }

        $images = ($page instanceof TwitterCard && $page->getImages() !== [])
            ? $page->getImages()
            : ($defaults?->getImages() ?? []);

        if ($images === [] && $openGraph instanceof OpenGraph) {
            $images = array_map(static fn(OgImage $image): string => $image->getUrl(), $openGraph->getImages());
        }

        return new TwitterCard(
            card: $page?->getCard() ?? $defaults?->getCard() ?? 'summary_large_image',
            site: $page?->getSite() ?? $defaults?->getSite(),
            creator: $page?->getCreator() ?? $defaults?->getCreator(),
            title: $page?->getTitle() ?? $defaults?->getTitle() ?? $openGraph?->getTitle() ?? ($title !== '' ? $title : null),
            description: $page?->getDescription() ?? $defaults?->getDescription() ?? $openGraph?->getDescription() ?? $description,
            images: $images,
        );
    }
}
