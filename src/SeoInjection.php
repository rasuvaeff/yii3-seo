<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Link;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\Yii\View\Renderer\LinkTagsInjectionInterface;
use Yiisoft\Yii\View\Renderer\MetaTagsInjectionInterface;

/**
 * Merges per-request {@see Metadata} with site-wide {@see MetadataDefaults} and
 * feeds the result to `WebViewRenderer` as meta and link tags.
 *
 * @api
 */
final class SeoInjection implements MetaTagsInjectionInterface, LinkTagsInjectionInterface
{
    private ?Metadata $metadata = null;

    public function __construct(
        private readonly MetadataDefaults $defaults = new MetadataDefaults(),
    ) {}

    public function setMetadata(Metadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function clear(): void
    {
        $this->metadata = null;
    }

    public function reset(): void
    {
        $this->clear();
    }

    public function getTitle(): string
    {
        $pageTitle = $this->metadata?->getTitle();

        if ($pageTitle === null) {
            return $this->defaults->getTitle()?->getDefault() ?? '';
        }

        $value = $pageTitle->getValue() ?? '';

        if ($pageTitle->isAbsolute()) {
            return $value;
        }

        $template = $this->defaults->getTitle()?->getTemplate();

        return $template !== null ? str_replace('%s', $value, $template) : $value;
    }

    public function getJsonLdHtml(): string
    {
        $blocks = [...$this->defaults->getJsonLd(), ...($this->metadata?->getJsonLd() ?? [])];

        if ($blocks === []) {
            return '';
        }

        return implode("\n", array_map(static fn(JsonLd $block): string => $block->toHtml(), $blocks));
    }

    /** @return list<Meta> */
    #[\Override]
    public function getMetaTags(): array
    {
        $tags = [];

        $description = $this->metadata?->getDescription();

        if ($description !== null) {
            $tags[] = $this->metaName('description', $description);
        }

        $keywords = $this->metadata?->getKeywords() ?? [];

        if ($keywords !== []) {
            $tags[] = $this->metaName('keywords', implode(', ', $keywords));
        }

        foreach ($this->metadata?->getAuthors() ?? [] as $author) {
            $tags[] = $this->metaName('author', $author->getName());
        }

        $applicationName = $this->metadata?->getApplicationName() ?? $this->defaults->getApplicationName();

        if ($applicationName !== null) {
            $tags[] = $this->metaName('application-name', $applicationName);
        }

        $generator = $this->metadata?->getGenerator() ?? $this->defaults->getGenerator();

        if ($generator !== null) {
            $tags[] = $this->metaName('generator', $generator);
        }

        $creator = $this->metadata?->getCreator();

        if ($creator !== null) {
            $tags[] = $this->metaName('creator', $creator);
        }

        $publisher = $this->metadata?->getPublisher();

        if ($publisher !== null) {
            $tags[] = $this->metaName('publisher', $publisher);
        }

        $themeColor = $this->metadata?->getThemeColor() ?? $this->defaults->getThemeColor();

        if ($themeColor !== null) {
            $tags[] = $this->metaName('theme-color', $themeColor);
        }

        $colorScheme = $this->metadata?->getColorScheme() ?? $this->defaults->getColorScheme();

        if ($colorScheme !== null) {
            $tags[] = $this->metaName('color-scheme', $colorScheme);
        }

        $robots = $this->metadata?->getRobots() ?? $this->defaults->getRobots();

        if ($robots !== null) {
            $tags[] = $this->metaName('robots', implode(', ', $robots->getDirectives()));

            $googleBot = $robots->getGoogleBotDirectives();

            if ($googleBot !== []) {
                $tags[] = $this->metaName('googlebot', implode(', ', $googleBot));
            }
        }

        $verification = $this->metadata?->getVerification() ?? $this->defaults->getVerification();

        if ($verification !== null) {
            $tags = [...$tags, ...$this->verificationTags($verification)];
        }

        foreach ([...$this->defaults->getOther(), ...($this->metadata?->getOther() ?? [])] as $metaTag) {
            $tags[] = match ($metaTag->getAttributeType()) {
                'name' => $this->metaName($metaTag->getAttributeValue(), $metaTag->getContent()),
                'property' => $this->metaProperty($metaTag->getAttributeValue(), $metaTag->getContent()),
                'http-equiv' => Html::meta()->httpEquiv($metaTag->getAttributeValue())->content($metaTag->getContent()),
            };
        }

        $resolver = $this->resolver();
        $title = $this->getTitle();
        $og = $this->mergedOpenGraph();
        $ogTitle = $og?->getTitle() ?? ($title !== '' ? $title : null);
        $ogDescription = $og?->getDescription() ?? $description;

        if ($og !== null) {
            $tags = [...$tags, ...$this->openGraphTags($og, $ogTitle, $ogDescription, $resolver)];
        }

        $twitter = $this->mergedTwitter();

        if ($twitter !== null) {
            $tags = [...$tags, ...$this->twitterTags($twitter, $og, $ogTitle, $ogDescription, $resolver)];
        }

        return $tags;
    }

    /** @return array<array-key, Link> */
    #[\Override]
    public function getLinkTags(): array
    {
        $tags = [];
        $resolver = $this->resolver();
        $alternates = $this->metadata?->getAlternates();

        if ($alternates !== null) {
            if ($alternates->getCanonical() !== null) {
                $tags['canonical'] = Html::link()->rel('canonical')->href($resolver->resolve($alternates->getCanonical()));
            }

            foreach ($alternates->getLanguages() as $locale => $url) {
                $tags[] = Html::link()->rel('alternate')->attribute('hreflang', $locale)->href($resolver->resolve($url));
            }
        }

        $icons = $this->metadata?->getIcons() ?? $this->defaults->getIcons();

        if ($icons !== null) {
            foreach ($icons->all() as $icon) {
                $link = Html::link()->rel($icon->getRel())->href($icon->getUrl());

                if ($icon->getType() !== null) {
                    $link = $link->attribute('type', $icon->getType());
                }

                if ($icon->getSizes() !== null) {
                    $link = $link->attribute('sizes', $icon->getSizes());
                }

                $tags[] = $link;
            }
        }

        $manifest = $this->metadata?->getManifest();

        if ($manifest !== null) {
            $tags['manifest'] = Html::link()->rel('manifest')->href($manifest);
        }

        foreach ($this->metadata?->getAuthors() ?? [] as $author) {
            if ($author->getUrl() !== null) {
                $tags[] = Html::link()->rel('author')->href($author->getUrl());
            }
        }

        return $tags;
    }

    private function resolver(): UrlResolver
    {
        return new UrlResolver($this->defaults->getMetadataBase());
    }

    private function mergedOpenGraph(): ?OpenGraph
    {
        $defaults = $this->defaults->getOpenGraph();
        $page = $this->metadata?->getOpenGraph();

        if ($defaults === null && $page === null) {
            return null;
        }

        return new OpenGraph(
            title: $page?->getTitle() ?? $defaults?->getTitle(),
            description: $page?->getDescription() ?? $defaults?->getDescription(),
            type: $page?->getType() ?? $defaults?->getType(),
            url: $page?->getUrl() ?? $defaults?->getUrl(),
            siteName: $page?->getSiteName() ?? $defaults?->getSiteName(),
            locale: $page?->getLocale() ?? $defaults?->getLocale(),
            images: ($page !== null && $page->getImages() !== []) ? $page->getImages() : ($defaults?->getImages() ?? []),
        );
    }

    private function mergedTwitter(): ?TwitterCard
    {
        $defaults = $this->defaults->getTwitter();
        $page = $this->metadata?->getTwitter();

        if ($defaults === null && $page === null) {
            return null;
        }

        return new TwitterCard(
            card: $page?->getCard() ?? $defaults?->getCard(),
            site: $page?->getSite() ?? $defaults?->getSite(),
            creator: $page?->getCreator() ?? $defaults?->getCreator(),
            title: $page?->getTitle() ?? $defaults?->getTitle(),
            description: $page?->getDescription() ?? $defaults?->getDescription(),
            images: ($page !== null && $page->getImages() !== []) ? $page->getImages() : ($defaults?->getImages() ?? []),
        );
    }

    /** @return list<Meta> */
    private function openGraphTags(OpenGraph $og, ?string $title, ?string $description, UrlResolver $resolver): array
    {
        $tags = [];

        if ($title !== null) {
            $tags[] = $this->metaProperty('og:title', $title);
        }

        $tags[] = $this->metaProperty('og:type', $og->getType() ?? 'website');

        if ($description !== null) {
            $tags[] = $this->metaProperty('og:description', $description);
        }

        if ($og->getUrl() !== null) {
            $tags[] = $this->metaProperty('og:url', $resolver->resolve($og->getUrl()));
        }

        if ($og->getSiteName() !== null) {
            $tags[] = $this->metaProperty('og:site_name', $og->getSiteName());
        }

        if ($og->getLocale() !== null) {
            $tags[] = $this->metaProperty('og:locale', $og->getLocale());
        }

        foreach ($og->getImages() as $image) {
            $tags[] = $this->metaProperty('og:image', $resolver->resolve($image->getUrl()));

            if ($image->getWidth() !== null) {
                $tags[] = $this->metaProperty('og:image:width', (string) $image->getWidth());
            }

            if ($image->getHeight() !== null) {
                $tags[] = $this->metaProperty('og:image:height', (string) $image->getHeight());
            }

            if ($image->getAlt() !== null) {
                $tags[] = $this->metaProperty('og:image:alt', $image->getAlt());
            }

            if ($image->getType() !== null) {
                $tags[] = $this->metaProperty('og:image:type', $image->getType());
            }
        }

        return $tags;
    }

    /** @return list<Meta> */
    private function twitterTags(
        TwitterCard $twitter,
        ?OpenGraph $og,
        ?string $ogTitle,
        ?string $ogDescription,
        UrlResolver $resolver,
    ): array {
        $tags = [$this->metaName('twitter:card', $twitter->getCard() ?? 'summary_large_image')];

        if ($twitter->getSite() !== null) {
            $tags[] = $this->metaName('twitter:site', $twitter->getSite());
        }

        if ($twitter->getCreator() !== null) {
            $tags[] = $this->metaName('twitter:creator', $twitter->getCreator());
        }

        $title = $twitter->getTitle() ?? $ogTitle;

        if ($title !== null) {
            $tags[] = $this->metaName('twitter:title', $title);
        }

        $description = $twitter->getDescription() ?? $ogDescription;

        if ($description !== null) {
            $tags[] = $this->metaName('twitter:description', $description);
        }

        $images = $twitter->getImages();

        if ($images === [] && $og !== null) {
            $images = array_map(static fn(OgImage $image): string => $image->getUrl(), $og->getImages());
        }

        foreach ($images as $image) {
            $tags[] = $this->metaName('twitter:image', $resolver->resolve($image));
        }

        return $tags;
    }

    /** @return list<Meta> */
    private function verificationTags(Verification $verification): array
    {
        $tags = [];

        if ($verification->getGoogle() !== null) {
            $tags[] = $this->metaName('google-site-verification', $verification->getGoogle());
        }

        if ($verification->getYandex() !== null) {
            $tags[] = $this->metaName('yandex-verification', $verification->getYandex());
        }

        if ($verification->getBing() !== null) {
            $tags[] = $this->metaName('msvalidate.01', $verification->getBing());
        }

        foreach ($verification->getOther() as $name => $content) {
            $tags[] = $this->metaName($name, $content);
        }

        return $tags;
    }

    private function metaName(string $name, string $content): Meta
    {
        return Html::meta()->name($name)->content($content);
    }

    private function metaProperty(string $property, string $content): Meta
    {
        return Html::meta()->attribute('property', $property)->content($content);
    }
}
