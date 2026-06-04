<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Seo\Title;

#[CoversClass(Title::class)]
final class TitleTest extends TestCase
{
    #[Test]
    public function ofIsAppliedToTemplate(): void
    {
        $title = Title::of('Home');

        $this->assertSame('Home', $title->getValue());
        $this->assertFalse($title->isAbsolute());
        $this->assertNull($title->getTemplate());
        $this->assertNull($title->getDefault());
    }

    #[Test]
    public function absoluteBypassesTemplate(): void
    {
        $title = Title::absolute('Exact');

        $this->assertSame('Exact', $title->getValue());
        $this->assertTrue($title->isAbsolute());
    }

    #[Test]
    public function templateCarriesTemplateAndDefault(): void
    {
        $title = Title::template('%s | Acme', default: 'Acme');

        $this->assertSame('%s | Acme', $title->getTemplate());
        $this->assertSame('Acme', $title->getDefault());
        $this->assertNull($title->getValue());
        $this->assertFalse($title->isAbsolute());
    }

    #[Test]
    public function templateDefaultIsNullable(): void
    {
        $this->assertNull(Title::template('%s | Acme')->getDefault());
    }

    #[Test]
    public function throwsWhenTemplateHasNoPlaceholder(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Title template must contain "%s", got "Acme"');

        Title::template('Acme');
    }
}
