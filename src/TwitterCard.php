<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

/**
 * Every field is nullable so a page object can inherit unset fields from the
 * site-wide defaults. `title`/`description`/`images` additionally fall back to
 * the resolved OpenGraph values, and `card` defaults to `summary_large_image`
 * at render time. Image URLs may be relative (resolved against `metadataBase`).
 *
 * @api
 */
final readonly class TwitterCard
{
    private const array VALID_CARDS = ['summary', 'summary_large_image', 'app', 'player'];

    /** @var list<string> */
    private array $images;

    /** @param list<string> $images */
    public function __construct(
        private ?string $card = null,
        private ?string $site = null,
        private ?string $creator = null,
        private ?string $title = null,
        private ?string $description = null,
        array $images = [],
    ) {
        if ($card !== null && !in_array($card, self::VALID_CARDS, strict: true)) {
            throw new InvalidArgumentException("Invalid Twitter card \"{$card}\"");
        }

        foreach ($images as $image) {
            if ($image === '') {
                throw new InvalidArgumentException('Twitter image URL must not be empty');
            }
        }

        $this->images = $images;
    }

    public function getCard(): ?string
    {
        return $this->card;
    }

    public function getSite(): ?string
    {
        return $this->site;
    }

    public function getCreator(): ?string
    {
        return $this->creator;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /** @return list<string> */
    public function getImages(): array
    {
        return $this->images;
    }
}
