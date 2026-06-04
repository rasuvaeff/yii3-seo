<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

/**
 * Convenience wrapper around a list of {@see Icon} `<link>` tags. The `icon`,
 * `shortcut` and `apple` shortcuts map to the standard rels; `other` accepts
 * arbitrary {@see Icon} instances (e.g. `mask-icon`).
 *
 * @api
 */
final readonly class Icons
{
    /** @var list<Icon> */
    private array $icons;

    /** @param list<Icon> $other */
    public function __construct(
        ?string $icon = null,
        ?string $shortcut = null,
        ?string $apple = null,
        array $other = [],
    ) {
        $icons = [];

        if ($icon !== null) {
            $icons[] = new Icon(rel: 'icon', url: $icon);
        }

        if ($shortcut !== null) {
            $icons[] = new Icon(rel: 'shortcut icon', url: $shortcut);
        }

        if ($apple !== null) {
            $icons[] = new Icon(rel: 'apple-touch-icon', url: $apple);
        }

        foreach ($other as $custom) {
            $icons[] = $custom;
        }

        $this->icons = $icons;
    }

    /** @return list<Icon> */
    public function all(): array
    {
        return $this->icons;
    }
}
