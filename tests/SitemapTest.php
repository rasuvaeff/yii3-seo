<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use Generator;
use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\Sitemap;
use Rasuvaeff\Yii3Seo\SitemapUrl;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Sitemap::class)]
final class SitemapTest
{
    public function wrapsUrlsIntoAUrlsetDocument(): void
    {
        $sitemap = new Sitemap([new SitemapUrl('/a'), new SitemapUrl('/b')], 'https://example.com');

        Assert::same(
            $this->render($sitemap),
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
            . ' xmlns:xhtml="http://www.w3.org/1999/xhtml"'
            . ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n"
            . "<url><loc>https://example.com/a</loc></url>\n"
            . "<url><loc>https://example.com/b</loc></url>\n"
            . "</urlset>\n",
        );
    }

    public function anEmptySitemapIsStillAValidDocument(): void
    {
        $chunks = iterator_to_array((new Sitemap([]))->toChunks());

        Assert::same(count($chunks), 2);
        Assert::string($chunks[0])->contains('<urlset');
        Assert::same($chunks[1], "</urlset>\n");
    }

    public function emitsOneChunkPerUrlPlusTheEnvelope(): void
    {
        $chunks = iterator_to_array(
            (new Sitemap([new SitemapUrl('/a'), new SitemapUrl('/b')], 'https://example.com'))->toChunks(),
        );

        Assert::same(count($chunks), 4);
    }

    public function pullsUrlsLazilyFromAGenerator(): void
    {
        $pulled = 0;
        $urls = (static function () use (&$pulled): Generator {
            foreach (['/a', '/b'] as $path) {
                ++$pulled;

                yield new SitemapUrl($path);
            }
        })();

        $chunks = (new Sitemap($urls, 'https://example.com'))->toChunks();
        $chunks->current();

        Assert::same($pulled, 0);

        $chunks->next();

        Assert::same($pulled, 1);
    }

    public function absoluteUrlsDoNotNeedABase(): void
    {
        $sitemap = new Sitemap([new SitemapUrl('https://example.com/a')]);

        Assert::string($this->render($sitemap))->contains('<loc>https://example.com/a</loc>');
    }

    public function throwsWhenARelativeUrlHasNoBase(): void
    {
        try {
            $this->render(new Sitemap([new SitemapUrl('/a')]));
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('requires a metadataBase');
        }
    }

    private function render(Sitemap $sitemap): string
    {
        return implode('', iterator_to_array($sitemap->toChunks()));
    }
}
