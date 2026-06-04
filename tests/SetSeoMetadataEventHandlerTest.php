<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Seo\Metadata;
use Rasuvaeff\Yii3Seo\SeoInjection;
use Rasuvaeff\Yii3Seo\SeoMetadataEvent;
use Rasuvaeff\Yii3Seo\SetSeoMetadataEventHandler;

#[CoversClass(SetSeoMetadataEventHandler::class)]
final class SetSeoMetadataEventHandlerTest extends TestCase
{
    #[Test]
    public function setsMetadataOnInjection(): void
    {
        $injection = new SeoInjection();
        $handler = new SetSeoMetadataEventHandler(seoInjection: $injection);

        $handler(new SeoMetadataEvent(metadata: new Metadata(title: 'Home')));

        $this->assertSame('Home', $injection->getTitle());
    }
}
