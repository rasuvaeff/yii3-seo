<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Seo\UrlResolver;

#[CoversClass(UrlResolver::class)]
final class UrlResolverTest extends TestCase
{
    #[Test]
    public function absoluteUrlIsReturnedUnchanged(): void
    {
        $resolver = new UrlResolver('https://example.com');

        $this->assertSame('https://other.com/x', $resolver->resolve('https://other.com/x'));
    }

    #[Test]
    public function relativeUrlIsJoinedToBase(): void
    {
        $resolver = new UrlResolver('https://example.com');

        $this->assertSame('https://example.com/products/1', $resolver->resolve('/products/1'));
    }

    #[Test]
    public function joinNormalisesSlashes(): void
    {
        $resolver = new UrlResolver('https://example.com/');

        $this->assertSame('https://example.com/products/1', $resolver->resolve('products/1'));
    }

    #[Test]
    public function throwsOnRelativeUrlWithoutBase(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Relative URL "/x" requires a metadataBase to be configured');

        ((new UrlResolver(null)))->resolve('/x');
    }

    #[DataProvider('invalidUrlProvider')]
    #[Test]
    public function throwsOnInvalidUrl(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid URL \"{$url}\"");

        (new UrlResolver('https://example.com'))->resolve($url);
    }

    /** @return iterable<string, array{0: string}> */
    public static function invalidUrlProvider(): iterable
    {
        yield 'absolute url with whitespace' => ['https://exa mple.com/x'];
        yield 'network path reference' => ['//cdn.example.com/x'];
        yield 'broken scheme-like url' => ['https:example.com'];
        yield 'empty string' => [''];
    }
}
