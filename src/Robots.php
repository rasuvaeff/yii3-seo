<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

/**
 * Robots directives rendered as `<meta name="robots">` plus an optional
 * `<meta name="googlebot">`. Supports `max-snippet`, `max-image-preview` and
 * `max-video-preview` Google directives.
 *
 * @api
 */
final class Robots
{
    private const array VALID_DIRECTIVES = [
        'all', 'index', 'noindex', 'follow', 'nofollow', 'none',
        'noarchive', 'nosnippet', 'noimageindex', 'notranslate',
    ];

    private const array VALID_IMAGE_PREVIEW = ['none', 'standard', 'large'];

    /** @var non-empty-list<string> */
    private array $directives;

    /** @var list<string> */
    private array $googleBot = [];

    /** @param non-empty-list<string> $directives */
    public function __construct(array $directives)
    {
        foreach ($directives as $directive) {
            $this->assertValidDirective($directive);
        }

        $this->directives = $directives;
    }

    public static function index(): self
    {
        return new self(['index', 'follow']);
    }

    public static function noindex(): self
    {
        return new self(['noindex']);
    }

    public static function nofollow(): self
    {
        return new self(['nofollow']);
    }

    public static function none(): self
    {
        return new self(['noindex', 'nofollow']);
    }

    public static function noarchive(): self
    {
        return new self(['noarchive']);
    }

    public function withNoSnippet(): self
    {
        return $this->appendDirective('nosnippet');
    }

    public function withNoImageIndex(): self
    {
        return $this->appendDirective('noimageindex');
    }

    public function withMaxSnippet(int $length): self
    {
        return $this->appendDirective("max-snippet:{$length}");
    }

    public function withMaxImagePreview(string $size): self
    {
        if (!in_array($size, self::VALID_IMAGE_PREVIEW, strict: true)) {
            throw new InvalidArgumentException("Invalid max-image-preview \"{$size}\"");
        }

        return $this->appendDirective("max-image-preview:{$size}");
    }

    public function withMaxVideoPreview(int $seconds): self
    {
        return $this->appendDirective("max-video-preview:{$seconds}");
    }

    public function withGoogleBot(string ...$directives): self
    {
        foreach ($directives as $directive) {
            $this->assertValidDirective($directive);
        }

        $clone = clone $this;
        $clone->googleBot = array_values($directives);

        return $clone;
    }

    /** @return non-empty-list<string> */
    public function getDirectives(): array
    {
        return $this->directives;
    }

    /** @return list<string> */
    public function getGoogleBotDirectives(): array
    {
        return $this->googleBot;
    }

    private function appendDirective(string $directive): self
    {
        $clone = clone $this;
        $clone->directives[] = $directive;

        return $clone;
    }

    private function assertValidDirective(string $directive): void
    {
        if (in_array($directive, self::VALID_DIRECTIVES, strict: true)) {
            return;
        }

        if (preg_match('/^max-snippet:-?\d+$/', $directive) === 1) {
            return;
        }

        if (preg_match('/^max-image-preview:(?:none|standard|large)$/', $directive) === 1) {
            return;
        }

        if (preg_match('/^max-video-preview:-?\d+$/', $directive) === 1) {
            return;
        }

        throw new InvalidArgumentException("Invalid robots directive \"{$directive}\"");
    }
}
