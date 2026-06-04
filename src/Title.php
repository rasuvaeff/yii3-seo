<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

/**
 * @api
 */
final readonly class Title
{
    private function __construct(
        private ?string $value,
        private ?string $template,
        private ?string $default,
        private bool $absolute,
    ) {}

    public static function of(string $value): self
    {
        return new self(value: $value, template: null, default: null, absolute: false);
    }

    public static function absolute(string $value): self
    {
        return new self(value: $value, template: null, default: null, absolute: true);
    }

    public static function template(string $template, ?string $default = null): self
    {
        if (!str_contains($template, '%s')) {
            throw new InvalidArgumentException("Title template must contain \"%s\", got \"{$template}\"");
        }

        return new self(value: null, template: $template, default: $default, absolute: false);
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getDefault(): ?string
    {
        return $this->default;
    }

    public function isAbsolute(): bool
    {
        return $this->absolute;
    }
}
