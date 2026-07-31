<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

/**
 * One `User-agent` group of a `robots.txt` file.
 *
 * Every value is rejected if it contains a control character, so a crawler name
 * or a path coming from configuration can never inject an extra `Disallow: /`
 * line into the served file. Paths must start with `/` or `*`, as the protocol
 * requires.
 *
 * A group with neither `allow` nor `disallow` entries renders the empty
 * `Disallow:` line, which is the protocol's way of spelling "nothing is
 * restricted".
 *
 * @api
 */
final readonly class RobotsTxtGroup
{
    /** @var non-empty-list<string> */
    private array $userAgents;

    /** @var list<string> */
    private array $allow;

    /** @var list<string> */
    private array $disallow;

    /**
     * @param array<array-key, string> $userAgents crawler names, or `*` for every crawler
     * @param array<array-key, string> $allow paths the group explicitly allows
     * @param array<array-key, string> $disallow paths the group blocks
     * @param int|null $crawlDelay seconds between requests; honoured by Bing and Yandex, ignored by Google
     */
    public function __construct(
        array $userAgents,
        array $allow = [],
        array $disallow = [],
        private ?int $crawlDelay = null,
    ) {
        $userAgents = array_values($userAgents);

        if ($userAgents === []) {
            throw new InvalidArgumentException('A robots.txt group needs at least one user agent');
        }

        foreach ($userAgents as $userAgent) {
            if ($userAgent === '' || $this->hasControlCharacter($userAgent)) {
                throw new InvalidArgumentException("Invalid robots.txt user agent \"{$userAgent}\"");
            }
        }

        foreach ($allow as $path) {
            $this->assertValidPath($path);
        }

        foreach ($disallow as $path) {
            $this->assertValidPath($path);
        }

        if ($crawlDelay !== null && $crawlDelay < 0) {
            throw new InvalidArgumentException("Invalid robots.txt crawl delay \"{$crawlDelay}\"");
        }

        $this->userAgents = $userAgents;
        $this->allow = array_values($allow);
        $this->disallow = array_values($disallow);
    }

    /** Blocks every crawler from the whole site. */
    public static function disallowAll(): self
    {
        return new self(userAgents: ['*'], disallow: ['/']);
    }

    /** Places no restriction on any crawler. */
    public static function allowAll(): self
    {
        return new self(userAgents: ['*']);
    }

    /** @return non-empty-list<string> */
    public function getUserAgents(): array
    {
        return $this->userAgents;
    }

    /** @return list<string> */
    public function getAllow(): array
    {
        return $this->allow;
    }

    /** @return list<string> */
    public function getDisallow(): array
    {
        return $this->disallow;
    }

    public function getCrawlDelay(): ?int
    {
        return $this->crawlDelay;
    }

    private function assertValidPath(string $path): void
    {
        if ($path === '' || $this->hasControlCharacter($path)) {
            throw new InvalidArgumentException("Invalid robots.txt path \"{$path}\"");
        }

        if (!str_starts_with($path, '/') && !str_starts_with($path, '*')) {
            throw new InvalidArgumentException("Robots.txt path \"{$path}\" must start with \"/\" or \"*\"");
        }
    }

    private function hasControlCharacter(string $value): bool
    {
        return preg_match('/[\x00-\x1F\x7F]/', $value) === 1;
    }
}
