<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Seo\Icon;
use Rasuvaeff\Yii3Seo\Icons;

#[CoversClass(Icons::class)]
final class IconsTest extends TestCase
{
    #[Test]
    public function shortcutsMapToStandardRels(): void
    {
        $icons = new Icons(icon: '/favicon.ico', shortcut: '/shortcut.ico', apple: '/apple.png');

        $rels = array_map(static fn(Icon $icon): string => $icon->getRel(), $icons->all());
        $urls = array_map(static fn(Icon $icon): string => $icon->getUrl(), $icons->all());

        $this->assertSame(['icon', 'shortcut icon', 'apple-touch-icon'], $rels);
        $this->assertSame(['/favicon.ico', '/shortcut.ico', '/apple.png'], $urls);
    }

    #[Test]
    public function emptyByDefault(): void
    {
        $this->assertSame([], (new Icons())->all());
    }

    #[Test]
    public function otherIconsAreAppended(): void
    {
        $mask = new Icon(rel: 'mask-icon', url: '/safari.svg');
        $icons = new Icons(icon: '/favicon.ico', other: [$mask]);

        $all = $icons->all();

        $this->assertCount(2, $all);
        $this->assertSame('mask-icon', $all[1]->getRel());
    }
}
