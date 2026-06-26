<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\Icon;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Icon::class)]
final class IconTest
{
    public function gettersReturnValues(): void
    {
        $icon = new Icon(rel: 'icon', url: '/favicon.ico', sizes: '32x32', type: 'image/x-icon');

        Assert::same($icon->getRel(), 'icon');
        Assert::same($icon->getUrl(), '/favicon.ico');
        Assert::same($icon->getSizes(), '32x32');
        Assert::same($icon->getType(), 'image/x-icon');
    }

    public function optionalFieldsDefaultToNull(): void
    {
        $icon = new Icon(rel: 'icon', url: '/favicon.ico');

        Assert::null($icon->getSizes());
        Assert::null($icon->getType());
    }

    public function throwsOnEmptyRel(): void
    {
        try {
            new Icon(rel: '', url: '/favicon.ico');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Icon rel must not be empty');
        }
    }

    public function throwsOnEmptyUrl(): void
    {
        try {
            new Icon(rel: 'icon', url: '');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Icon URL must not be empty');
        }
    }
}
