<?php

declare(strict_types=1);

return [
    'rasuvaeff/yii3-seo' => [
        // Set to a Rasuvaeff\Yii3Seo\MetadataDefaults instance to apply site-wide defaults.
        'defaults' => null,
        'sitemap' => [
            // URL path the exported sitemap files are served under.
            'publicPath' => '/',
        ],
        'robotsTxt' => [
            // Set to false on staging and preview environments: every crawler is then told
            // "Disallow: /" and no sitemap is advertised. The package never inspects the
            // environment itself — the application decides.
            'indexable' => true,
            // Set to a Rasuvaeff\Yii3Seo\RobotsTxt instance to serve a custom file.
            'robots' => null,
        ],
    ],
];
