<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\ChangeFrequency;
use Rasuvaeff\Yii3Seo\SitemapImage;
use Rasuvaeff\Yii3Seo\SitemapIndexEntry;
use Rasuvaeff\Yii3Seo\SitemapUrl;
use Rasuvaeff\Yii3Seo\SitemapXmlWriter;
use Rasuvaeff\Yii3Seo\UrlResolver;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(SitemapXmlWriter::class)]
final class SitemapXmlWriterTest
{
    private SitemapXmlWriter $writer;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->writer = new SitemapXmlWriter(new UrlResolver('https://example.com'));
    }

    public function rendersAMinimalUrl(): void
    {
        Assert::same(
            $this->writer->renderUrl(new SitemapUrl('/a')),
            "<url><loc>https://example.com/a</loc></url>\n",
        );
    }

    public function rendersEveryUrlFieldInProtocolOrder(): void
    {
        $url = new SitemapUrl(
            loc: '/a',
            lastModified: new DateTimeImmutable('2026-07-29T10:00:00+00:00'),
            changeFrequency: ChangeFrequency::Daily,
            priority: 0.5,
            images: [new SitemapImage('/i.png')],
            alternates: ['de-DE' => '/de/a'],
        );

        Assert::same(
            $this->writer->renderUrl($url),
            '<url><loc>https://example.com/a</loc>'
            . '<lastmod>2026-07-29T10:00:00+00:00</lastmod>'
            . '<changefreq>daily</changefreq>'
            . '<priority>0.5</priority>'
            . '<xhtml:link rel="alternate" hreflang="de-DE" href="https://example.com/de/a"/>'
            . '<image:image><image:loc>https://example.com/i.png</image:loc></image:image>'
            . "</url>\n",
        );
    }

    public function escapesUrlsInsteadOfConcatenatingThem(): void
    {
        Assert::same(
            $this->writer->renderUrl(new SitemapUrl('/a?x=1&y=<2>')),
            "<url><loc>https://example.com/a?x=1&amp;y=&lt;2&gt;</loc></url>\n",
        );
    }

    #[DataProvider('priorityProvider')]
    public function rendersPriorityWithOneDecimal(float $priority, string $expected): void
    {
        Assert::same(
            $this->writer->renderUrl(new SitemapUrl('/a', priority: $priority)),
            "<url><loc>https://example.com/a</loc><priority>{$expected}</priority></url>\n",
        );
    }

    public function rendersEveryAlternateAndImage(): void
    {
        $url = new SitemapUrl(
            loc: '/a',
            images: [new SitemapImage('/1.png'), new SitemapImage('/2.png')],
            alternates: ['de' => '/de/a', 'x-default' => '/a'],
        );

        Assert::same(
            $this->writer->renderUrl($url),
            '<url><loc>https://example.com/a</loc>'
            . '<xhtml:link rel="alternate" hreflang="de" href="https://example.com/de/a"/>'
            . '<xhtml:link rel="alternate" hreflang="x-default" href="https://example.com/a"/>'
            . '<image:image><image:loc>https://example.com/1.png</image:loc></image:image>'
            . '<image:image><image:loc>https://example.com/2.png</image:loc></image:image>'
            . "</url>\n",
        );
    }

    public function rendersConsecutiveUrlsIndependently(): void
    {
        $this->writer->renderUrl(new SitemapUrl('/a'));

        Assert::same(
            $this->writer->renderUrl(new SitemapUrl('/b')),
            "<url><loc>https://example.com/b</loc></url>\n",
        );
    }

    public function rendersAnIndexEntry(): void
    {
        $entry = new SitemapIndexEntry('/sitemap-1.xml', new DateTimeImmutable('2026-07-29T10:00:00+00:00'));

        Assert::same(
            $this->writer->renderIndexEntry($entry),
            '<sitemap><loc>https://example.com/sitemap-1.xml</loc>'
            . "<lastmod>2026-07-29T10:00:00+00:00</lastmod></sitemap>\n",
        );
    }

    public function rendersAnIndexEntryWithoutLastModified(): void
    {
        Assert::same(
            $this->writer->renderIndexEntry(new SitemapIndexEntry('/sitemap-1.xml')),
            "<sitemap><loc>https://example.com/sitemap-1.xml</loc></sitemap>\n",
        );
    }

    public function throwsWhenARelativeUrlHasNoBase(): void
    {
        $writer = new SitemapXmlWriter(new UrlResolver(null));

        try {
            $writer->renderUrl(new SitemapUrl('/a'));
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('requires a metadataBase');
        }
    }

    public function documentEnvelopesAreValidXml(): void
    {
        Assert::same(
            SitemapXmlWriter::URLSET_HEADER,
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
            . ' xmlns:xhtml="http://www.w3.org/1999/xhtml"'
            . ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n",
        );
        Assert::same(SitemapXmlWriter::URLSET_FOOTER, "</urlset>\n");
        Assert::same(
            SitemapXmlWriter::SITEMAP_INDEX_HEADER,
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n",
        );
        Assert::same(SitemapXmlWriter::SITEMAP_INDEX_FOOTER, "</sitemapindex>\n");
    }

    /** @return iterable<string, array{float, string}> */
    public static function priorityProvider(): iterable
    {
        yield 'lowest' => [0.0, '0.0'];
        yield 'half' => [0.5, '0.5'];
        yield 'highest' => [1.0, '1.0'];
    }
}
