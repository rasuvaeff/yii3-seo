<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Benchmarks;

use Rasuvaeff\Yii3Seo\Metadata;
use Rasuvaeff\Yii3Seo\OpenGraph;
use Rasuvaeff\Yii3Seo\Robots;
use Rasuvaeff\Yii3Seo\TwitterCard;
use Testo\Bench;

final class MetadataBench
{
    #[Bench(
        callables: [
            'full' => [self::class, 'constructFull'],
        ],
        calls: 1_000,
        iterations: 10,
    )]
    public static function constructMinimal(): Metadata
    {
        return new Metadata(title: 'Home');
    }

    public static function constructFull(): Metadata
    {
        return new Metadata(
            title: 'Home — My Site',
            description: 'Welcome to my site',
            keywords: ['php', 'yii3', 'framework'],
            applicationName: 'My Site',
            themeColor: '#ffffff',
            robots: Robots::index(),
            openGraph: new OpenGraph(title: 'Home', description: 'Welcome', siteName: 'My Site'),
            twitter: new TwitterCard(title: 'Home', description: 'Welcome'),
        );
    }
}
