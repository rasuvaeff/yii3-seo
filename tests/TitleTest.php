<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\Title;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Title::class)]
final class TitleTest
{
    public function ofIsAppliedToTemplate(): void
    {
        $title = Title::of('Home');

        Assert::same($title->getValue(), 'Home');
        Assert::false($title->isAbsolute());
        Assert::null($title->getTemplate());
        Assert::null($title->getDefault());
    }

    public function absoluteBypassesTemplate(): void
    {
        $title = Title::absolute('Exact');

        Assert::same($title->getValue(), 'Exact');
        Assert::true($title->isAbsolute());
    }

    public function templateCarriesTemplateAndDefault(): void
    {
        $title = Title::template('%s | Acme', default: 'Acme');

        Assert::same($title->getTemplate(), '%s | Acme');
        Assert::same($title->getDefault(), 'Acme');
        Assert::null($title->getValue());
        Assert::false($title->isAbsolute());
    }

    public function templateDefaultIsNullable(): void
    {
        Assert::null(Title::template('%s | Acme')->getDefault());
    }

    public function throwsWhenTemplateHasNoPlaceholder(): void
    {
        try {
            Title::template('Acme');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Title template must contain "%s", got "Acme"');
        }
    }
}
