<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use Rasuvaeff\Yii3Seo\Alternates;
use Rasuvaeff\Yii3Seo\Author;
use Rasuvaeff\Yii3Seo\Icons;
use Rasuvaeff\Yii3Seo\JsonLd;
use Rasuvaeff\Yii3Seo\Metadata;
use Rasuvaeff\Yii3Seo\MetaTag;
use Rasuvaeff\Yii3Seo\OpenGraph;
use Rasuvaeff\Yii3Seo\Robots;
use Rasuvaeff\Yii3Seo\Title;
use Rasuvaeff\Yii3Seo\TwitterCard;
use Rasuvaeff\Yii3Seo\Verification;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Metadata::class)]
final class MetadataTest
{
    public function stringTitleIsNormalisedToTitleObject(): void
    {
        $title = (new Metadata(title: 'Home'))->getTitle();

        Assert::instanceOf($title, Title::class);
        Assert::same($title->getValue(), 'Home');
        Assert::false($title->isAbsolute());
    }

    public function titleObjectIsKept(): void
    {
        $title = Title::absolute('Exact');

        Assert::same((new Metadata(title: $title))->getTitle(), $title);
    }

    public function titleIsNullByDefault(): void
    {
        Assert::null((new Metadata())->getTitle());
    }

    public function gettersReturnValues(): void
    {
        $robots = Robots::noindex();
        $alternates = new Alternates(canonical: '/');
        $openGraph = new OpenGraph();
        $twitter = new TwitterCard();
        $icons = new Icons(icon: '/favicon.ico');
        $verification = new Verification(google: 'token');
        $author = new Author(name: 'Alice');
        $jsonLd = JsonLd::fromArray(['@type' => 'WebPage']);
        $other = MetaTag::name('x', 'y');

        $metadata = new Metadata(
            description: 'D',
            keywords: ['a', 'b'],
            authors: [$author],
            applicationName: 'App',
            generator: 'Gen',
            creator: 'Creator',
            publisher: 'Publisher',
            themeColor: '#fff',
            colorScheme: 'dark',
            robots: $robots,
            alternates: $alternates,
            openGraph: $openGraph,
            twitter: $twitter,
            icons: $icons,
            manifest: '/site.webmanifest',
            verification: $verification,
            jsonLd: [$jsonLd],
            other: [$other],
        );

        Assert::same($metadata->getDescription(), 'D');
        Assert::same($metadata->getKeywords(), ['a', 'b']);
        Assert::same($metadata->getAuthors(), [$author]);
        Assert::same($metadata->getApplicationName(), 'App');
        Assert::same($metadata->getGenerator(), 'Gen');
        Assert::same($metadata->getCreator(), 'Creator');
        Assert::same($metadata->getPublisher(), 'Publisher');
        Assert::same($metadata->getThemeColor(), '#fff');
        Assert::same($metadata->getColorScheme(), 'dark');
        Assert::same($metadata->getRobots(), $robots);
        Assert::same($metadata->getAlternates(), $alternates);
        Assert::same($metadata->getOpenGraph(), $openGraph);
        Assert::same($metadata->getTwitter(), $twitter);
        Assert::same($metadata->getIcons(), $icons);
        Assert::same($metadata->getManifest(), '/site.webmanifest');
        Assert::same($metadata->getVerification(), $verification);
        Assert::same($metadata->getJsonLd(), [$jsonLd]);
        Assert::same($metadata->getOther(), [$other]);
    }

    public function collectionsAreEmptyByDefault(): void
    {
        $metadata = new Metadata();

        Assert::same($metadata->getKeywords(), []);
        Assert::same($metadata->getAuthors(), []);
        Assert::same($metadata->getJsonLd(), []);
        Assert::same($metadata->getOther(), []);
    }
}
