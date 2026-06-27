<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\Alternates;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(Alternates::class)]
final class AlternatesTest
{
    public function gettersReturnValues(): void
    {
        $alternates = new Alternates(
            canonical: '/page',
            languages: ['en' => '/en', 'en-US' => '/us', 'x-default' => '/'],
        );

        Assert::same($alternates->getCanonical(), '/page');
        Assert::same($alternates->getLanguages(), ['en' => '/en', 'en-US' => '/us', 'x-default' => '/']);
    }

    public function defaultsAreEmpty(): void
    {
        $alternates = new Alternates();

        Assert::null($alternates->getCanonical());
        Assert::same($alternates->getLanguages(), []);
    }

    public function throwsOnEmptyCanonical(): void
    {
        try {
            new Alternates(canonical: '');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Canonical URL must not be empty');
        }
    }

    #[DataProvider('invalidLocaleProvider')]
    public function throwsOnInvalidLocale(string $locale): void
    {
        try {
            new Alternates(languages: [$locale => '/x']);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains("Invalid hreflang locale \"{$locale}\"");
        }
    }

    public function throwsOnEmptyLanguageUrl(): void
    {
        try {
            new Alternates(languages: ['en' => '']);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Alternate URL for "en" must not be empty');
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidLocaleProvider(): iterable
    {
        yield 'uppercase language' => ['EN'];
        yield 'wrong region case' => ['en-us'];
        yield 'unknown keyword' => ['default'];
    }
}
