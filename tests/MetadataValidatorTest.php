<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use Rasuvaeff\Yii3Seo\Alternates;
use Rasuvaeff\Yii3Seo\MetadataIssue;
use Rasuvaeff\Yii3Seo\MetadataValidationResult;
use Rasuvaeff\Yii3Seo\MetadataValidator;
use Rasuvaeff\Yii3Seo\MetaTag;
use Rasuvaeff\Yii3Seo\OgImage;
use Rasuvaeff\Yii3Seo\OpenGraph;
use Rasuvaeff\Yii3Seo\ResolvedMetadata;
use Rasuvaeff\Yii3Seo\Robots;
use Rasuvaeff\Yii3Seo\TwitterCard;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(MetadataValidator::class)]
final class MetadataValidatorTest
{
    private const string DESCRIPTION = 'A perfectly reasonable meta description for a perfectly ordinary page.';

    private MetadataValidator $fixture;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->fixture = new MetadataValidator();
    }

    public function reportsNothingForCompleteMetadata(): void
    {
        $result = $this->fixture->validate($this->completeMetadata());

        Assert::same($this->codes($result), []);
        Assert::true($result->isValid());
    }

    public function reportsEveryMissingElementOfEmptyMetadata(): void
    {
        $result = $this->fixture->validate(new ResolvedMetadata());

        Assert::same($this->codes($result), [
            'title.missing',
            'description.missing',
            'canonical.missing',
            'image.missing',
        ]);
        Assert::false($result->isValid());
    }

    public function missingTitleIsAnErrorAndOtherGapsAreWarnings(): void
    {
        $result = $this->fixture->validate(new ResolvedMetadata());

        Assert::count($result->getErrors(), 1);
        Assert::same($result->getErrors()[0]->getCode(), 'title.missing');
        Assert::count($result->getWarnings(), 3);
    }

    #[DataProvider('lengthProvider')]
    public function reportsAdvisoryLengths(string $title, string $description, array $expected): void
    {
        $result = $this->fixture->validate(new ResolvedMetadata(title: $title, description: $description));

        Assert::same(
            array_values(array_filter(
                $this->codes($result),
                static fn(string $code): bool => str_starts_with($code, 'title.') || str_starts_with($code, 'description.'),
            )),
            $expected,
        );
        Assert::true($result->isValid());
    }

    public static function lengthProvider(): iterable
    {
        yield 'within bounds' => [
            str_repeat('a', 60),
            str_repeat('b', 160),
            [],
        ];

        yield 'title too long' => [
            str_repeat('a', 61),
            str_repeat('b', 160),
            ['title.too_long'],
        ];

        yield 'description too long' => [
            str_repeat('a', 60),
            str_repeat('b', 161),
            ['description.too_long'],
        ];

        yield 'description too short' => [
            str_repeat('a', 60),
            str_repeat('b', 49),
            ['description.too_short'],
        ];

        yield 'description at the recommended minimum' => [
            str_repeat('a', 60),
            str_repeat('b', 50),
            [],
        ];

        yield 'multibyte characters are counted as characters, not bytes' => [
            str_repeat('я', 60),
            str_repeat('я', 100),
            [],
        ];
    }

    public function advisoryLengthMessagesCarryTheActualAndSuggestedLength(): void
    {
        $longTitle = $this->fixture->validate(new ResolvedMetadata(title: str_repeat('a', 61)));
        $longDescription = $this->fixture->validate(
            new ResolvedMetadata(title: 'Page', description: str_repeat('b', 161)),
        );
        $shortDescription = $this->fixture->validate(
            new ResolvedMetadata(title: 'Page', description: str_repeat('b', 49)),
        );

        Assert::same(
            $longTitle->getWarnings()[0]->getMessage(),
            'Title is 61 characters long; search results usually truncate after 60',
        );
        Assert::same(
            $longDescription->getWarnings()[0]->getMessage(),
            'Description is 161 characters long; search results usually truncate after 160',
        );
        Assert::same(
            $shortDescription->getWarnings()[0]->getMessage(),
            'Description is 49 characters long; at least 50 characters is recommended',
        );
    }

    public function reportsUnresolvableCrawlerUrlsInsteadOfThrowing(): void
    {
        $result = $this->fixture->validate(new ResolvedMetadata(
            title: 'Page',
            alternates: new Alternates(canonical: '/page', languages: ['de-DE' => '/de/page']),
            openGraph: new OpenGraph(url: '/page', images: [new OgImage(url: '/og.jpg', width: 1, height: 1, alt: 'a')]),
            twitter: new TwitterCard(images: ['/twitter.jpg']),
        ));

        Assert::count(
            array_filter($result->getIssues(), static fn(MetadataIssue $i): bool => $i->getCode() === 'url.unresolvable'),
            5,
        );
        Assert::string($result->getErrors()[0]->getMessage())->contains('canonical: Relative URL "/page"');
    }

    public function reportsUnresolvableUrlsAlongsideTheMissingCanonical(): void
    {
        $result = $this->fixture->validate(new ResolvedMetadata(
            title: 'Page',
            description: self::DESCRIPTION,
            openGraph: new OpenGraph(url: '/page', images: [new OgImage(url: '/og.jpg', width: 1, height: 1, alt: 'a')]),
        ));

        Assert::same($this->codes($result), ['url.unresolvable', 'url.unresolvable', 'canonical.missing']);
    }

    public function reportsEveryUnresolvableUrlWhenThereIsNoOgUrl(): void
    {
        $result = $this->fixture->validate(new ResolvedMetadata(
            title: 'Page',
            description: self::DESCRIPTION,
            alternates: new Alternates(canonical: '/page', languages: ['de-DE' => '/de/page']),
            twitter: new TwitterCard(images: ['/twitter.jpg']),
        ));

        Assert::same($this->codes($result), ['url.unresolvable', 'url.unresolvable', 'url.unresolvable']);
    }

    public function skipsTheMismatchCheckWhenOneOfTheUrlsCannotBeResolved(): void
    {
        $result = $this->fixture->validate(new ResolvedMetadata(
            metadataBase: 'https://example.com',
            title: 'Page',
            description: self::DESCRIPTION,
            alternates: new Alternates(canonical: '/page'),
            openGraph: new OpenGraph(
                url: 'https://example.com/a b',
                images: [new OgImage(url: '/og.jpg', width: 1, height: 1, alt: 'a')],
            ),
        ));

        Assert::same($this->codes($result), ['url.unresolvable']);
    }

    public function detectsCanonicalAndOgUrlMismatch(): void
    {
        $result = $this->fixture->validate(new ResolvedMetadata(
            metadataBase: 'https://example.com',
            title: 'Page',
            alternates: new Alternates(canonical: '/page'),
            openGraph: new OpenGraph(url: '/other', images: [new OgImage(url: '/og.jpg', width: 1, height: 1, alt: 'a')]),
        ));

        Assert::same($this->codes($result), ['description.missing', 'canonical.og_url_mismatch']);
        Assert::string($result->getErrors()[0]->getMessage())
            ->contains('Canonical URL "https://example.com/page" does not match og:url "https://example.com/other"');
    }

    public function acceptsCanonicalMatchingOgUrlInDifferentNotation(): void
    {
        $result = $this->fixture->validate(new ResolvedMetadata(
            metadataBase: 'https://example.com',
            title: 'Page',
            alternates: new Alternates(canonical: '/page'),
            openGraph: new OpenGraph(url: 'https://example.com/page', images: [
                new OgImage(url: '/og.jpg', width: 1, height: 1, alt: 'a'),
            ]),
        ));

        Assert::same($this->codes($result), ['description.missing']);
    }

    public function reportsSocialImageProblems(): void
    {
        $result = $this->fixture->validate(new ResolvedMetadata(
            metadataBase: 'https://example.com',
            title: 'Page',
            alternates: new Alternates(canonical: '/page'),
            openGraph: new OpenGraph(images: [new OgImage(url: '/og.jpg')]),
        ));

        Assert::same($this->codes($result), [
            'description.missing',
            'image.alt_missing',
            'image.dimensions_missing',
        ]);
    }

    public function partialImageDimensionsAreStillReported(): void
    {
        $result = $this->fixture->validate(new ResolvedMetadata(
            metadataBase: 'https://example.com',
            title: 'Page',
            description: str_repeat('A perfectly reasonable meta description. ', 3),
            alternates: new Alternates(canonical: '/page'),
            openGraph: new OpenGraph(images: [new OgImage(url: '/og.jpg', width: 1200, alt: 'Cover')]),
        ));

        Assert::same($this->codes($result), ['image.dimensions_missing']);
    }

    public function twitterImageAloneSatisfiesTheSocialImageCheck(): void
    {
        $result = $this->fixture->validate(new ResolvedMetadata(
            metadataBase: 'https://example.com',
            title: 'Page',
            alternates: new Alternates(canonical: '/page'),
            twitter: new TwitterCard(images: ['/twitter.jpg']),
        ));

        Assert::same($this->codes($result), ['description.missing']);
    }

    #[DataProvider('conflictingRobotsProvider')]
    public function detectsConflictingRobotsDirectives(Robots $robots, array $expected): void
    {
        $result = $this->fixture->validate(new ResolvedMetadata(robots: $robots));

        Assert::same(
            array_values(array_map(
                static fn(MetadataIssue $issue): string => $issue->getMessage(),
                array_filter(
                    $result->getIssues(),
                    static fn(MetadataIssue $issue): bool => $issue->getCode() === 'robots.conflicting',
                ),
            )),
            $expected,
        );
    }

    public static function conflictingRobotsProvider(): iterable
    {
        yield 'index and noindex' => [
            new Robots(['index', 'noindex']),
            ['Conflicting robots directives "index" and "noindex"'],
        ];

        yield 'follow and nofollow' => [
            new Robots(['follow', 'nofollow']),
            ['Conflicting robots directives "follow" and "nofollow"'],
        ];

        yield 'none with index' => [
            new Robots(['none', 'index']),
            ['Conflicting robots directives "none" and "index"'],
        ];

        yield 'all with noindex' => [
            new Robots(['all', 'noindex']),
            ['Conflicting robots directives "all" and "noindex"'],
        ];

        yield 'all with none' => [
            new Robots(['all', 'none']),
            ['Conflicting robots directives "all" and "none"'],
        ];

        yield 'all with nofollow' => [
            new Robots(['all', 'nofollow']),
            ['Conflicting robots directives "all" and "nofollow"'],
        ];

        yield 'none with follow' => [
            new Robots(['none', 'follow']),
            ['Conflicting robots directives "none" and "follow"'],
        ];

        yield 'two conflicts in one directive list' => [
            new Robots(['index', 'noindex', 'follow', 'nofollow']),
            [
                'Conflicting robots directives "index" and "noindex"',
                'Conflicting robots directives "follow" and "nofollow"',
            ],
        ];

        yield 'googlebot directives are checked separately' => [
            (new Robots(['index']))->withGoogleBot('index', 'noindex'),
            ['Conflicting googlebot directives "index" and "noindex"'],
        ];

        yield 'compatible directives' => [
            (new Robots(['index', 'follow', 'noarchive']))->withGoogleBot('noarchive'),
            [],
        ];
    }

    public function detectsDuplicateCustomTagsOncePerKey(): void
    {
        $result = $this->fixture->validate(new ResolvedMetadata(other: [
            MetaTag::name('rating', 'general'),
            MetaTag::name('rating', 'mature'),
            MetaTag::name('rating', 'general'),
            MetaTag::property('fb:app_id', '1'),
            MetaTag::property('fb:app_id', '2'),
            MetaTag::name('fb:app_id', '1'),
        ]));

        $duplicates = array_values(array_map(
            static fn(MetadataIssue $issue): string => $issue->getMessage(),
            array_filter(
                $result->getIssues(),
                static fn(MetadataIssue $issue): bool => $issue->getCode() === 'other.duplicate',
            ),
        ));

        Assert::same($duplicates, [
            'Duplicate custom meta tag name="rating"',
            'Duplicate custom meta tag property="fb:app_id"',
        ]);
    }

    private function completeMetadata(): ResolvedMetadata
    {
        return new ResolvedMetadata(
            metadataBase: 'https://example.com',
            title: 'A perfectly reasonable page title',
            description: str_repeat('A perfectly reasonable meta description. ', 3),
            robots: (new Robots(['index', 'follow']))->withGoogleBot('noarchive'),
            alternates: new Alternates(canonical: '/page', languages: ['en-US' => '/en/page']),
            openGraph: new OpenGraph(
                url: '/page',
                images: [new OgImage(url: '/og.jpg', width: 1200, height: 630, alt: 'Cover')],
            ),
            twitter: new TwitterCard(images: ['/og.jpg']),
            other: [MetaTag::name('rating', 'general')],
        );
    }

    /** @return list<string> */
    private function codes(MetadataValidationResult $result): array
    {
        return array_map(static fn(MetadataIssue $issue): string => $issue->getCode(), $result->getIssues());
    }
}
