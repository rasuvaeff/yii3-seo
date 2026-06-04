<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
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

#[CoversClass(Metadata::class)]
final class MetadataTest extends TestCase
{
    #[Test]
    public function stringTitleIsNormalisedToTitleObject(): void
    {
        $title = (new Metadata(title: 'Home'))->getTitle();

        $this->assertInstanceOf(Title::class, $title);
        $this->assertSame('Home', $title->getValue());
        $this->assertFalse($title->isAbsolute());
    }

    #[Test]
    public function titleObjectIsKept(): void
    {
        $title = Title::absolute('Exact');

        $this->assertSame($title, (new Metadata(title: $title))->getTitle());
    }

    #[Test]
    public function titleIsNullByDefault(): void
    {
        $this->assertNull((new Metadata())->getTitle());
    }

    #[Test]
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

        $this->assertSame('D', $metadata->getDescription());
        $this->assertSame(['a', 'b'], $metadata->getKeywords());
        $this->assertSame([$author], $metadata->getAuthors());
        $this->assertSame('App', $metadata->getApplicationName());
        $this->assertSame('Gen', $metadata->getGenerator());
        $this->assertSame('Creator', $metadata->getCreator());
        $this->assertSame('Publisher', $metadata->getPublisher());
        $this->assertSame('#fff', $metadata->getThemeColor());
        $this->assertSame('dark', $metadata->getColorScheme());
        $this->assertSame($robots, $metadata->getRobots());
        $this->assertSame($alternates, $metadata->getAlternates());
        $this->assertSame($openGraph, $metadata->getOpenGraph());
        $this->assertSame($twitter, $metadata->getTwitter());
        $this->assertSame($icons, $metadata->getIcons());
        $this->assertSame('/site.webmanifest', $metadata->getManifest());
        $this->assertSame($verification, $metadata->getVerification());
        $this->assertSame([$jsonLd], $metadata->getJsonLd());
        $this->assertSame([$other], $metadata->getOther());
    }

    #[Test]
    public function collectionsAreEmptyByDefault(): void
    {
        $metadata = new Metadata();

        $this->assertSame([], $metadata->getKeywords());
        $this->assertSame([], $metadata->getAuthors());
        $this->assertSame([], $metadata->getJsonLd());
        $this->assertSame([], $metadata->getOther());
    }
}
