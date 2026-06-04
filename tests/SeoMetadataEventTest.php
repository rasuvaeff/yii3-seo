<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Seo\Metadata;
use Rasuvaeff\Yii3Seo\SeoMetadataEvent;

#[CoversClass(SeoMetadataEvent::class)]
final class SeoMetadataEventTest extends TestCase
{
    #[Test]
    public function exposesMetadata(): void
    {
        $metadata = new Metadata(title: 'Home');
        $event = new SeoMetadataEvent(metadata: $metadata);

        $this->assertSame($metadata, $event->metadata);
    }
}
