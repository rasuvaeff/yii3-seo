<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

/**
 * Resolves crawler-facing URLs against an optional `metadataBase`. Absolute URLs
 * are returned unchanged; relative URLs are joined to the base, and throw when
 * no base is configured.
 *
 * @internal
 */
final readonly class UrlResolver
{
    public function __construct(private ?string $base) {}

    public function resolve(string $url): string
    {
        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return $url;
        }

        if (!$this->isRelativePath($url)) {
            throw new InvalidArgumentException("Invalid URL \"{$url}\"");
        }

        if ($this->base === null) {
            throw new InvalidArgumentException(
                "Relative URL \"{$url}\" requires a metadataBase to be configured",
            );
        }

        return rtrim($this->base, '/') . '/' . ltrim($url, '/');
    }

    private function isRelativePath(string $url): bool
    {
        if ($url === '' || preg_match('/\s/', $url) === 1) {
            return false;
        }

        if (str_starts_with($url, '//')) {
            return false;
        }

        return preg_match('/^[A-Za-z][A-Za-z0-9+.-]*:/', $url) !== 1;
    }
}
