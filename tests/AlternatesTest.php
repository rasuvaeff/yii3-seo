<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Seo\Alternates;

#[CoversClass(Alternates::class)]
final class AlternatesTest extends TestCase
{
    #[Test]
    public function gettersReturnValues(): void
    {
        $alternates = new Alternates(
            canonical: '/page',
            languages: ['en' => '/en', 'en-US' => '/us', 'x-default' => '/'],
        );

        $this->assertSame('/page', $alternates->getCanonical());
        $this->assertSame(['en' => '/en', 'en-US' => '/us', 'x-default' => '/'], $alternates->getLanguages());
    }

    #[Test]
    public function defaultsAreEmpty(): void
    {
        $alternates = new Alternates();

        $this->assertNull($alternates->getCanonical());
        $this->assertSame([], $alternates->getLanguages());
    }

    #[Test]
    public function throwsOnEmptyCanonical(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Canonical URL must not be empty');

        new Alternates(canonical: '');
    }

    #[Test]
    #[DataProvider('invalidLocaleProvider')]
    public function throwsOnInvalidLocale(string $locale): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid hreflang locale \"{$locale}\"");

        new Alternates(languages: [$locale => '/x']);
    }

    #[Test]
    public function throwsOnEmptyLanguageUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Alternate URL for "en" must not be empty');

        new Alternates(languages: ['en' => '']);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidLocaleProvider(): iterable
    {
        yield 'uppercase language' => ['EN'];
        yield 'wrong region case' => ['en-us'];
        yield 'unknown keyword' => ['default'];
    }
}
