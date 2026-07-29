<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\RobotsTxtGroup;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(RobotsTxtGroup::class)]
final class RobotsTxtGroupTest
{
    public function exposesEveryField(): void
    {
        $group = new RobotsTxtGroup(
            userAgents: ['Googlebot', 'Bingbot'],
            allow: ['/public/'],
            disallow: ['/admin/', '*.json'],
            crawlDelay: 10,
        );

        Assert::same($group->getUserAgents(), ['Googlebot', 'Bingbot']);
        Assert::same($group->getAllow(), ['/public/']);
        Assert::same($group->getDisallow(), ['/admin/', '*.json']);
        Assert::same($group->getCrawlDelay(), 10);
    }

    public function defaultsToNoRulesAtAll(): void
    {
        $group = new RobotsTxtGroup(['*']);

        Assert::same($group->getAllow(), []);
        Assert::same($group->getDisallow(), []);
        Assert::null($group->getCrawlDelay());
    }

    public function reindexesEveryListToALiteralList(): void
    {
        $group = new RobotsTxtGroup(
            userAgents: [3 => '*'],
            allow: [7 => '/a'],
            disallow: [9 => '/b'],
        );

        Assert::same($group->getUserAgents(), ['*']);
        Assert::same($group->getAllow(), ['/a']);
        Assert::same($group->getDisallow(), ['/b']);
    }

    public function disallowAllBlocksEveryCrawler(): void
    {
        $group = RobotsTxtGroup::disallowAll();

        Assert::same($group->getUserAgents(), ['*']);
        Assert::same($group->getDisallow(), ['/']);
    }

    public function allowAllRestrictsNothing(): void
    {
        $group = RobotsTxtGroup::allowAll();

        Assert::same($group->getUserAgents(), ['*']);
        Assert::same($group->getDisallow(), []);
        Assert::same($group->getAllow(), []);
    }

    public function throwsWithoutAUserAgent(): void
    {
        try {
            new RobotsTxtGroup([]);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('needs at least one user agent');
        }
    }

    #[DataProvider('invalidUserAgentProvider')]
    public function throwsOnAUserAgentThatCouldForgeALine(string $userAgent): void
    {
        try {
            new RobotsTxtGroup([$userAgent]);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Invalid robots.txt user agent');
        }
    }

    #[DataProvider('injectingPathProvider')]
    public function throwsOnAPathThatCouldForgeALine(string $path): void
    {
        try {
            new RobotsTxtGroup(['*'], disallow: [$path]);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Invalid robots.txt path');
        }
    }

    public function validatesAllowPathsAsWell(): void
    {
        try {
            new RobotsTxtGroup(['*'], allow: ["/a\nDisallow: /"]);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Invalid robots.txt path');
        }
    }

    #[DataProvider('unanchoredPathProvider')]
    public function throwsOnAPathThatIsNotAnchored(string $path): void
    {
        try {
            new RobotsTxtGroup(['*'], disallow: [$path]);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('must start with "/" or "*"');
        }
    }

    public function throwsOnANegativeCrawlDelay(): void
    {
        try {
            new RobotsTxtGroup(['*'], crawlDelay: -1);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Invalid robots.txt crawl delay "-1"');
        }
    }

    public function acceptsAZeroCrawlDelay(): void
    {
        Assert::same((new RobotsTxtGroup(['*'], crawlDelay: 0))->getCrawlDelay(), 0);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidUserAgentProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'newline' => ["Googlebot\nDisallow: /"];
        yield 'carriage return' => ["Googlebot\rDisallow: /"];
        yield 'null byte' => ["Google\0bot"];
    }

    /** @return iterable<string, array{string}> */
    public static function injectingPathProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'newline' => ["/a\nDisallow: /"];
        yield 'carriage return' => ["/a\rDisallow: /"];
        yield 'delete' => ["/a\x7F"];
    }

    /** @return iterable<string, array{string}> */
    public static function unanchoredPathProvider(): iterable
    {
        yield 'bare word' => ['admin'];
        yield 'scheme' => ['https://example.com/admin'];
    }
}
