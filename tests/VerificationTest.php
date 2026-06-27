<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\Verification;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Verification::class)]
final class VerificationTest
{
    public function gettersReturnValues(): void
    {
        $verification = new Verification(
            google: 'g-token',
            yandex: 'y-token',
            bing: 'b-token',
            other: ['custom-verification' => 'c-token'],
        );

        Assert::same($verification->getGoogle(), 'g-token');
        Assert::same($verification->getYandex(), 'y-token');
        Assert::same($verification->getBing(), 'b-token');
        Assert::same($verification->getOther(), ['custom-verification' => 'c-token']);
    }

    public function defaultsAreEmpty(): void
    {
        $verification = new Verification();

        Assert::null($verification->getGoogle());
        Assert::null($verification->getYandex());
        Assert::null($verification->getBing());
        Assert::same($verification->getOther(), []);
    }

    public function throwsOnEmptyOtherName(): void
    {
        try {
            new Verification(other: ['' => 'token']);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Verification meta name must not be empty');
        }
    }
}
