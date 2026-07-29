<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

/**
 * An application-owned source of sitemap URLs.
 *
 * Implementations should yield rather than build an array, so a provider backed
 * by a database cursor keeps memory bounded no matter how many rows it walks.
 * The package never crawls a site to discover URLs; the application always says
 * what belongs in the sitemap.
 *
 * @api
 */
interface SitemapProviderInterface
{
    /** @return iterable<SitemapUrl> */
    public function getUrls(): iterable;
}
