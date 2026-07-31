<?php

declare(strict_types=1);

use Rasuvaeff\Yii3Seo\MetadataDefaults;
use Rasuvaeff\Yii3Seo\RobotsTxt;
use Rasuvaeff\Yii3Seo\SeoInjection;
use Rasuvaeff\Yii3Seo\SetSeoMetadataEventHandler;
use Rasuvaeff\Yii3Seo\SitemapFileExporter;

/** @var array $params */

$defaults = $params['rasuvaeff/yii3-seo']['defaults'] ?? new MetadataDefaults();
$robotsTxtParams = $params['rasuvaeff/yii3-seo']['robotsTxt'] ?? [];

$robotsTxt = ($robotsTxtParams['indexable'] ?? true)
    ? ($robotsTxtParams['robots'] ?? RobotsTxt::allowAll(metadataBase: $defaults->getMetadataBase()))
    : RobotsTxt::disallowAll();

return [
    SeoInjection::class => [
        '__construct()' => [
            'defaults' => $defaults,
        ],
        'reset' =>
            /** @psalm-scope-this SeoInjection */
            function (): void {
                $this->reset();
            },
    ],
    SetSeoMetadataEventHandler::class => SetSeoMetadataEventHandler::class,
    SitemapFileExporter::class => [
        '__construct()' => [
            'metadataBase' => $defaults->getMetadataBase(),
            'publicPath' => $params['rasuvaeff/yii3-seo']['sitemap']['publicPath'] ?? '/',
        ],
    ],
    RobotsTxt::class => $robotsTxt,
];
