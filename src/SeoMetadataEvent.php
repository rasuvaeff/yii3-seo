<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

/**
 * @api
 */
final readonly class SeoMetadataEvent
{
    public function __construct(
        public Metadata $metadata,
    ) {}
}
