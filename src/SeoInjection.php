<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Link;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\Yii\View\Renderer\LayoutParametersInjectionInterface;
use Yiisoft\Yii\View\Renderer\LinkTagsInjectionInterface;
use Yiisoft\Yii\View\Renderer\MetaTagsInjectionInterface;

/**
 * Merges per-request {@see Metadata} with site-wide {@see MetadataDefaults} and
 * feeds the result to `WebViewRenderer` as meta and link tags.
 *
 * @api
 */
final class SeoInjection implements LayoutParametersInjectionInterface, MetaTagsInjectionInterface, LinkTagsInjectionInterface
{
    private ?Metadata $metadata = null;

    private ?string $requestPath = null;

    private ?ResolvedMetadata $resolvedMetadata = null;

    public function __construct(
        private readonly MetadataDefaults $defaults = new MetadataDefaults(),
        private readonly MetadataResolver $metadataResolver = new MetadataResolver(),
    ) {}

    public function setMetadata(Metadata $metadata): void
    {
        $this->metadata = $metadata;
        $this->resolvedMetadata = null;
    }

    /**
     * Records the current request path (with an optional query string) for the
     * opt-in {@see SelfCanonical} policy. {@see SelfCanonicalMiddleware} calls
     * this; the request authority is deliberately not recorded.
     */
    public function setRequestPath(?string $requestPath): void
    {
        $this->requestPath = $requestPath;
        $this->resolvedMetadata = null;
    }

    public function clear(): void
    {
        $this->metadata = null;
        $this->requestPath = null;
        $this->resolvedMetadata = null;
    }

    public function reset(): void
    {
        $this->clear();
    }

    /** @return array{seo: self} */
    #[\Override]
    public function getLayoutParameters(): array
    {
        return ['seo' => $this];
    }

    public function getTitle(): string
    {
        return $this->getResolvedMetadata()->getTitle();
    }

    public function getResolvedMetadata(): ResolvedMetadata
    {
        return $this->resolvedMetadata ??= $this->metadataResolver->resolve(
            metadata: $this->metadata,
            defaults: $this->defaults,
            requestPath: $this->requestPath,
        );
    }

    public function getJsonLdHtml(): string
    {
        $blocks = $this->getResolvedMetadata()->getJsonLd();

        return implode("\n", array_map(static fn(JsonLd $block): string => $block->toHtml(), $blocks));
    }

    /** @return list<Meta> */
    #[\Override]
    public function getMetaTags(): array
    {
        $tags = [];
        $metadata = $this->getResolvedMetadata();

        $description = $metadata->getDescription();

        if ($description !== null) {
            $tags[] = $this->metaName('description', $description);
        }

        $keywords = $metadata->getKeywords();

        if ($keywords !== []) {
            $tags[] = $this->metaName('keywords', implode(', ', $keywords));
        }

        foreach ($metadata->getAuthors() as $author) {
            $tags[] = $this->metaName('author', $author->getName());
        }

        $applicationName = $metadata->getApplicationName();

        if ($applicationName !== null) {
            $tags[] = $this->metaName('application-name', $applicationName);
        }

        $generator = $metadata->getGenerator();

        if ($generator !== null) {
            $tags[] = $this->metaName('generator', $generator);
        }

        $creator = $metadata->getCreator();

        if ($creator !== null) {
            $tags[] = $this->metaName('creator', $creator);
        }

        $publisher = $metadata->getPublisher();

        if ($publisher !== null) {
            $tags[] = $this->metaName('publisher', $publisher);
        }

        $themeColor = $metadata->getThemeColor();

        if ($themeColor !== null) {
            $tags[] = $this->metaName('theme-color', $themeColor);
        }

        $colorScheme = $metadata->getColorScheme();

        if ($colorScheme !== null) {
            $tags[] = $this->metaName('color-scheme', $colorScheme);
        }

        $robots = $metadata->getRobots();

        if ($robots instanceof \Rasuvaeff\Yii3Seo\Robots) {
            $tags[] = $this->metaName('robots', implode(', ', $robots->getDirectives()));

            $googleBot = $robots->getGoogleBotDirectives();

            if ($googleBot !== []) {
                $tags[] = $this->metaName('googlebot', implode(', ', $googleBot));
            }
        }

        $verification = $metadata->getVerification();

        if ($verification instanceof \Rasuvaeff\Yii3Seo\Verification) {
            $tags = [...$tags, ...$this->verificationTags($verification)];
        }

        foreach ($metadata->getOther() as $metaTag) {
            $tags[] = match ($metaTag->getAttributeType()) {
                'name' => $this->metaName($metaTag->getAttributeValue(), $metaTag->getContent()),
                'property' => $this->metaProperty($metaTag->getAttributeValue(), $metaTag->getContent()),
                'http-equiv' => Html::meta()->httpEquiv($metaTag->getAttributeValue())->content($metaTag->getContent()),
            };
        }

        $resolver = $this->urlResolver($metadata);
        $og = $metadata->getOpenGraph();

        if ($og instanceof \Rasuvaeff\Yii3Seo\OpenGraph) {
            $tags = [...$tags, ...$this->openGraphTags($og, $resolver)];
        }

        $twitter = $metadata->getTwitter();

        if ($twitter instanceof \Rasuvaeff\Yii3Seo\TwitterCard) {
            $tags = [...$tags, ...$this->twitterTags($twitter, $resolver)];
        }

        return $tags;
    }

    /** @return array<array-key, Link> */
    #[\Override]
    public function getLinkTags(): array
    {
        $tags = [];
        $metadata = $this->getResolvedMetadata();
        $resolver = $this->urlResolver($metadata);
        $alternates = $metadata->getAlternates();

        if ($alternates instanceof \Rasuvaeff\Yii3Seo\Alternates) {
            if ($alternates->getCanonical() !== null) {
                $tags['canonical'] = Html::link()->rel('canonical')->href($resolver->resolve($alternates->getCanonical()));
            }

            foreach ($alternates->getLanguages() as $locale => $url) {
                $tags[] = Html::link()->rel('alternate')->attribute('hreflang', $locale)->href($resolver->resolve($url));
            }
        }

        $icons = $metadata->getIcons();

        if ($icons instanceof \Rasuvaeff\Yii3Seo\Icons) {
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

        $manifest = $metadata->getManifest();

        if ($manifest !== null) {
            $tags['manifest'] = Html::link()->rel('manifest')->href($manifest);
        }

        foreach ($metadata->getAuthors() as $author) {
            if ($author->getUrl() !== null) {
                $tags[] = Html::link()->rel('author')->href($author->getUrl());
            }
        }

        return $tags;
    }

    private function urlResolver(ResolvedMetadata $metadata): UrlResolver
    {
        return new UrlResolver($metadata->getMetadataBase());
    }

    /** @return list<Meta> */
    private function openGraphTags(OpenGraph $og, UrlResolver $resolver): array
    {
        $tags = [];
        $title = $og->getTitle();
        $description = $og->getDescription();

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
        UrlResolver $resolver,
    ): array {
        $tags = [$this->metaName('twitter:card', $twitter->getCard() ?? 'summary_large_image')];

        if ($twitter->getSite() !== null) {
            $tags[] = $this->metaName('twitter:site', $twitter->getSite());
        }

        if ($twitter->getCreator() !== null) {
            $tags[] = $this->metaName('twitter:creator', $twitter->getCreator());
        }

        $title = $twitter->getTitle();

        if ($title !== null) {
            $tags[] = $this->metaName('twitter:title', $title);
        }

        $description = $twitter->getDescription();

        if ($description !== null) {
            $tags[] = $this->metaName('twitter:description', $description);
        }

        foreach ($twitter->getImages() as $image) {
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
