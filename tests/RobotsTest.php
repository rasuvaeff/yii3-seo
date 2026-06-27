<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\Robots;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(Robots::class)]
final class RobotsTest
{
    public function factoriesProduceExpectedDirectives(): void
    {
        Assert::same(Robots::index()->getDirectives(), ['index', 'follow']);
        Assert::same(Robots::noindex()->getDirectives(), ['noindex']);
        Assert::same(Robots::nofollow()->getDirectives(), ['nofollow']);
        Assert::same(Robots::none()->getDirectives(), ['noindex', 'nofollow']);
        Assert::same(Robots::noarchive()->getDirectives(), ['noarchive']);
    }

    public function customDirectivesAreAccepted(): void
    {
        Assert::same((new Robots(['noindex', 'nosnippet']))->getDirectives(), ['noindex', 'nosnippet']);
    }

    public function throwsOnInvalidDirective(): void
    {
        try {
            new Robots(['spider']);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Invalid robots directive "spider"');
        }
    }

    #[DataProvider('validMaxDirectiveProvider')]
    public function acceptsValidMaxDirectives(string $directive): void
    {
        Assert::same((new Robots([$directive]))->getDirectives(), [$directive]);
    }

    #[DataProvider('anchoredJunkProvider')]
    public function rejectsDirectivesWithLeadingOrTrailingJunk(string $directive): void
    {
        try {
            new Robots([$directive]);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains("Invalid robots directive \"{$directive}\"");
        }
    }

    public function withModifiersAppendGoogleDirectives(): void
    {
        $robots = Robots::index()
            ->withNoSnippet()
            ->withNoImageIndex()
            ->withMaxSnippet(-1)
            ->withMaxImagePreview('large')
            ->withMaxVideoPreview(30);

        Assert::same(
            $robots->getDirectives(),
            ['index', 'follow', 'nosnippet', 'noimageindex', 'max-snippet:-1', 'max-image-preview:large', 'max-video-preview:30'],
        );
    }

    public function withModifiersDoNotMutateOriginal(): void
    {
        $base = Robots::index();
        $derived = $base->withMaxSnippet(10);

        Assert::same($base->getDirectives(), ['index', 'follow']);
        Assert::notSame($base, $derived);
    }

    public function withGoogleBotDoesNotMutateOriginal(): void
    {
        $base = Robots::index();
        $derived = $base->withGoogleBot('noindex');

        Assert::same($base->getGoogleBotDirectives(), []);
        Assert::same($derived->getDirectives(), ['index', 'follow']);
        Assert::same(Robots::index()->withGoogleBot('noindex', 'max-snippet:50')->getGoogleBotDirectives(), ['noindex', 'max-snippet:50']);
        Assert::notSame($base, $derived);
    }

    public function googleBotIsEmptyByDefault(): void
    {
        Assert::same(Robots::noindex()->getGoogleBotDirectives(), []);
    }

    public function throwsOnInvalidImagePreview(): void
    {
        try {
            Robots::index()->withMaxImagePreview('huge');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Invalid max-image-preview "huge"');
        }
    }

    public function throwsOnInvalidGoogleBotDirective(): void
    {
        try {
            Robots::index()->withGoogleBot('spider');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Invalid robots directive "spider"');
        }
    }

    /** @return iterable<string, array{string}> */
    public static function validMaxDirectiveProvider(): iterable
    {
        yield 'max-snippet' => ['max-snippet:-1'];
        yield 'max-image-preview' => ['max-image-preview:large'];
        yield 'max-video-preview' => ['max-video-preview:30'];
    }

    /** @return iterable<string, array{string}> */
    public static function anchoredJunkProvider(): iterable
    {
        yield 'snippet leading junk' => ['xmax-snippet:5'];
        yield 'snippet trailing junk' => ['max-snippet:5x'];
        yield 'image-preview leading junk' => ['xmax-image-preview:large'];
        yield 'image-preview trailing junk' => ['max-image-preview:largex'];
        yield 'video-preview leading junk' => ['xmax-video-preview:5'];
        yield 'video-preview trailing junk' => ['max-video-preview:5x'];
    }
}
