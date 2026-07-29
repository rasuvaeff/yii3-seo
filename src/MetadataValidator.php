<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

/**
 * Inspects {@see ResolvedMetadata} and reports SEO problems as typed issues.
 *
 * The validator never throws and never modifies metadata: it is a diagnostic
 * tool for development, CI checks and preview tooling. Suggested title and
 * description lengths are advisory warnings, not failures.
 *
 * Reported codes:
 * `title.missing`, `title.too_long`, `description.missing`,
 * `description.too_short`, `description.too_long`, `canonical.missing`,
 * `canonical.og_url_mismatch`, `url.unresolvable`, `image.missing`,
 * `image.alt_missing`, `image.dimensions_missing`, `robots.conflicting`,
 * `other.duplicate`.
 *
 * @api
 */
final readonly class MetadataValidator
{
    private const int TITLE_MAX_LENGTH = 60;

    private const int DESCRIPTION_MIN_LENGTH = 50;

    private const int DESCRIPTION_MAX_LENGTH = 160;

    /** @var list<array{string, string}> */
    private const array CONFLICTING_DIRECTIVES = [
        ['index', 'noindex'],
        ['follow', 'nofollow'],
        ['all', 'none'],
        ['all', 'noindex'],
        ['all', 'nofollow'],
        ['none', 'index'],
        ['none', 'follow'],
    ];

    public function validate(ResolvedMetadata $metadata): MetadataValidationResult
    {
        return new MetadataValidationResult([
            ...$this->validateTitle($metadata),
            ...$this->validateDescription($metadata),
            ...$this->validateUrls($metadata),
            ...$this->validateImages($metadata),
            ...$this->validateRobots($metadata),
            ...$this->validateCustomTags($metadata),
        ]);
    }

    /** @return list<MetadataIssue> */
    private function validateTitle(ResolvedMetadata $metadata): array
    {
        $title = $metadata->getTitle();

        if ($title === '') {
            return [MetadataIssue::error('title.missing', 'Page title is empty')];
        }

        $length = mb_strlen($title);
        $max = self::TITLE_MAX_LENGTH;

        if ($length > $max) {
            return [MetadataIssue::warning(
                'title.too_long',
                "Title is {$length} characters long; search results usually truncate after {$max}",
            )];
        }

        return [];
    }

    /** @return list<MetadataIssue> */
    private function validateDescription(ResolvedMetadata $metadata): array
    {
        $description = $metadata->getDescription();

        if ($description === null || $description === '') {
            return [MetadataIssue::warning('description.missing', 'Meta description is not set')];
        }

        $length = mb_strlen($description);
        $max = self::DESCRIPTION_MAX_LENGTH;
        $min = self::DESCRIPTION_MIN_LENGTH;

        if ($length > $max) {
            return [MetadataIssue::warning(
                'description.too_long',
                "Description is {$length} characters long; search results usually truncate after {$max}",
            )];
        }

        if ($length < $min) {
            return [MetadataIssue::warning(
                'description.too_short',
                "Description is {$length} characters long; at least {$min} characters is recommended",
            )];
        }

        return [];
    }

    /** @return list<MetadataIssue> */
    private function validateUrls(ResolvedMetadata $metadata): array
    {
        $resolver = new UrlResolver($metadata->getMetadataBase());
        $issues = [];

        foreach ($this->crawlerUrls($metadata) as [$label, $url]) {
            try {
                $resolver->resolve($url);
            } catch (InvalidArgumentException $e) {
                $issues[] = MetadataIssue::error('url.unresolvable', "{$label}: {$e->getMessage()}");
            }
        }

        $canonical = $metadata->getAlternates()?->getCanonical();

        if ($canonical === null) {
            $issues[] = MetadataIssue::warning('canonical.missing', 'Canonical URL is not set');

            return $issues;
        }

        $ogUrl = $metadata->getOpenGraph()?->getUrl();

        if ($ogUrl === null) {
            return $issues;
        }

        $resolvedCanonical = $this->tryResolve($resolver, $canonical);
        $resolvedOgUrl = $this->tryResolve($resolver, $ogUrl);

        if ($resolvedCanonical !== null && $resolvedOgUrl !== null && $resolvedCanonical !== $resolvedOgUrl) {
            $issues[] = MetadataIssue::error(
                'canonical.og_url_mismatch',
                "Canonical URL \"{$resolvedCanonical}\" does not match og:url \"{$resolvedOgUrl}\"",
            );
        }

        return $issues;
    }

    /** @return list<array{string, string}> */
    private function crawlerUrls(ResolvedMetadata $metadata): array
    {
        $urls = [];
        $alternates = $metadata->getAlternates();
        $canonical = $alternates?->getCanonical();

        if ($canonical !== null) {
            $urls[] = ['canonical', $canonical];
        }

        foreach ($alternates?->getLanguages() ?? [] as $locale => $url) {
            $urls[] = ["hreflang \"{$locale}\"", $url];
        }

        $openGraph = $metadata->getOpenGraph();
        $ogUrl = $openGraph?->getUrl();

        if ($ogUrl !== null) {
            $urls[] = ['og:url', $ogUrl];
        }

        foreach ($openGraph?->getImages() ?? [] as $image) {
            $urls[] = ['og:image', $image->getUrl()];
        }

        foreach ($metadata->getTwitter()?->getImages() ?? [] as $image) {
            $urls[] = ['twitter:image', $image];
        }

        return $urls;
    }

    private function tryResolve(UrlResolver $resolver, string $url): ?string
    {
        try {
            return $resolver->resolve($url);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /** @return list<MetadataIssue> */
    private function validateImages(ResolvedMetadata $metadata): array
    {
        $images = $metadata->getOpenGraph()?->getImages() ?? [];

        if ($images === [] && ($metadata->getTwitter()?->getImages() ?? []) === []) {
            return [MetadataIssue::warning('image.missing', 'No Open Graph or Twitter image is set')];
        }

        $issues = [];

        foreach ($images as $image) {
            $url = $image->getUrl();

            if ($image->getAlt() === null) {
                $issues[] = MetadataIssue::warning(
                    'image.alt_missing',
                    "Open Graph image \"{$url}\" has no alt text",
                );
            }

            if ($image->getWidth() === null || $image->getHeight() === null) {
                $issues[] = MetadataIssue::warning(
                    'image.dimensions_missing',
                    "Open Graph image \"{$url}\" has no width and height",
                );
            }
        }

        return $issues;
    }

    /** @return list<MetadataIssue> */
    private function validateRobots(ResolvedMetadata $metadata): array
    {
        $robots = $metadata->getRobots();

        if (!$robots instanceof \Rasuvaeff\Yii3Seo\Robots) {
            return [];
        }

        return [
            ...$this->conflictingDirectives('robots', $robots->getDirectives()),
            ...$this->conflictingDirectives('googlebot', $robots->getGoogleBotDirectives()),
        ];
    }

    /**
     * @param list<string> $directives
     *
     * @return list<MetadataIssue>
     */
    private function conflictingDirectives(string $tag, array $directives): array
    {
        $issues = [];
        $present = array_flip($directives);

        foreach (self::CONFLICTING_DIRECTIVES as [$first, $second]) {
            if (isset($present[$first], $present[$second])) {
                $issues[] = MetadataIssue::error(
                    'robots.conflicting',
                    "Conflicting {$tag} directives \"{$first}\" and \"{$second}\"",
                );
            }
        }

        return $issues;
    }

    /** @return list<MetadataIssue> */
    private function validateCustomTags(ResolvedMetadata $metadata): array
    {
        $issues = [];
        $seen = [];
        $reported = [];

        foreach ($metadata->getOther() as $metaTag) {
            $type = $metaTag->getAttributeType();
            $value = $metaTag->getAttributeValue();
            $key = "{$type}|{$value}";

            if (!isset($seen[$key])) {
                $seen[$key] = $metaTag;

                continue;
            }

            if (isset($reported[$key])) {
                continue;
            }

            $reported[$key] = $metaTag;
            $issues[] = MetadataIssue::warning(
                'other.duplicate',
                "Duplicate custom meta tag {$type}=\"{$value}\"",
            );
        }

        return $issues;
    }
}
