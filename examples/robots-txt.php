<?php

declare(strict_types=1);

/**
 * Crawl policy: a typed `robots.txt`, the forced non-production disallow, and
 * the same policy serialized as an `X-Robots-Tag` header for responses that
 * have no `<head>`.
 *
 * Run:
 *   docker run --rm -v "$PWD":/app -w /app composer:2 php examples/robots-txt.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Rasuvaeff\Yii3Seo\Robots;
use Rasuvaeff\Yii3Seo\RobotsTxt;
use Rasuvaeff\Yii3Seo\RobotsTxtGroup;

$metadataBase = 'https://example.com';

$robotsTxt = new RobotsTxt(
    groups: [
        new RobotsTxtGroup(
            userAgents: ['*'],
            allow: ['/admin/public/'],
            disallow: ['/admin/', '/cart/', '*.json'],
        ),
        new RobotsTxtGroup(userAgents: ['AhrefsBot', 'SemrushBot'], disallow: ['/'], crawlDelay: 10),
    ],
    sitemaps: ['/sitemap.xml'],
    metadataBase: $metadataBase,
);

echo "=== Production robots.txt ===\n\n", $robotsTxt->toString();

/**
 * Staging must never reach the index. The package never inspects the
 * environment itself — the application decides and says so explicitly, either
 * here or through the `rasuvaeff/yii3-seo` → `robotsTxt` → `indexable`
 * parameter.
 */
$indexable = false;

echo "\n=== Non-production robots.txt ===\n\n";
echo ($indexable ? $robotsTxt : RobotsTxt::disallowAll())->toString();

echo "\n=== X-Robots-Tag for a generated PDF ===\n\n";

foreach (Robots::noindex()->withGoogleBot('noindex', 'noimageindex')->toHeaderValues() as $value) {
    echo 'X-Robots-Tag: ', $value, "\n";
}
