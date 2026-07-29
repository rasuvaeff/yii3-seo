<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\SelfCanonical;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(SelfCanonical::class)]
final class SelfCanonicalTest
{
    public function enabledDropsEveryQueryParameter(): void
    {
        Assert::same(SelfCanonical::enabled()->getQueryParameters(), []);
    }

    public function keepingQueryPreservesTheConfiguredOrder(): void
    {
        Assert::same(SelfCanonical::keepingQuery('page', 'sort')->getQueryParameters(), ['page', 'sort']);
    }

    public function constructorAcceptsAList(): void
    {
        Assert::same((new SelfCanonical(['page']))->getQueryParameters(), ['page']);
    }

    public function defaultsToNoQueryParameters(): void
    {
        Assert::same((new SelfCanonical())->getQueryParameters(), []);
    }

    public function throwsOnEmptyQueryParameterName(): void
    {
        try {
            new SelfCanonical(['page', '']);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Canonical query parameter name must not be empty');
        }
    }
}
