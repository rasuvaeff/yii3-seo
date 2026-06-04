<?php

declare(strict_types=1);

/**
 * Full flow with site-wide defaults + per-page metadata: title template,
 * metadataBase URL resolution, OpenGraph images, Twitter card, robots,
 * verification, icons, hreflang, JSON-LD and custom meta.
 *
 * Run:
 *   docker run --rm -v "$PWD":/app -w /app composer:2 php examples/full-page.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Rasuvaeff\Yii3Seo\Alternates;
use Rasuvaeff\Yii3Seo\Icons;
use Rasuvaeff\Yii3Seo\JsonLd;
use Rasuvaeff\Yii3Seo\Metadata;
use Rasuvaeff\Yii3Seo\MetadataDefaults;
use Rasuvaeff\Yii3Seo\MetaTag;
use Rasuvaeff\Yii3Seo\OgImage;
use Rasuvaeff\Yii3Seo\OpenGraph;
use Rasuvaeff\Yii3Seo\Robots;
use Rasuvaeff\Yii3Seo\SeoInjection;
use Rasuvaeff\Yii3Seo\SeoMetadataEvent;
use Rasuvaeff\Yii3Seo\SetSeoMetadataEventHandler;
use Rasuvaeff\Yii3Seo\Title;
use Rasuvaeff\Yii3Seo\TwitterCard;
use Rasuvaeff\Yii3Seo\Verification;

// In Yii3 this comes from the `rasuvaeff/yii3-seo` `defaults` param.
$defaults = new MetadataDefaults(
    metadataBase: 'https://example.com',
    title: Title::template('%s | My Store', default: 'My Store'),
    openGraph: new OpenGraph(siteName: 'My Store', locale: 'en_US'),
    twitter: new TwitterCard(card: 'summary_large_image', site: '@mystore'),
    icons: new Icons(icon: '/favicon.ico', apple: '/apple-touch-icon.png'),
    verification: new Verification(google: 'google-token'),
);

$seoInjection = new SeoInjection($defaults);
$handler = new SetSeoMetadataEventHandler(seoInjection: $seoInjection);

$handler(new SeoMetadataEvent(
    metadata: new Metadata(
        title: 'Awesome Product',                          // -> "Awesome Product | My Store"
        description: 'Buy the awesome product at the best price.',
        robots: Robots::index()->withMaxImagePreview('large'),
        alternates: new Alternates(
            canonical: '/products/awesome-product',         // resolved against metadataBase
            languages: [
                'en'        => '/en/products/awesome-product',
                'ru'        => '/ru/products/awesome-product',
                'x-default' => '/products/awesome-product',
            ],
        ),
        openGraph: new OpenGraph(
            type: 'product',
            images: [new OgImage(url: '/og/awesome-product.jpg', width: 1200, height: 630, alt: 'Awesome Product')],
        ),
        twitter: new TwitterCard(creator: '@author'),       // card/site/image inherited or derived
        jsonLd: [JsonLd::fromArray([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => 'Awesome Product',
            'offers' => ['@type' => 'Offer', 'price' => '29.99', 'priceCurrency' => 'USD'],
        ])],
        other: [MetaTag::name(name: 'rating', content: 'general')],
    ),
));

// --- layout.php renders these ---
echo '<title>' . htmlspecialchars($seoInjection->getTitle(), ENT_QUOTES) . '</title>' . PHP_EOL;

// --- WebViewRenderer injects these automatically ---
echo '<!-- meta tags -->' . PHP_EOL;
foreach ($seoInjection->getMetaTags() as $tag) {
    echo $tag->render() . PHP_EOL;
}

echo '<!-- link tags -->' . PHP_EOL;
foreach ($seoInjection->getLinkTags() as $tag) {
    echo $tag->render() . PHP_EOL;
}

// --- layout.php renders this ---
echo '<!-- json-ld -->' . PHP_EOL;
echo $seoInjection->getJsonLdHtml() . PHP_EOL;
