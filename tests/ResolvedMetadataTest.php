<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\Alternates;
use Rasuvaeff\Yii3Seo\Author;
use Rasuvaeff\Yii3Seo\Icon;
use Rasuvaeff\Yii3Seo\Icons;
use Rasuvaeff\Yii3Seo\JsonLd;
use Rasuvaeff\Yii3Seo\MetaTag;
use Rasuvaeff\Yii3Seo\OgImage;
use Rasuvaeff\Yii3Seo\OpenGraph;
use Rasuvaeff\Yii3Seo\ResolvedMetadata;
use Rasuvaeff\Yii3Seo\Robots;
use Rasuvaeff\Yii3Seo\TwitterCard;
use Rasuvaeff\Yii3Seo\Verification;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(ResolvedMetadata::class)]
final class ResolvedMetadataTest
{
    public function gettersReturnResolvedValues(): void
    {
        $author = new Author(name: 'Alice');
        $robots = Robots::index();
        $alternates = new Alternates(canonical: '/page');
        $openGraph = new OpenGraph(type: 'article');
        $twitter = new TwitterCard(card: 'summary');
        $icons = new Icons(icon: '/favicon.ico');
        $verification = new Verification(google: 'token');
        $jsonLd = JsonLd::fromArray(['@type' => 'WebPage']);
        $other = MetaTag::name('rating', 'general');

        $resolved = new ResolvedMetadata(
            metadataBase: 'https://example.com',
            title: 'Page | Brand',
            description: 'Description',
            keywords: ['php', 'seo'],
            authors: [$author],
            applicationName: 'Application',
            generator: 'Yii',
            creator: 'Creator',
            publisher: 'Publisher',
            themeColor: '#ffffff',
            colorScheme: 'light',
            robots: $robots,
            alternates: $alternates,
            openGraph: $openGraph,
            twitter: $twitter,
            icons: $icons,
            manifest: '/manifest.webmanifest',
            verification: $verification,
            jsonLd: [$jsonLd],
            other: [$other],
        );

        Assert::same($resolved->getMetadataBase(), 'https://example.com');
        Assert::same($resolved->getTitle(), 'Page | Brand');
        Assert::same($resolved->getDescription(), 'Description');
        Assert::same($resolved->getKeywords(), ['php', 'seo']);
        Assert::same($resolved->getAuthors(), [$author]);
        Assert::same($resolved->getApplicationName(), 'Application');
        Assert::same($resolved->getGenerator(), 'Yii');
        Assert::same($resolved->getCreator(), 'Creator');
        Assert::same($resolved->getPublisher(), 'Publisher');
        Assert::same($resolved->getThemeColor(), '#ffffff');
        Assert::same($resolved->getColorScheme(), 'light');
        Assert::same($resolved->getRobots(), $robots);
        Assert::same($resolved->getAlternates(), $alternates);
        Assert::same($resolved->getOpenGraph(), $openGraph);
        Assert::same($resolved->getTwitter(), $twitter);
        Assert::same($resolved->getIcons(), $icons);
        Assert::same($resolved->getManifest(), '/manifest.webmanifest');
        Assert::same($resolved->getVerification(), $verification);
        Assert::same($resolved->getJsonLd(), [$jsonLd]);
        Assert::same($resolved->getOther(), [$other]);
    }

    public function defaultsRepresentEmptyMetadata(): void
    {
        $resolved = new ResolvedMetadata();

        Assert::null($resolved->getMetadataBase());
        Assert::same($resolved->getTitle(), '');
        Assert::null($resolved->getDescription());
        Assert::same($resolved->getKeywords(), []);
        Assert::same($resolved->getAuthors(), []);
        Assert::null($resolved->getOpenGraph());
        Assert::null($resolved->getTwitter());
        Assert::same($resolved->getJsonLd(), []);
        Assert::same($resolved->getOther(), []);
    }

    public function exportsEverythingWithUrlsResolvedAgainstMetadataBase(): void
    {
        $resolved = new ResolvedMetadata(
            metadataBase: 'https://example.com',
            title: 'Page | Brand',
            description: 'Description',
            keywords: ['php', 'seo'],
            authors: [new Author(name: 'Alice', url: 'https://example.com/alice')],
            applicationName: 'Application',
            generator: 'Yii',
            creator: 'Creator',
            publisher: 'Publisher',
            themeColor: '#ffffff',
            colorScheme: 'light',
            robots: Robots::index()->withGoogleBot('noarchive'),
            alternates: new Alternates(canonical: '/page', languages: ['en-US' => '/en/page']),
            openGraph: new OpenGraph(
                title: 'Og title',
                description: 'Og description',
                type: 'article',
                url: '/page',
                siteName: 'Brand',
                locale: 'en_US',
                images: [new OgImage(url: '/og.jpg', width: 1200, height: 630, alt: 'Cover', type: 'image/jpeg')],
            ),
            twitter: new TwitterCard(card: 'summary', site: '@brand', creator: '@alice', images: ['/twitter.jpg']),
            icons: new Icons(icon: '/favicon.ico'),
            manifest: '/manifest.webmanifest',
            verification: new Verification(google: 'token', other: ['pinterest-site-verification' => 'pin']),
            jsonLd: [JsonLd::fromArray(['@type' => 'WebPage'])],
            other: [MetaTag::name('rating', 'general')],
        );

        Assert::same($resolved->toArray(), [
            'metadataBase' => 'https://example.com',
            'title' => 'Page | Brand',
            'description' => 'Description',
            'keywords' => ['php', 'seo'],
            'authors' => [['name' => 'Alice', 'url' => 'https://example.com/alice']],
            'applicationName' => 'Application',
            'generator' => 'Yii',
            'creator' => 'Creator',
            'publisher' => 'Publisher',
            'themeColor' => '#ffffff',
            'colorScheme' => 'light',
            'robots' => ['directives' => ['index', 'follow'], 'googleBot' => ['noarchive']],
            'alternates' => [
                'canonical' => 'https://example.com/page',
                'languages' => ['en-US' => 'https://example.com/en/page'],
            ],
            'openGraph' => [
                'title' => 'Og title',
                'description' => 'Og description',
                'type' => 'article',
                'url' => 'https://example.com/page',
                'siteName' => 'Brand',
                'locale' => 'en_US',
                'images' => [[
                    'url' => 'https://example.com/og.jpg',
                    'width' => 1200,
                    'height' => 630,
                    'alt' => 'Cover',
                    'type' => 'image/jpeg',
                ]],
            ],
            'twitter' => [
                'card' => 'summary',
                'site' => '@brand',
                'creator' => '@alice',
                'images' => ['https://example.com/twitter.jpg'],
            ],
            'icons' => [['rel' => 'icon', 'url' => '/favicon.ico']],
            'manifest' => '/manifest.webmanifest',
            'verification' => [
                'google' => 'token',
                'other' => ['pinterest-site-verification' => 'pin'],
            ],
            'jsonLd' => [['@type' => 'WebPage']],
            'other' => [['attributeType' => 'name', 'attributeValue' => 'rating', 'content' => 'general']],
        ]);
    }

    public function exportOmitsNullsAndEmptyCollectionsButKeepsTitle(): void
    {
        Assert::same((new ResolvedMetadata())->toArray(), ['title' => '']);
    }

    public function exportAppliesTheSameDefaultsAsRendering(): void
    {
        $resolved = new ResolvedMetadata(openGraph: new OpenGraph(), twitter: new TwitterCard());
        $exported = $resolved->toArray();

        Assert::same($exported['openGraph'], ['type' => 'website']);
        Assert::same($exported['twitter'], ['card' => 'summary_large_image']);
    }

    public function exportKeepsIconAndManifestUrlsAsConfigured(): void
    {
        $resolved = new ResolvedMetadata(
            metadataBase: 'https://example.com',
            icons: new Icons(other: [new Icon(rel: 'apple-touch-icon', url: '/touch.png', sizes: '180x180', type: 'image/png')]),
            manifest: '/manifest.webmanifest',
        );

        Assert::same($resolved->toArray()['icons'], [[
            'rel' => 'apple-touch-icon',
            'url' => '/touch.png',
            'sizes' => '180x180',
            'type' => 'image/png',
        ]]);
        Assert::same($resolved->toArray()['manifest'], '/manifest.webmanifest');
    }

    public function exportThrowsOnRelativeUrlWithoutMetadataBase(): void
    {
        $resolved = new ResolvedMetadata(alternates: new Alternates(canonical: '/page'));

        try {
            $resolved->toArray();
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Relative URL "/page" requires a metadataBase');
        }
    }
}
