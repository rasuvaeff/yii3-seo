<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use Rasuvaeff\Yii3Seo\Alternates;
use Rasuvaeff\Yii3Seo\Author;
use Rasuvaeff\Yii3Seo\Icons;
use Rasuvaeff\Yii3Seo\JsonLd;
use Rasuvaeff\Yii3Seo\MetaTag;
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
}
