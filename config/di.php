<?php

declare(strict_types=1);

use Rasuvaeff\Yii3Seo\MetadataDefaults;
use Rasuvaeff\Yii3Seo\SeoInjection;
use Rasuvaeff\Yii3Seo\SetSeoMetadataEventHandler;

/** @var array $params */

return [
    SeoInjection::class => [
        '__construct()' => [
            'defaults' => $params['rasuvaeff/yii3-seo']['defaults'] ?? new MetadataDefaults(),
        ],
        'reset' =>
            /** @psalm-scope-this SeoInjection */
            function (): void {
                $this->reset();
            },
    ],
    SetSeoMetadataEventHandler::class => SetSeoMetadataEventHandler::class,
];
