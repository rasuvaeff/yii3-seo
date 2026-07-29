<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

/**
 * Protocol limits a single sitemap file must stay within: at most 50 000 URLs
 * and 50 MiB uncompressed, per sitemaps.org.
 *
 * Lower values are accepted (some crawlers and CDNs prefer smaller files);
 * higher values are not, because a file above either limit is rejected outright.
 *
 * @api
 */
final readonly class SitemapLimits
{
    public const int MAX_URLS = 50_000;

    public const int MAX_BYTES = 52_428_800;

    public function __construct(
        private int $maxUrls = self::MAX_URLS,
        private int $maxBytes = self::MAX_BYTES,
    ) {
        if ($maxUrls < 1 || $maxUrls > self::MAX_URLS) {
            throw new InvalidArgumentException("Invalid sitemap URL limit \"{$maxUrls}\"");
        }

        if ($maxBytes < 1 || $maxBytes > self::MAX_BYTES) {
            throw new InvalidArgumentException("Invalid sitemap byte limit \"{$maxBytes}\"");
        }
    }

    public function getMaxUrls(): int
    {
        return $this->maxUrls;
    }

    public function getMaxBytes(): int
    {
        return $this->maxBytes;
    }
}
