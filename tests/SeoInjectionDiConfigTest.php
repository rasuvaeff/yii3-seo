<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Seo\Metadata;
use Rasuvaeff\Yii3Seo\SeoInjection;

#[CoversClass(SeoInjection::class)]
final class SeoInjectionDiConfigTest extends TestCase
{
    #[Test]
    public function resetClearsPerRequestMetadataState(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(title: 'Stale title'));

        $injection->reset();

        $this->assertSame('', $injection->getTitle());
        $this->assertSame([], $injection->getMetaTags());
        $this->assertSame([], $injection->getLinkTags());
        $this->assertSame('', $injection->getJsonLdHtml());
    }
}
