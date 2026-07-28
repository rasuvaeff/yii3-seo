<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests\Integration;

use Rasuvaeff\Yii3Seo\Alternates;
use Rasuvaeff\Yii3Seo\Author;
use Rasuvaeff\Yii3Seo\Icons;
use Rasuvaeff\Yii3Seo\JsonLd;
use Rasuvaeff\Yii3Seo\Metadata;
use Rasuvaeff\Yii3Seo\MetadataDefaults;
use Rasuvaeff\Yii3Seo\OgImage;
use Rasuvaeff\Yii3Seo\OpenGraph;
use Rasuvaeff\Yii3Seo\Robots;
use Rasuvaeff\Yii3Seo\SelfCanonical;
use Rasuvaeff\Yii3Seo\SeoInjection;
use Rasuvaeff\Yii3Seo\Title;
use Rasuvaeff\Yii3Seo\TwitterCard;
use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Data\DataProvider;
use Testo\Test;
use Yiisoft\Html\Html;

/**
 * Byte-exact `<head>` snapshots for the four page shapes the package is meant to
 * cover. They freeze tag order, fallback behavior and URL resolution: any change
 * to what an application ships to crawlers has to be an explicit edit here.
 */
#[Test]
#[CoversNothing]
final class HeadSnapshotIntegrationTest
{
    #[DataProvider('pageProvider')]
    public function rendersTheExpectedHead(string $requestPath, ?Metadata $metadata, string $expected): void
    {
        $injection = new SeoInjection($this->defaults());
        $injection->setRequestPath($requestPath);

        if ($metadata instanceof Metadata) {
            $injection->setMetadata($metadata);
        }

        Assert::same($this->renderHead($injection), $expected);
    }

    public static function pageProvider(): iterable
    {
        yield 'minimal page' => [
            '/',
            null,
            <<<'HTML'
                <title>My Store</title>
                <meta property="og:title" content="My Store">
                <meta property="og:type" content="website">
                <meta property="og:site_name" content="My Store">
                <meta property="og:locale" content="en_US">
                <meta name="twitter:card" content="summary_large_image">
                <meta name="twitter:site" content="@mystore">
                <meta name="twitter:title" content="My Store">
                <link rel="canonical" href="https://example.com/">
                <link rel="icon" href="/favicon.ico">
                HTML,
        ];

        yield 'article' => [
            '/blog/hello-world',
            new Metadata(
                title: 'Hello World',
                description: 'The first post on our engineering blog.',
                authors: [new Author(name: 'Alice', url: 'https://example.com/authors/alice')],
                robots: Robots::index()->withMaxImagePreview('large'),
                openGraph: new OpenGraph(
                    type: 'article',
                    images: [new OgImage(url: '/og/hello-world.jpg', width: 1200, height: 630, alt: 'Hello World')],
                ),
                jsonLd: [JsonLd::fromArray([
                    '@context' => 'https://schema.org',
                    '@type' => 'Article',
                    'headline' => 'Hello World',
                ])],
            ),
            <<<'HTML'
                <title>Hello World | My Store</title>
                <meta name="description" content="The first post on our engineering blog.">
                <meta name="author" content="Alice">
                <meta name="robots" content="index, follow, max-image-preview:large">
                <meta property="og:title" content="Hello World | My Store">
                <meta property="og:type" content="article">
                <meta property="og:description" content="The first post on our engineering blog.">
                <meta property="og:site_name" content="My Store">
                <meta property="og:locale" content="en_US">
                <meta property="og:image" content="https://example.com/og/hello-world.jpg">
                <meta property="og:image:width" content="1200">
                <meta property="og:image:height" content="630">
                <meta property="og:image:alt" content="Hello World">
                <meta name="twitter:card" content="summary_large_image">
                <meta name="twitter:site" content="@mystore">
                <meta name="twitter:title" content="Hello World | My Store">
                <meta name="twitter:description" content="The first post on our engineering blog.">
                <meta name="twitter:image" content="https://example.com/og/hello-world.jpg">
                <link rel="canonical" href="https://example.com/blog/hello-world">
                <link rel="icon" href="/favicon.ico">
                <link rel="author" href="https://example.com/authors/alice">
                <script type="application/ld+json">
                {
                    "@context": "https://schema.org",
                    "@type": "Article",
                    "headline": "Hello World"
                }
                </script>
                HTML,
        ];

        yield 'product' => [
            '/products/awesome?page=2&utm_source=mail',
            new Metadata(
                title: 'Awesome Product',
                description: 'Buy the awesome product.',
                keywords: ['awesome', 'product'],
                openGraph: new OpenGraph(
                    type: 'product',
                    images: [new OgImage(
                        url: '/og/awesome.jpg',
                        width: 1200,
                        height: 630,
                        alt: 'Awesome',
                        type: 'image/jpeg',
                    )],
                ),
                twitter: new TwitterCard(creator: '@alice'),
                jsonLd: [JsonLd::fromArray([
                    '@context' => 'https://schema.org',
                    '@type' => 'Product',
                    'name' => 'Awesome Product',
                ])],
            ),
            <<<'HTML'
                <title>Awesome Product | My Store</title>
                <meta name="description" content="Buy the awesome product.">
                <meta name="keywords" content="awesome, product">
                <meta property="og:title" content="Awesome Product | My Store">
                <meta property="og:type" content="product">
                <meta property="og:description" content="Buy the awesome product.">
                <meta property="og:site_name" content="My Store">
                <meta property="og:locale" content="en_US">
                <meta property="og:image" content="https://example.com/og/awesome.jpg">
                <meta property="og:image:width" content="1200">
                <meta property="og:image:height" content="630">
                <meta property="og:image:alt" content="Awesome">
                <meta property="og:image:type" content="image/jpeg">
                <meta name="twitter:card" content="summary_large_image">
                <meta name="twitter:site" content="@mystore">
                <meta name="twitter:creator" content="@alice">
                <meta name="twitter:title" content="Awesome Product | My Store">
                <meta name="twitter:description" content="Buy the awesome product.">
                <meta name="twitter:image" content="https://example.com/og/awesome.jpg">
                <link rel="canonical" href="https://example.com/products/awesome?page=2">
                <link rel="icon" href="/favicon.ico">
                <script type="application/ld+json">
                {
                    "@context": "https://schema.org",
                    "@type": "Product",
                    "name": "Awesome Product"
                }
                </script>
                HTML,
        ];

        yield 'multilingual page' => [
            '/en/products/awesome',
            new Metadata(
                title: 'Awesome Product',
                alternates: new Alternates(languages: [
                    'en' => '/en/products/awesome',
                    'ru' => '/ru/products/awesome',
                    'x-default' => '/products/awesome',
                ]),
                openGraph: new OpenGraph(locale: 'en_GB'),
            ),
            <<<'HTML'
                <title>Awesome Product | My Store</title>
                <meta property="og:title" content="Awesome Product | My Store">
                <meta property="og:type" content="website">
                <meta property="og:site_name" content="My Store">
                <meta property="og:locale" content="en_GB">
                <meta name="twitter:card" content="summary_large_image">
                <meta name="twitter:site" content="@mystore">
                <meta name="twitter:title" content="Awesome Product | My Store">
                <link rel="canonical" href="https://example.com/en/products/awesome">
                <link rel="alternate" hreflang="en" href="https://example.com/en/products/awesome">
                <link rel="alternate" hreflang="ru" href="https://example.com/ru/products/awesome">
                <link rel="alternate" hreflang="x-default" href="https://example.com/products/awesome">
                <link rel="icon" href="/favicon.ico">
                HTML,
        ];
    }

    private function defaults(): MetadataDefaults
    {
        return new MetadataDefaults(
            metadataBase: 'https://example.com',
            title: Title::template('%s | My Store', default: 'My Store'),
            openGraph: new OpenGraph(siteName: 'My Store', locale: 'en_US'),
            twitter: new TwitterCard(card: 'summary_large_image', site: '@mystore'),
            icons: new Icons(icon: '/favicon.ico'),
            selfCanonical: SelfCanonical::keepingQuery('page'),
        );
    }

    private function renderHead(SeoInjection $injection): string
    {
        $head = ['<title>' . Html::encode($injection->getTitle()) . '</title>'];

        foreach ($injection->getMetaTags() as $tag) {
            $head[] = $tag->render();
        }

        foreach ($injection->getLinkTags() as $tag) {
            $head[] = $tag->render();
        }

        $jsonLd = $injection->getJsonLdHtml();

        if ($jsonLd !== '') {
            $head[] = $jsonLd;
        }

        return implode("\n", $head);
    }
}
