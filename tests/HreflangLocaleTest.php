<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\HreflangLocale;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(HreflangLocale::class)]
final class HreflangLocaleTest
{
    #[DataProvider('validLocaleProvider')]
    public function acceptsValidLocales(string $locale): void
    {
        HreflangLocale::assert($locale);

        Assert::true(true);
    }

    #[DataProvider('invalidLocaleProvider')]
    public function rejectsInvalidLocales(string $locale): void
    {
        try {
            HreflangLocale::assert($locale);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains("Invalid hreflang locale \"{$locale}\"");
        }
    }

    /** @return iterable<string, array{string}> */
    public static function validLocaleProvider(): iterable
    {
        yield 'language' => ['de'];
        yield 'language and region' => ['de-DE'];
        yield 'x-default' => ['x-default'];
    }

    /** @return iterable<string, array{string}> */
    public static function invalidLocaleProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'uppercase language' => ['DE'];
        yield 'lowercase region' => ['de-de'];
        yield 'script subtag' => ['zh-Hant'];
        yield 'trailing newline' => ["de\n"];
    }
}
