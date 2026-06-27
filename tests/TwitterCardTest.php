<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\TwitterCard;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(TwitterCard::class)]
final class TwitterCardTest
{
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

        Assert::same($card->getCard(), 'summary');
        Assert::same($card->getSite(), '@site');
        Assert::same($card->getCreator(), '@creator');
        Assert::same($card->getTitle(), 'T');
        Assert::same($card->getDescription(), 'D');
        Assert::same($card->getImages(), ['/a.jpg', '/b.jpg']);
    }

    public function cardIsNullByDefaultSoItCanInherit(): void
    {
        Assert::null((new TwitterCard())->getCard());
    }

    #[DataProvider('validCardProvider')]
    public function acceptsValidCards(string $card): void
    {
        Assert::same((new TwitterCard(card: $card))->getCard(), $card);
    }

    public function throwsOnInvalidCard(): void
    {
        try {
            new TwitterCard(card: 'carousel');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Invalid Twitter card "carousel"');
        }
    }

    public function throwsOnEmptyImageUrl(): void
    {
        try {
            new TwitterCard(images: ['']);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Twitter image URL must not be empty');
        }
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
