<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\UrlResolver;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(UrlResolver::class)]
final class UrlResolverTest
{
    public function absoluteUrlIsReturnedUnchanged(): void
    {
        $resolver = new UrlResolver('https://example.com');

        Assert::same($resolver->resolve('https://other.com/x'), 'https://other.com/x');
    }

    public function relativeUrlIsJoinedToBase(): void
    {
        $resolver = new UrlResolver('https://example.com');

        Assert::same($resolver->resolve('/products/1'), 'https://example.com/products/1');
    }

    public function joinNormalisesSlashes(): void
    {
        $resolver = new UrlResolver('https://example.com/');

        Assert::same($resolver->resolve('products/1'), 'https://example.com/products/1');
    }

    public function throwsOnRelativeUrlWithoutBase(): void
    {
        try {
            (new UrlResolver(null))->resolve('/x');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Relative URL "/x" requires a metadataBase to be configured');
        }
    }

    #[DataProvider('invalidUrlProvider')]
    public function throwsOnInvalidUrl(string $url): void
    {
        try {
            (new UrlResolver('https://example.com'))->resolve($url);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains("Invalid URL \"{$url}\"");
        }
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
