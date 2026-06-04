<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
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
use Yiisoft\Yii\View\Renderer\LinkTagsInjectionInterface;
use Yiisoft\Yii\View\Renderer\MetaTagsInjectionInterface;

/**
 * End-to-end: site-wide defaults + the event flow (SeoMetadataEvent ->
 * SetSeoMetadataEventHandler -> SeoInjection) feeding a full `<head>` exactly as
 * WebViewRenderer + a layout would assemble it.
 */
#[CoversNothing]
final class HeadRenderingIntegrationTest extends TestCase
{
    private SeoInjection $injection;

    #[\Override]
    protected function setUp(): void
    {
        $defaults = new MetadataDefaults(
            metadataBase: 'https://example.com',
            title: Title::template('%s | My Store', default: 'My Store'),
            openGraph: new OpenGraph(siteName: 'My Store', locale: 'en_US'),
            twitter: new TwitterCard(card: 'summary_large_image', site: '@mystore'),
        );

        $this->injection = new SeoInjection($defaults);

        // The framework dispatches SeoMetadataEvent; the registered handler runs this.
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

    #[Test]
    public function seoInjectionFulfillsTheWebViewRendererContract(): void
    {
        $this->assertInstanceOf(MetaTagsInjectionInterface::class, $this->injection);
        $this->assertInstanceOf(LinkTagsInjectionInterface::class, $this->injection);
    }

    #[Test]
    public function fullHeadIsAssembledFromDefaultsAndPageMetadata(): void
    {
        $head = $this->renderHead();

        // Title template from defaults applied to the page title.
        $this->assertStringContainsString('<title>Awesome Product | My Store</title>', $head);

        // Per-page meta.
        $this->assertStringContainsString('<meta name="description" content="Buy the awesome product.">', $head);

        // OpenGraph: page type + defaults siteName/locale + title cascade + resolved image URL.
        $this->assertStringContainsString('<meta property="og:title" content="Awesome Product | My Store">', $head);
        $this->assertStringContainsString('<meta property="og:type" content="product">', $head);
        $this->assertStringContainsString('<meta property="og:site_name" content="My Store">', $head);
        $this->assertStringContainsString('<meta property="og:locale" content="en_US">', $head);
        $this->assertStringContainsString('<meta property="og:image" content="https://example.com/og/awesome.jpg">', $head);
        $this->assertStringContainsString('<meta property="og:image:width" content="1200">', $head);

        // Twitter: card/site inherited from defaults, creator from page, image cascaded from OG.
        $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $head);
        $this->assertStringContainsString('<meta name="twitter:site" content="@mystore">', $head);
        $this->assertStringContainsString('<meta name="twitter:creator" content="@author">', $head);
        $this->assertStringContainsString('<meta name="twitter:image" content="https://example.com/og/awesome.jpg">', $head);

        // Link tags: canonical + hreflang resolved against metadataBase.
        $this->assertStringContainsString('rel="canonical"', $head);
        $this->assertStringContainsString('href="https://example.com/products/awesome"', $head);
        $this->assertStringContainsString('hreflang="en"', $head);
        $this->assertStringContainsString('href="https://example.com/en/products/awesome"', $head);

        // JSON-LD.
        $this->assertStringContainsString('<script type="application/ld+json">', $head);
        $this->assertStringContainsString('"@type": "Product"', $head);
    }

    /**
     * Mirrors what a Yii3 layout does: WebViewRenderer injects meta/link tags,
     * the layout renders the title and JSON-LD manually.
     */
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
