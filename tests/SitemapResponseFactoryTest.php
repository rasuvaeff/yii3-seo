<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\StreamFactory;
use Rasuvaeff\Yii3Seo\Sitemap;
use Rasuvaeff\Yii3Seo\SitemapIndex;
use Rasuvaeff\Yii3Seo\SitemapIndexEntry;
use Rasuvaeff\Yii3Seo\SitemapResponseFactory;
use Rasuvaeff\Yii3Seo\SitemapUrl;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(SitemapResponseFactory::class)]
final class SitemapResponseFactoryTest
{
    private SitemapResponseFactory $factory;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->factory = new SitemapResponseFactory(new ResponseFactory(), new StreamFactory());
    }

    public function servesTheDocumentAsXml(): void
    {
        $response = $this->factory->create(new Sitemap([new SitemapUrl('/a')], 'https://example.com'));

        Assert::same($response->getStatusCode(), 200);
        Assert::same($response->getHeaderLine('Content-Type'), 'application/xml; charset=UTF-8');
        Assert::string((string) $response->getBody())->contains('<loc>https://example.com/a</loc>');
    }

    public function theBodyIsRewoundAndComplete(): void
    {
        $response = $this->factory->create(new Sitemap([new SitemapUrl('/a')], 'https://example.com'));
        $body = $response->getBody();

        Assert::same($body->tell(), 0);
        Assert::same(
            $body->getContents(),
            implode('', iterator_to_array((new Sitemap([new SitemapUrl('/a')], 'https://example.com'))->toChunks())),
        );
    }

    public function servesASitemapIndexTheSameWay(): void
    {
        $index = new SitemapIndex([new SitemapIndexEntry('/sitemap-1.xml')], 'https://example.com');

        $response = $this->factory->create($index);

        Assert::string((string) $response->getBody())->contains('<sitemapindex');
    }
}
