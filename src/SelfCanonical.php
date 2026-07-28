<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

/**
 * Opt-in self-canonical policy: derive `<link rel="canonical">` from the
 * configured `metadataBase` plus the current request path when a page does not
 * declare {@see Alternates::getCanonical()} itself.
 *
 * The request authority is never used, so a request arriving on an alternative
 * host, port or scheme still produces the canonical URL of the configured site.
 * Query parameters are dropped unless explicitly allow-listed; allow-listed
 * parameters are emitted in the configured order, so the same page always
 * yields the same canonical URL regardless of how the query was ordered.
 *
 * @api
 */
final readonly class SelfCanonical
{
    /** @var list<string> */
    private array $queryParameters;

    /** @param array<array-key, string> $queryParameters query parameters that are part of the canonical identity */
    public function __construct(array $queryParameters = [])
    {
        $names = [];

        foreach ($queryParameters as $name) {
            if ($name === '') {
                throw new InvalidArgumentException('Canonical query parameter name must not be empty');
            }

            $names[] = $name;
        }

        $this->queryParameters = $names;
    }

    /**
     * Canonical URLs carry the request path only; every query parameter is
     * dropped.
     */
    public static function enabled(): self
    {
        return new self();
    }

    /**
     * Canonical URLs keep the listed query parameters, in the listed order.
     * Everything else is dropped. Only scalar string values are kept, so
     * array-valued parameters such as `?tag[]=a` never enter a canonical URL.
     */
    public static function keepingQuery(string ...$queryParameters): self
    {
        return new self($queryParameters);
    }

    /** @return list<string> */
    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }
}
