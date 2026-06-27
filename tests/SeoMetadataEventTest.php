<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use Rasuvaeff\Yii3Seo\Metadata;
use Rasuvaeff\Yii3Seo\SeoMetadataEvent;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(SeoMetadataEvent::class)]
final class SeoMetadataEventTest
{
    public function exposesMetadata(): void
    {
        $metadata = new Metadata(title: 'Home');
        $event = new SeoMetadataEvent(metadata: $metadata);

        Assert::same($event->metadata, $metadata);
    }
}
