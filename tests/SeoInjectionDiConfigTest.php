<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use Rasuvaeff\Yii3Seo\Metadata;
use Rasuvaeff\Yii3Seo\SeoInjection;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(SeoInjection::class)]
final class SeoInjectionDiConfigTest
{
    public function resetClearsPerRequestMetadataState(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(title: 'Stale title'));

        $injection->reset();

        Assert::same($injection->getTitle(), '');
        Assert::same($injection->getMetaTags(), []);
        Assert::same($injection->getLinkTags(), []);
        Assert::same($injection->getJsonLdHtml(), '');
    }
}
