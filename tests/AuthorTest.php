<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Seo\Author;

#[CoversClass(Author::class)]
final class AuthorTest extends TestCase
{
    #[Test]
    public function gettersReturnValues(): void
    {
        $author = new Author(name: 'Alice', url: 'https://example.com/alice');

        $this->assertSame('Alice', $author->getName());
        $this->assertSame('https://example.com/alice', $author->getUrl());
    }

    #[Test]
    public function urlIsOptional(): void
    {
        $this->assertNull((new Author(name: 'Alice'))->getUrl());
    }

    #[Test]
    public function throwsOnEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Author name must not be empty');

        new Author(name: '');
    }

    #[Test]
    public function throwsOnInvalidUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid author URL "bad"');

        new Author(name: 'Alice', url: 'bad');
    }
}
