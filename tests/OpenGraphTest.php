<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\OgImage;
use Rasuvaeff\Yii3Seo\OpenGraph;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(OpenGraph::class)]
final class OpenGraphTest
{
    public function gettersReturnValues(): void
    {
        $image = new OgImage(url: '/og.jpg');
        $og = new OpenGraph(
            title: 'T',
            description: 'D',
            type: 'article',
            url: 'https://example.com',
            siteName: 'S',
            locale: 'en_US',
            images: [$image],
        );

        Assert::same($og->getTitle(), 'T');
        Assert::same($og->getDescription(), 'D');
        Assert::same($og->getType(), 'article');
        Assert::same($og->getUrl(), 'https://example.com');
        Assert::same($og->getSiteName(), 'S');
        Assert::same($og->getLocale(), 'en_US');
        Assert::same($og->getImages(), [$image]);
    }

    public function defaultsAreEmpty(): void
    {
        $og = new OpenGraph();

        Assert::null($og->getTitle());
        Assert::null($og->getDescription());
        Assert::null($og->getType());
        Assert::null($og->getUrl());
        Assert::null($og->getSiteName());
        Assert::null($og->getLocale());
        Assert::same($og->getImages(), []);
    }

    public function throwsOnEmptyType(): void
    {
        try {
            new OpenGraph(type: '');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('OpenGraph type must not be empty');
        }
    }

    public function throwsOnEmptyUrl(): void
    {
        try {
            new OpenGraph(url: '');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('OpenGraph URL must not be empty');
        }
    }
}
