<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Seo\TwitterCard;

#[CoversClass(TwitterCard::class)]
final class TwitterCardTest extends TestCase
{
    #[Test]
    public function gettersReturnValues(): void
    {
        $card = new TwitterCard(
            card: 'summary',
            site: '@site',
            creator: '@creator',
            title: 'T',
            description: 'D',
            images: ['/a.jpg', '/b.jpg'],
        );

        $this->assertSame('summary', $card->getCard());
        $this->assertSame('@site', $card->getSite());
        $this->assertSame('@creator', $card->getCreator());
        $this->assertSame('T', $card->getTitle());
        $this->assertSame('D', $card->getDescription());
        $this->assertSame(['/a.jpg', '/b.jpg'], $card->getImages());
    }

    #[Test]
    public function cardIsNullByDefaultSoItCanInherit(): void
    {
        $this->assertNull((new TwitterCard())->getCard());
    }

    #[Test]
    #[DataProvider('validCardProvider')]
    public function acceptsValidCards(string $card): void
    {
        $this->assertSame($card, (new TwitterCard(card: $card))->getCard());
    }

    #[Test]
    public function throwsOnInvalidCard(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Twitter card "carousel"');

        new TwitterCard(card: 'carousel');
    }

    #[Test]
    public function throwsOnEmptyImageUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Twitter image URL must not be empty');

        new TwitterCard(images: ['']);
    }

    /** @return iterable<string, array{string}> */
    public static function validCardProvider(): iterable
    {
        yield 'summary' => ['summary'];
        yield 'summary_large_image' => ['summary_large_image'];
        yield 'app' => ['app'];
        yield 'player' => ['player'];
    }
}
