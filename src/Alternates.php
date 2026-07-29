<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

/**
 * Canonical URL and `hreflang` alternates. Both `canonical` and the `languages`
 * URLs may be relative (resolved against `metadataBase` at render time).
 *
 * @api
 */
final readonly class Alternates
{
    /** @var array<string, string> */
    private array $languages;

    /** @param array<string, string> $languages locale => url */
    public function __construct(
        private ?string $canonical = null,
        array $languages = [],
    ) {
        if ($canonical === '') {
            throw new InvalidArgumentException('Canonical URL must not be empty');
        }

        foreach ($languages as $locale => $url) {
            HreflangLocale::assert($locale);

            if ($url === '') {
                throw new InvalidArgumentException("Alternate URL for \"{$locale}\" must not be empty");
            }
        }

        $this->languages = $languages;
    }

    public function getCanonical(): ?string
    {
        return $this->canonical;
    }

    /** @return array<string, string> */
    public function getLanguages(): array
    {
        return $this->languages;
    }
}
