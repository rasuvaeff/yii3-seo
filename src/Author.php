<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

/**
 * @api
 */
final readonly class Author
{
    public function __construct(
        private string $name,
        private ?string $url = null,
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('Author name must not be empty');
        }

        if ($url !== null && filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException("Invalid author URL \"{$url}\"");
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
}
