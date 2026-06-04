<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Seo\Verification;

#[CoversClass(Verification::class)]
final class VerificationTest extends TestCase
{
    #[Test]
    public function gettersReturnValues(): void
    {
        $verification = new Verification(
            google: 'g-token',
            yandex: 'y-token',
            bing: 'b-token',
            other: ['custom-verification' => 'c-token'],
        );

        $this->assertSame('g-token', $verification->getGoogle());
        $this->assertSame('y-token', $verification->getYandex());
        $this->assertSame('b-token', $verification->getBing());
        $this->assertSame(['custom-verification' => 'c-token'], $verification->getOther());
    }

    #[Test]
    public function defaultsAreEmpty(): void
    {
        $verification = new Verification();

        $this->assertNull($verification->getGoogle());
        $this->assertNull($verification->getYandex());
        $this->assertNull($verification->getBing());
        $this->assertSame([], $verification->getOther());
    }

    #[Test]
    public function throwsOnEmptyOtherName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Verification meta name must not be empty');

        new Verification(other: ['' => 'token']);
    }
}
