<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\SitemapFileExporter;
use Rasuvaeff\Yii3Seo\SitemapLimits;
use Rasuvaeff\Yii3Seo\SitemapUrl;
use Rasuvaeff\Yii3Seo\SitemapXmlWriter;
use Rasuvaeff\Yii3Seo\UrlResolver;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\AfterTest;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(SitemapFileExporter::class)]
final class SitemapFileExporterTest
{
    private string $directory;

    #[BeforeTest]
    public function createDirectory(): void
    {
        $this->directory = sys_get_temp_dir() . '/yii3-seo-sitemap-' . uniqid();

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

    public function writesASingleFileNamedAfterTheIndex(): void
    {
        $files = $this->exporter()->export([new SitemapUrl('/a'), new SitemapUrl('/b')], $this->directory);

        Assert::same($files, [$this->path('sitemap.xml')]);
        Assert::same(
            $this->read('sitemap.xml'),
            SitemapXmlWriter::URLSET_HEADER
            . "<url><loc>https://example.com/a</loc></url>\n"
            . "<url><loc>https://example.com/b</loc></url>\n"
            . SitemapXmlWriter::URLSET_FOOTER,
        );
    }

    public function writesAValidEmptySitemapForAnEmptyStream(): void
    {
        $files = $this->exporter()->export([], $this->directory);

        Assert::same($files, [$this->path('sitemap.xml')]);
        Assert::same(
            $this->read('sitemap.xml'),
            SitemapXmlWriter::URLSET_HEADER . SitemapXmlWriter::URLSET_FOOTER,
        );
    }

    public function normalisesATrailingSlashInTheDirectory(): void
    {
        $files = $this->exporter()->export([new SitemapUrl('/a')], $this->directory . '/');

        Assert::same($files, [$this->path('sitemap.xml')]);
    }

    public function splitsAtTheUrlLimitAndWritesAnIndex(): void
    {
        $exporter = $this->exporter(new SitemapLimits(maxUrls: 2));
        $urls = [new SitemapUrl('/a'), new SitemapUrl('/b'), new SitemapUrl('/c')];

        $files = $exporter->export($urls, $this->directory);

        Assert::same($files, [
            $this->path('sitemap-1.xml'),
            $this->path('sitemap-2.xml'),
            $this->path('sitemap.xml'),
        ]);
        Assert::same(
            $this->read('sitemap-1.xml'),
            SitemapXmlWriter::URLSET_HEADER
            . "<url><loc>https://example.com/a</loc></url>\n"
            . "<url><loc>https://example.com/b</loc></url>\n"
            . SitemapXmlWriter::URLSET_FOOTER,
        );
        Assert::same(
            $this->read('sitemap-2.xml'),
            SitemapXmlWriter::URLSET_HEADER
            . "<url><loc>https://example.com/c</loc></url>\n"
            . SitemapXmlWriter::URLSET_FOOTER,
        );
        Assert::same(
            $this->read('sitemap.xml'),
            SitemapXmlWriter::SITEMAP_INDEX_HEADER
            . "<sitemap><loc>https://example.com/sitemap-1.xml</loc></sitemap>\n"
            . "<sitemap><loc>https://example.com/sitemap-2.xml</loc></sitemap>\n"
            . SitemapXmlWriter::SITEMAP_INDEX_FOOTER,
        );
    }

    public function splitsAtTheByteLimit(): void
    {
        $exporter = $this->exporter(new SitemapLimits(maxBytes: $this->bytesFor(1)));

        $files = $exporter->export([new SitemapUrl('/a'), new SitemapUrl('/b')], $this->directory);

        Assert::same(count($files), 3);
    }

    public function fillsAFileUpToTheByteLimitBeforeSplitting(): void
    {
        $exporter = $this->exporter(new SitemapLimits(maxBytes: $this->bytesFor(2)));

        $files = $exporter->export([new SitemapUrl('/a'), new SitemapUrl('/b')], $this->directory);

        Assert::same($files, [$this->path('sitemap.xml')]);
    }

    public function splitsWhenTheClosingTagNoLongerFits(): void
    {
        $exporter = $this->exporter(new SitemapLimits(maxBytes: $this->bytesFor(2) - 5));

        $files = $exporter->export([new SitemapUrl('/a'), new SitemapUrl('/b')], $this->directory);

        Assert::same(count($files), 3);
    }

    public function indexEntriesUseTheConfiguredPublicPath(): void
    {
        $exporter = new SitemapFileExporter(
            metadataBase: 'https://example.com',
            limits: new SitemapLimits(maxUrls: 1),
            publicPath: '/seo/',
        );

        $exporter->export([new SitemapUrl('/a'), new SitemapUrl('/b')], $this->directory);

        Assert::same(
            $this->read('sitemap.xml'),
            SitemapXmlWriter::SITEMAP_INDEX_HEADER
            . "<sitemap><loc>https://example.com/seo/sitemap-1.xml</loc></sitemap>\n"
            . "<sitemap><loc>https://example.com/seo/sitemap-2.xml</loc></sitemap>\n"
            . SitemapXmlWriter::SITEMAP_INDEX_FOOTER,
        );
    }

    public function indexEntriesCarryTheGivenLastModified(): void
    {
        $exporter = $this->exporter(new SitemapLimits(maxUrls: 1));

        $exporter->export(
            [new SitemapUrl('/a'), new SitemapUrl('/b')],
            $this->directory,
            new DateTimeImmutable('2026-07-29T10:00:00+00:00'),
        );

        Assert::string($this->read('sitemap.xml'))->contains('<lastmod>2026-07-29T10:00:00+00:00</lastmod>');
    }

    public function throwsOnAMissingDirectory(): void
    {
        try {
            $this->exporter()->export([new SitemapUrl('/a')], $this->directory . '/missing');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('does not exist');
        }
    }

    public function throwsWhenASingleUrlExceedsTheByteLimit(): void
    {
        $exporter = $this->exporter(new SitemapLimits(maxBytes: $this->bytesFor(1) - 1));

        try {
            $exporter->export([new SitemapUrl('/a')], $this->directory);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('does not fit into the configured byte limit');
        }
    }

    public function throwsOnAPublicPathThatIsNotAPath(): void
    {
        try {
            new SitemapFileExporter(publicPath: 'seo');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('must start with "/"');
        }
    }

    private function exporter(?SitemapLimits $limits = null): SitemapFileExporter
    {
        return new SitemapFileExporter('https://example.com', $limits ?? new SitemapLimits());
    }

    private function bytesFor(int $urls): int
    {
        $writer = new SitemapXmlWriter(new UrlResolver('https://example.com'));
        $entry = $writer->renderUrl(new SitemapUrl('/a'));

        return strlen(SitemapXmlWriter::URLSET_HEADER)
            + strlen($entry) * $urls
            + strlen(SitemapXmlWriter::URLSET_FOOTER);
    }

    private function path(string $name): string
    {
        return $this->directory . '/' . $name;
    }

    private function read(string $name): string
    {
        return (string) file_get_contents($this->path($name));
    }
}
