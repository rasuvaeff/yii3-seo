<?php

declare(strict_types=1);

/**
 * Crawlability: build an XML sitemap from an application-owned provider, stream
 * it, and export it to files with the protocol limits enforced.
 *
 * Run:
 *   docker run --rm -v "$PWD":/app -w /app composer:2 php examples/sitemap.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Rasuvaeff\Yii3Seo\ChangeFrequency;
use Rasuvaeff\Yii3Seo\Sitemap;
use Rasuvaeff\Yii3Seo\SitemapFileExporter;
use Rasuvaeff\Yii3Seo\SitemapImage;
use Rasuvaeff\Yii3Seo\SitemapLimits;
use Rasuvaeff\Yii3Seo\SitemapProviderInterface;
use Rasuvaeff\Yii3Seo\SitemapUrl;

$metadataBase = 'https://example.com';

/**
 * A real provider walks a database cursor. Yielding keeps memory bounded no
 * matter how many rows it produces.
 */
final class ProductSitemapProvider implements SitemapProviderInterface
{
    #[Override]
    public function getUrls(): iterable
    {
        yield new SitemapUrl(
            loc: '/',
            changeFrequency: ChangeFrequency::Daily,
            priority: 1.0,
            alternates: ['en' => '/', 'de' => '/de/', 'x-default' => '/'],
        );

        foreach ([1, 2, 3] as $id) {
            yield new SitemapUrl(
                loc: "/products/{$id}",
                lastModified: new DateTimeImmutable("2026-07-2{$id}T10:00:00+00:00"),
                changeFrequency: ChangeFrequency::Weekly,
                priority: 0.8,
                images: [new SitemapImage("/images/products/{$id}.jpg")],
            );
        }
    }
}

$provider = new ProductSitemapProvider();

echo "=== Streamed sitemap ===\n\n";

foreach ((new Sitemap($provider->getUrls(), $metadataBase))->toChunks() as $chunk) {
    echo $chunk;
}

echo "\n=== File export, split every two URLs ===\n\n";

$directory = sys_get_temp_dir() . '/yii3-seo-example-sitemap';

is_dir($directory) or mkdir($directory);

$exporter = new SitemapFileExporter(
    metadataBase: $metadataBase,
    limits: new SitemapLimits(maxUrls: 2),
);

$files = $exporter->export($provider->getUrls(), $directory, new DateTimeImmutable('2026-07-29T09:00:00+00:00'));

foreach ($files as $file) {
    echo basename($file), "\n";
}

echo "\n", (string) file_get_contents($directory . '/sitemap.xml'), "\n";

foreach ($files as $file) {
    unlink($file);
}

rmdir($directory);
