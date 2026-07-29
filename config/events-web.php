<?php

declare(strict_types=1);

use Rasuvaeff\Yii3Seo\SeoMetadataEvent;
use Rasuvaeff\Yii3Seo\SetSeoMetadataEventHandler;

return [
    SeoMetadataEvent::class => [
        [SetSeoMetadataEventHandler::class, '__invoke'],
    ],
];
