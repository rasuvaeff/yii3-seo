<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests\Integration;

use DateTimeImmutable;
use Rasuvaeff\Yii3Seo\ChangeFrequency;
use Rasuvaeff\Yii3Seo\Sitemap;
use Rasuvaeff\Yii3Seo\SitemapFileExporter;
use Rasuvaeff\Yii3Seo\SitemapImage;
use Rasuvaeff\Yii3Seo\SitemapLimits;
use Rasuvaeff\Yii3Seo\SitemapProviderInterface;
use Rasuvaeff\Yii3Seo\SitemapUrl;
use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Lifecycle\AfterTest;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

/**
 * Byte-exact sitemap snapshots. They freeze element order, URL resolution and
 * the file layout an export produces: any change to what a crawler downloads has
 * to be an explicit edit here.
 */
#[Test]
#[CoversNothing]
final class SitemapSnapshotIntegrationTest
{
    private const string METADATA_BASE = 'https://example.com';

    private string $directory;

    #[BeforeTest]
    public function createDirectory(): void
    {
        $this->directory = sys_get_temp_dir() . '/yii3-seo-sitemap-snapshot-' . uniqid();

        mkdir($this->directory);
    }

    #[AfterTest]
    public function removeDirectory(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->directory);
    }

    public function rendersTheExpectedSitemapForAProviderBackedSite(): void
    {
        $sitemap = new Sitemap($this->provider()->getUrls(), self::METADATA_BASE);

        Assert::same(implode('', iterator_to_array($sitemap->toChunks())), $this->expectedSitemap());
    }

    public function exportsASingleFileWhenEverythingFits(): void
    {
        $exporter = new SitemapFileExporter(self::METADATA_BASE);

        $files = $exporter->export($this->provider()->getUrls(), $this->directory);

        Assert::same($files, [$this->directory . '/sitemap.xml']);
        Assert::same((string) file_get_contents($this->directory . '/sitemap.xml'), $this->expectedSitemap());
    }

    public function exportsChunksAndAnIndexWhenTheStreamDoesNotFit(): void
    {
        $exporter = new SitemapFileExporter(
            metadataBase: self::METADATA_BASE,
            limits: new SitemapLimits(maxUrls: 2),
        );

        $files = $exporter->export(
            $this->provider()->getUrls(),
            $this->directory,
            new DateTimeImmutable('2026-07-29T09:00:00+00:00'),
        );

        Assert::same($files, [
            $this->directory . '/sitemap-1.xml',
            $this->directory . '/sitemap-2.xml',
            $this->directory . '/sitemap.xml',
        ]);
        Assert::same(
            (string) file_get_contents($this->directory . '/sitemap.xml'),
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
            . '<sitemap><loc>https://example.com/sitemap-1.xml</loc>'
            . "<lastmod>2026-07-29T09:00:00+00:00</lastmod></sitemap>\n"
            . '<sitemap><loc>https://example.com/sitemap-2.xml</loc>'
            . "<lastmod>2026-07-29T09:00:00+00:00</lastmod></sitemap>\n"
            . "</sitemapindex>\n",
        );
        Assert::same(
            (string) file_get_contents($this->directory . '/sitemap-2.xml'),
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
            . ' xmlns:xhtml="http://www.w3.org/1999/xhtml"'
            . ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n"
            . '<url><loc>https://example.com/products/1</loc>'
            . '<lastmod>2026-07-28T18:30:00+00:00</lastmod>'
            . '<changefreq>weekly</changefreq>'
            . '<priority>0.8</priority>'
            . '<image:image><image:loc>https://example.com/images/products/1.jpg</image:loc></image:image>'
            . "</url>\n"
            . "</urlset>\n",
        );
    }

    private function expectedSitemap(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
            . ' xmlns:xhtml="http://www.w3.org/1999/xhtml"'
            . ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n"
            . '<url><loc>https://example.com/</loc>'
            . '<changefreq>daily</changefreq>'
            . '<priority>1.0</priority>'
            . '<xhtml:link rel="alternate" hreflang="en" href="https://example.com/"/>'
            . '<xhtml:link rel="alternate" hreflang="de" href="https://example.com/de/"/>'
            . '<xhtml:link rel="alternate" hreflang="x-default" href="https://example.com/"/>'
            . "</url>\n"
            . '<url><loc>https://example.com/blog/hello-world</loc>'
            . '<lastmod>2026-07-29T08:15:00+00:00</lastmod>'
            . "</url>\n"
            . '<url><loc>https://example.com/products/1</loc>'
            . '<lastmod>2026-07-28T18:30:00+00:00</lastmod>'
            . '<changefreq>weekly</changefreq>'
            . '<priority>0.8</priority>'
            . '<image:image><image:loc>https://example.com/images/products/1.jpg</image:loc></image:image>'
            . "</url>\n"
            . "</urlset>\n";
    }

    private function provider(): SitemapProviderInterface
    {
        return new class implements SitemapProviderInterface {
            #[\Override]
            public function getUrls(): iterable
            {
                yield new SitemapUrl(
                    loc: '/',
                    changeFrequency: ChangeFrequency::Daily,
                    priority: 1.0,
                    alternates: ['en' => '/', 'de' => '/de/', 'x-default' => '/'],
                );

                yield new SitemapUrl(
                    loc: '/blog/hello-world',
                    lastModified: new DateTimeImmutable('2026-07-29T08:15:00+00:00'),
                );

                yield new SitemapUrl(
                    loc: '/products/1',
                    lastModified: new DateTimeImmutable('2026-07-28T18:30:00+00:00'),
                    changeFrequency: ChangeFrequency::Weekly,
                    priority: 0.8,
                    images: [new SitemapImage('/images/products/1.jpg')],
                );
            }
        };
    }
}
