<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

/**
 * Search-engine ownership verification meta tags.
 *
 * @api
 */
final readonly class Verification
{
    /** @var array<string, string> */
    private array $other;

    /** @param array<string, string> $other name => content */
    public function __construct(
        private ?string $google = null,
        private ?string $yandex = null,
        private ?string $bing = null,
        array $other = [],
    ) {
        foreach (array_keys($other) as $name) {
            if ($name === '') {
                throw new InvalidArgumentException('Verification meta name must not be empty');
            }
        }

        $this->other = $other;
    }

    public function getGoogle(): ?string
    {
        return $this->google;
    }

    public function getYandex(): ?string
    {
        return $this->yandex;
    }

    public function getBing(): ?string
    {
        return $this->bing;
    }

    /** @return array<string, string> */
    public function getOther(): array
    {
        return $this->other;
    }
}
