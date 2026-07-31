<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

/**
 * A `robots.txt` document: an ordered list of {@see RobotsTxtGroup} blocks
 * followed by the site's `Sitemap` references.
 *
 * Sitemap URLs are crawler-facing and resolve against `metadataBase` exactly
 * like canonical URLs and sitemap entries, so a relative reference never leaks
 * the request host.
 *
 * The package never decides on its own whether a site may be indexed: there is
 * no environment sniffing anywhere. An application that must keep staging out
 * of the index passes `indexable: false` to the `rasuvaeff/yii3-seo` →
 * `robotsTxt` parameter, or builds {@see disallowAll()} itself.
 *
 * @api
 */
final readonly class RobotsTxt
{
    /** @var list<RobotsTxtGroup> */
    private array $groups;

    /** @var list<string> */
    private array $sitemaps;

    /**
     * @param array<array-key, RobotsTxtGroup> $groups
     * @param array<array-key, string> $sitemaps sitemap URLs, absolute or relative to `metadataBase`
     */
    public function __construct(
        array $groups = [],
        array $sitemaps = [],
        private ?string $metadataBase = null,
    ) {
        $this->groups = array_values($groups);
        $this->sitemaps = array_values($sitemaps);
    }

    /**
     * Every crawler may index everything.
     *
     * @param array<array-key, string> $sitemaps
     */
    public static function allowAll(array $sitemaps = [], ?string $metadataBase = null): self
    {
        return new self([RobotsTxtGroup::allowAll()], $sitemaps, $metadataBase);
    }

    /**
     * No crawler may index anything, and no sitemap is advertised. This is what
     * a non-production environment serves.
     */
    public static function disallowAll(): self
    {
        return new self([RobotsTxtGroup::disallowAll()]);
    }

    /** @return list<RobotsTxtGroup> */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /** @return list<string> */
    public function getSitemaps(): array
    {
        return $this->sitemaps;
    }

    /**
     * Renders the file. Groups keep their configured order and each emits
     * `User-agent`, then `Allow`, then `Disallow`, then `Crawl-delay`; the
     * `Sitemap` references form the last block.
     */
    public function toString(): string
    {
        $blocks = array_map($this->renderGroup(...), $this->groups);
        $sitemaps = $this->renderSitemaps();

        if ($sitemaps !== '') {
            $blocks[] = $sitemaps;
        }

        return $blocks === [] ? '' : implode("\n\n", $blocks) . "\n";
    }

    private function renderGroup(RobotsTxtGroup $group): string
    {
        $lines = [];

        foreach ($group->getUserAgents() as $userAgent) {
            $lines[] = "User-agent: {$userAgent}";
        }

        foreach ($group->getAllow() as $path) {
            $lines[] = "Allow: {$path}";
        }

        foreach ($group->getDisallow() as $path) {
            $lines[] = "Disallow: {$path}";
        }

        if ($group->getAllow() === [] && $group->getDisallow() === []) {
            $lines[] = 'Disallow:';
        }

        $crawlDelay = $group->getCrawlDelay();

        if ($crawlDelay !== null) {
            $lines[] = "Crawl-delay: {$crawlDelay}";
        }

        return implode("\n", $lines);
    }

    private function renderSitemaps(): string
    {
        $resolver = new UrlResolver($this->metadataBase);
        $lines = [];

        foreach ($this->sitemaps as $sitemap) {
            $lines[] = 'Sitemap: ' . $resolver->resolve($sitemap);
        }

        return implode("\n", $lines);
    }
}
