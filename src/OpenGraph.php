<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use InvalidArgumentException;

/**
 * Every field is nullable so a page object can inherit unset fields from the
 * site-wide defaults. `title`/`description` additionally fall back to
 * `Metadata::$title`/`$description`, and `type` defaults to `website` at render
 * time. `url` may be relative (resolved against `metadataBase` at render time).
 *
 * @api
 */
final readonly class OpenGraph
{
    /** @var list<OgImage> */
    private array $images;

    /** @param list<OgImage> $images */
    public function __construct(
        private ?string $title = null,
        private ?string $description = null,
        private ?string $type = null,
        private ?string $url = null,
        private ?string $siteName = null,
        private ?string $locale = null,
        array $images = [],
    ) {
        if ($type === '') {
            throw new InvalidArgumentException('OpenGraph type must not be empty');
        }

        if ($url === '') {
            throw new InvalidArgumentException('OpenGraph URL must not be empty');
        }

        $this->images = $images;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getSiteName(): ?string
    {
        return $this->siteName;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /** @return list<OgImage> */
    public function getImages(): array
    {
        return $this->images;
    }
}
