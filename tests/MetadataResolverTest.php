<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use Rasuvaeff\Yii3Seo\Alternates;
use Rasuvaeff\Yii3Seo\Author;
use Rasuvaeff\Yii3Seo\Icons;
use Rasuvaeff\Yii3Seo\JsonLd;
use Rasuvaeff\Yii3Seo\Metadata;
use Rasuvaeff\Yii3Seo\MetadataDefaults;
use Rasuvaeff\Yii3Seo\MetadataResolver;
use Rasuvaeff\Yii3Seo\MetaTag;
use Rasuvaeff\Yii3Seo\OgImage;
use Rasuvaeff\Yii3Seo\OpenGraph;
use Rasuvaeff\Yii3Seo\Robots;
use Rasuvaeff\Yii3Seo\Title;
use Rasuvaeff\Yii3Seo\TwitterCard;
use Rasuvaeff\Yii3Seo\Verification;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(MetadataResolver::class)]
final class MetadataResolverTest
{
    public function resolvesDefaultsAndSocialFallbackCascade(): void
    {
        $defaultImage = new OgImage(url: '/default.jpg');
        $defaultJsonLd = JsonLd::fromArray(['@type' => 'Organization']);
        $pageJsonLd = JsonLd::fromArray(['@type' => 'WebPage']);
        $defaultMeta = MetaTag::property('fb:app_id', '123');
        $pageMeta = MetaTag::name('rating', 'general');
        $author = new Author(name: 'Alice');
        $robots = Robots::index();
        $icons = new Icons(icon: '/favicon.ico');
        $verification = new Verification(google: 'token');
        $alternates = new Alternates(canonical: '/page');
        $defaults = new MetadataDefaults(
            metadataBase: 'https://example.com',
            title: Title::template('%s | Brand', default: 'Brand'),
            applicationName: 'Application',
            generator: 'Yii',
            themeColor: '#000000',
            colorScheme: 'dark',
            robots: $robots,
            openGraph: new OpenGraph(siteName: 'Brand', images: [$defaultImage]),
            twitter: new TwitterCard(site: '@brand'),
            icons: $icons,
            verification: $verification,
            jsonLd: [$defaultJsonLd],
            other: [$defaultMeta],
        );
        $metadata = new Metadata(
            title: 'Page',
            description: 'Page description',
            keywords: ['php', 'seo'],
            authors: [$author],
            creator: 'Creator',
            publisher: 'Publisher',
            alternates: $alternates,
            openGraph: new OpenGraph(),
            twitter: new TwitterCard(),
            manifest: '/manifest.webmanifest',
            jsonLd: [$pageJsonLd],
            other: [$pageMeta],
        );

        $resolved = (new MetadataResolver())->resolve(metadata: $metadata, defaults: $defaults);
        $openGraph = $resolved->getOpenGraph();
        $twitter = $resolved->getTwitter();

        Assert::same($resolved->getMetadataBase(), 'https://example.com');
        Assert::same($resolved->getTitle(), 'Page | Brand');
        Assert::same($resolved->getDescription(), 'Page description');
        Assert::same($resolved->getKeywords(), ['php', 'seo']);
        Assert::same($resolved->getAuthors(), [$author]);
        Assert::same($resolved->getApplicationName(), 'Application');
        Assert::same($resolved->getGenerator(), 'Yii');
        Assert::same($resolved->getCreator(), 'Creator');
        Assert::same($resolved->getPublisher(), 'Publisher');
        Assert::same($resolved->getThemeColor(), '#000000');
        Assert::same($resolved->getColorScheme(), 'dark');
        Assert::same($resolved->getRobots(), $robots);
        Assert::same($resolved->getAlternates(), $alternates);
        Assert::same($resolved->getIcons(), $icons);
        Assert::same($resolved->getManifest(), '/manifest.webmanifest');
        Assert::same($resolved->getVerification(), $verification);
        Assert::same($resolved->getJsonLd(), [$defaultJsonLd, $pageJsonLd]);
        Assert::same($resolved->getOther(), [$defaultMeta, $pageMeta]);
        Assert::instanceOf($openGraph, OpenGraph::class);
        Assert::same($openGraph->getTitle(), 'Page | Brand');
        Assert::same($openGraph->getDescription(), 'Page description');
        Assert::same($openGraph->getType(), 'website');
        Assert::same($openGraph->getSiteName(), 'Brand');
        Assert::same($openGraph->getImages(), [$defaultImage]);
        Assert::instanceOf($twitter, TwitterCard::class);
        Assert::same($twitter->getCard(), 'summary_large_image');
        Assert::same($twitter->getSite(), '@brand');
        Assert::same($twitter->getTitle(), 'Page | Brand');
        Assert::same($twitter->getDescription(), 'Page description');
        Assert::same($twitter->getImages(), ['/default.jpg']);
    }

    public function pageOverridesNestedDefaultsFieldByField(): void
    {
        $defaultImage = new OgImage(url: '/default.jpg');
        $pageImage = new OgImage(url: '/page.jpg');
        $defaults = new MetadataDefaults(
            openGraph: new OpenGraph(
                title: 'Default title',
                description: 'Default description',
                type: 'website',
                url: '/default',
                siteName: 'Default site',
                locale: 'en_US',
                images: [$defaultImage],
            ),
            twitter: new TwitterCard(
                card: 'summary',
                site: '@default',
                creator: '@default-author',
                title: 'Default title',
                description: 'Default description',
                images: ['/default-twitter.jpg'],
            ),
        );
        $metadata = new Metadata(
            openGraph: new OpenGraph(title: 'Page title', type: 'article', images: [$pageImage]),
            twitter: new TwitterCard(card: 'summary_large_image', creator: '@page-author', images: ['/page-twitter.jpg']),
        );

        $resolved = (new MetadataResolver())->resolve(metadata: $metadata, defaults: $defaults);
        $openGraph = $resolved->getOpenGraph();
        $twitter = $resolved->getTwitter();

        Assert::instanceOf($openGraph, OpenGraph::class);
        Assert::same($openGraph->getTitle(), 'Page title');
        Assert::same($openGraph->getDescription(), 'Default description');
        Assert::same($openGraph->getType(), 'article');
        Assert::same($openGraph->getUrl(), '/default');
        Assert::same($openGraph->getSiteName(), 'Default site');
        Assert::same($openGraph->getLocale(), 'en_US');
        Assert::same($openGraph->getImages(), [$pageImage]);
        Assert::instanceOf($twitter, TwitterCard::class);
        Assert::same($twitter->getCard(), 'summary_large_image');
        Assert::same($twitter->getSite(), '@default');
        Assert::same($twitter->getCreator(), '@page-author');
        Assert::same($twitter->getTitle(), 'Default title');
        Assert::same($twitter->getDescription(), 'Default description');
        Assert::same($twitter->getImages(), ['/page-twitter.jpg']);
    }

    public function resolvesEmptyMetadata(): void
    {
        $resolved = (new MetadataResolver())->resolve();

        Assert::same($resolved->getTitle(), '');
        Assert::null($resolved->getDescription());
        Assert::null($resolved->getOpenGraph());
        Assert::null($resolved->getTwitter());
        Assert::same($resolved->getJsonLd(), []);
        Assert::same($resolved->getOther(), []);
    }

    public function absoluteTitleBypassesDefaultTemplate(): void
    {
        $resolved = (new MetadataResolver())->resolve(
            metadata: new Metadata(title: Title::absolute('Exact')),
            defaults: new MetadataDefaults(title: Title::template('%s | Brand', default: 'Brand')),
        );

        Assert::same($resolved->getTitle(), 'Exact');
    }
}
