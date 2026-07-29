<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use Rasuvaeff\Yii3Seo\ChangeFrequency;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(ChangeFrequency::class)]
final class ChangeFrequencyTest
{
    public function coversTheSevenProtocolValues(): void
    {
        Assert::same(
            array_map(static fn(ChangeFrequency $f): string => $f->value, ChangeFrequency::cases()),
            ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'],
        );
    }

    public function isBackedByTheProtocolSpelling(): void
    {
        Assert::same(ChangeFrequency::from('weekly'), ChangeFrequency::Weekly);
    }
}
