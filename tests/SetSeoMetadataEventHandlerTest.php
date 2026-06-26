<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use Rasuvaeff\Yii3Seo\Metadata;
use Rasuvaeff\Yii3Seo\SeoInjection;
use Rasuvaeff\Yii3Seo\SeoMetadataEvent;
use Rasuvaeff\Yii3Seo\SetSeoMetadataEventHandler;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(SetSeoMetadataEventHandler::class)]
final class SetSeoMetadataEventHandlerTest
{
    public function setsMetadataOnInjection(): void
    {
        $injection = new SeoInjection();
        $handler = new SetSeoMetadataEventHandler(seoInjection: $injection);

        $handler(new SeoMetadataEvent(metadata: new Metadata(title: 'Home')));

        Assert::same($injection->getTitle(), 'Home');
    }
}
