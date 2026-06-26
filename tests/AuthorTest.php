<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\Author;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Author::class)]
final class AuthorTest
{
    public function gettersReturnValues(): void
    {
        $author = new Author(name: 'Alice', url: 'https://example.com/alice');

        Assert::same($author->getName(), 'Alice');
        Assert::same($author->getUrl(), 'https://example.com/alice');
    }

    public function urlIsOptional(): void
    {
        Assert::null((new Author(name: 'Alice'))->getUrl());
    }

    public function throwsOnEmptyName(): void
    {
        try {
            new Author(name: '');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Author name must not be empty');
        }
    }

    public function throwsOnInvalidUrl(): void
    {
        try {
            new Author(name: 'Alice', url: 'bad');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Invalid author URL "bad"');
        }
    }
}
