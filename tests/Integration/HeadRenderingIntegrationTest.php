<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests\Integration;

use Rasuvaeff\Yii3Seo\Alternates;
use Rasuvaeff\Yii3Seo\JsonLd;
use Rasuvaeff\Yii3Seo\Metadata;
use Rasuvaeff\Yii3Seo\MetadataDefaults;
use Rasuvaeff\Yii3Seo\OgImage;
use Rasuvaeff\Yii3Seo\OpenGraph;
use Rasuvaeff\Yii3Seo\SeoInjection;
use Rasuvaeff\Yii3Seo\SeoMetadataEvent;
use Rasuvaeff\Yii3Seo\SetSeoMetadataEventHandler;
use Rasuvaeff\Yii3Seo\Title;
use Rasuvaeff\Yii3Seo\TwitterCard;
use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\Yii\View\Renderer\LinkTagsInjectionInterface;
use Yiisoft\Yii\View\Renderer\MetaTagsInjectionInterface;

/**
 * End-to-end: site-wide defaults + the event flow (SeoMetadataEvent ->
 * SetSeoMetadataEventHandler -> SeoInjection) feeding a full `<head>` exactly as
 * WebViewRenderer + a layout would assemble it.
 */
#[Test]
#[CoversNothing]
final class HeadRenderingIntegrationTest
{
    private SeoInjection $injection;

    #[BeforeTest]
    public function setUp(): void
    {
        $defaults = new MetadataDefaults(
            metadataBase: 'https://example.com',
            title: Title::template('%s | My Store', default: 'My Store'),
            openGraph: new OpenGraph(siteName: 'My Store', locale: 'en_US'),
            twitter: new TwitterCard(card: 'summary_large_image', site: '@mystore'),
        );

        $this->injection = new SeoInjection($defaults);

        $handler = new SetSeoMetadataEventHandler(seoInjection: $this->injection);
        $handler(new SeoMetadataEvent(metadata: new Metadata(
            title: 'Awesome Product',
            description: 'Buy the awesome product.',
            alternates: new Alternates(
                canonical: '/products/awesome',
                languages: ['en' => '/en/products/awesome', 'x-default' => '/products/awesome'],
            ),
            openGraph: new OpenGraph(
                type: 'product',
                images: [new OgImage(url: '/og/awesome.jpg', width: 1200, height: 630, alt: 'Awesome')],
            ),
            twitter: new TwitterCard(creator: '@author'),
            jsonLd: [JsonLd::fromArray(['@context' => 'https://schema.org', '@type' => 'Product', 'name' => 'Awesome Product'])],
        )));
    }

    public function seoInjectionFulfillsTheWebViewRendererContract(): void
    {
        Assert::instanceOf($this->injection, MetaTagsInjectionInterface::class);
        Assert::instanceOf($this->injection, LinkTagsInjectionInterface::class);
    }

    public function fullHeadIsAssembledFromDefaultsAndPageMetadata(): void
    {
        $head = $this->renderHead();

        Assert::string($head)->contains('<title>Awesome Product | My Store</title>');
        Assert::string($head)->contains('<meta name="description" content="Buy the awesome product.">');
        Assert::string($head)->contains('<meta property="og:title" content="Awesome Product | My Store">');
        Assert::string($head)->contains('<meta property="og:type" content="product">');
        Assert::string($head)->contains('<meta property="og:site_name" content="My Store">');
        Assert::string($head)->contains('<meta property="og:locale" content="en_US">');
        Assert::string($head)->contains('<meta property="og:image" content="https://example.com/og/awesome.jpg">');
        Assert::string($head)->contains('<meta property="og:image:width" content="1200">');
        Assert::string($head)->contains('<meta name="twitter:card" content="summary_large_image">');
        Assert::string($head)->contains('<meta name="twitter:site" content="@mystore">');
        Assert::string($head)->contains('<meta name="twitter:creator" content="@author">');
        Assert::string($head)->contains('<meta name="twitter:image" content="https://example.com/og/awesome.jpg">');
        Assert::string($head)->contains('rel="canonical"');
        Assert::string($head)->contains('href="https://example.com/products/awesome"');
        Assert::string($head)->contains('hreflang="en"');
        Assert::string($head)->contains('href="https://example.com/en/products/awesome"');
        Assert::string($head)->contains('<script type="application/ld+json">');
        Assert::string($head)->contains('"@type": "Product"');
    }

    private function renderHead(): string
    {
        $parts = ['<title>' . htmlspecialchars($this->injection->getTitle(), ENT_QUOTES) . '</title>'];

        foreach ($this->injection->getMetaTags() as $tag) {
            $parts[] = $tag->render();
        }

        foreach ($this->injection->getLinkTags() as $tag) {
            $parts[] = $tag->render();
        }

        $parts[] = $this->injection->getJsonLdHtml();

        return implode("\n", $parts);
    }
}
