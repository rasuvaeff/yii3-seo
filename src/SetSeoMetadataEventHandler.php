<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

/**
 * @api
 */
final readonly class SetSeoMetadataEventHandler
{
    public function __construct(
        private SeoInjection $seoInjection,
    ) {}

    public function __invoke(SeoMetadataEvent $event): void
    {
        $this->seoInjection->setMetadata($event->metadata);
    }
}
