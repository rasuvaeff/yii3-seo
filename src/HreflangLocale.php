<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

/**
 * Shared `hreflang` locale validation for {@see Alternates} and
 * {@see SitemapUrl}, so head metadata and sitemap alternates accept exactly the
 * same set of locales.
 *
 * @internal
 */
final readonly class HreflangLocale
{
    private const string PATTERN = '/^(?:[a-z]{2}(?:-[A-Z]{2})?|x-default)\z/';

    public static function assert(string $locale): void
    {
        if (preg_match(self::PATTERN, $locale) !== 1) {
            throw new InvalidArgumentException("Invalid hreflang locale \"{$locale}\"");
        }
    }
}
