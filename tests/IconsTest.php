<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use Rasuvaeff\Yii3Seo\Icon;
use Rasuvaeff\Yii3Seo\Icons;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Icons::class)]
final class IconsTest
{
    public function shortcutsMapToStandardRels(): void
    {
        $icons = new Icons(icon: '/favicon.ico', shortcut: '/shortcut.ico', apple: '/apple.png');

        $rels = array_map(static fn(Icon $icon): string => $icon->getRel(), $icons->all());
        $urls = array_map(static fn(Icon $icon): string => $icon->getUrl(), $icons->all());

        Assert::same($rels, ['icon', 'shortcut icon', 'apple-touch-icon']);
        Assert::same($urls, ['/favicon.ico', '/shortcut.ico', '/apple.png']);
    }

    public function emptyByDefault(): void
    {
        Assert::same((new Icons())->all(), []);
    }

    public function otherIconsAreAppended(): void
    {
        $mask = new Icon(rel: 'mask-icon', url: '/safari.svg');
        $icons = new Icons(icon: '/favicon.ico', other: [$mask]);

        $all = $icons->all();

        Assert::count($all, 2);
        Assert::same($all[1]->getRel(), 'mask-icon');
    }
}
