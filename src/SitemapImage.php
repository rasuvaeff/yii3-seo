<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

/**
 * An `<image:image>` entry of a {@see SitemapUrl}, rendered as a single
 * `<image:loc>` element.
 *
 * The image sitemap extension also defines `caption`, `title`, `license` and
 * `geo_location`; Google stopped using them, so only the location is modeled.
 * The URL may be relative and is resolved against `metadataBase` at render time.
 *
 * @api
 */
final readonly class SitemapImage
{
    public function __construct(private string $loc)
    {
        if ($loc === '') {
            throw new InvalidArgumentException('Sitemap image URL must not be empty');
        }
    }

    public function getLoc(): string
    {
        return $this->loc;
    }
}
