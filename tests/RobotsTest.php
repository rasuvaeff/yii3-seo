<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Seo\Robots;

#[CoversClass(Robots::class)]
final class RobotsTest extends TestCase
{
    #[Test]
    public function factoriesProduceExpectedDirectives(): void
    {
        $this->assertSame(['index', 'follow'], Robots::index()->getDirectives());
        $this->assertSame(['noindex'], Robots::noindex()->getDirectives());
        $this->assertSame(['nofollow'], Robots::nofollow()->getDirectives());
        $this->assertSame(['noindex', 'nofollow'], Robots::none()->getDirectives());
        $this->assertSame(['noarchive'], Robots::noarchive()->getDirectives());
    }

    #[Test]
    public function customDirectivesAreAccepted(): void
    {
        $this->assertSame(['noindex', 'nosnippet'], (new Robots(['noindex', 'nosnippet']))->getDirectives());
    }

    #[Test]
    public function throwsOnInvalidDirective(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid robots directive "spider"');

        new Robots(['spider']);
    }

    #[Test]
    #[DataProvider('validMaxDirectiveProvider')]
    public function acceptsValidMaxDirectives(string $directive): void
    {
        $this->assertSame([$directive], (new Robots([$directive]))->getDirectives());
    }

    #[Test]
    #[DataProvider('anchoredJunkProvider')]
    public function rejectsDirectivesWithLeadingOrTrailingJunk(string $directive): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid robots directive \"{$directive}\"");

        new Robots([$directive]);
    }

    #[Test]
    public function withModifiersAppendGoogleDirectives(): void
    {
        $robots = Robots::index()
            ->withNoSnippet()
            ->withNoImageIndex()
            ->withMaxSnippet(-1)
            ->withMaxImagePreview('large')
            ->withMaxVideoPreview(30);

        $this->assertSame(
            ['index', 'follow', 'nosnippet', 'noimageindex', 'max-snippet:-1', 'max-image-preview:large', 'max-video-preview:30'],
            $robots->getDirectives(),
        );
    }

    #[Test]
    public function withModifiersDoNotMutateOriginal(): void
    {
        $base = Robots::index();
        $derived = $base->withMaxSnippet(10);

        $this->assertSame(['index', 'follow'], $base->getDirectives());
        $this->assertNotSame($base, $derived);
    }

    #[Test]
    public function withGoogleBotDoesNotMutateOriginal(): void
    {
        $base = Robots::index();
        $derived = $base->withGoogleBot('noindex');

        $this->assertSame([], $base->getGoogleBotDirectives());
        $this->assertSame(['index', 'follow'], $derived->getDirectives());
        $this->assertSame(['noindex', 'max-snippet:50'], Robots::index()->withGoogleBot('noindex', 'max-snippet:50')->getGoogleBotDirectives());
        $this->assertNotSame($base, $derived);
    }

    #[Test]
    public function googleBotIsEmptyByDefault(): void
    {
        $this->assertSame([], Robots::noindex()->getGoogleBotDirectives());
    }

    #[Test]
    public function throwsOnInvalidImagePreview(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid max-image-preview "huge"');

        Robots::index()->withMaxImagePreview('huge');
    }

    #[Test]
    public function throwsOnInvalidGoogleBotDirective(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid robots directive "spider"');

        Robots::index()->withGoogleBot('spider');
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
