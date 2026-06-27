<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\MetadataDefaults;
use Rasuvaeff\Yii3Seo\Title;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(MetadataDefaults::class)]
final class MetadataDefaultsTest
{
    public function stringTitleIsNormalisedToTitleObject(): void
    {
        $title = (new MetadataDefaults(title: 'Acme'))->getTitle();

        Assert::instanceOf($title, Title::class);
        Assert::same($title->getValue(), 'Acme');
    }

    public function metadataBaseIsStored(): void
    {
        Assert::same((new MetadataDefaults(metadataBase: 'https://example.com'))->getMetadataBase(), 'https://example.com');
    }

    public function defaultsAreEmpty(): void
    {
        $defaults = new MetadataDefaults();

        Assert::null($defaults->getMetadataBase());
        Assert::null($defaults->getTitle());
        Assert::null($defaults->getApplicationName());
        Assert::null($defaults->getGenerator());
        Assert::null($defaults->getThemeColor());
        Assert::null($defaults->getColorScheme());
        Assert::null($defaults->getRobots());
        Assert::null($defaults->getOpenGraph());
        Assert::null($defaults->getTwitter());
        Assert::null($defaults->getIcons());
        Assert::null($defaults->getVerification());
        Assert::same($defaults->getJsonLd(), []);
        Assert::same($defaults->getOther(), []);
    }

    public function throwsOnInvalidMetadataBase(): void
    {
        try {
            new MetadataDefaults(metadataBase: 'not-a-url');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Invalid metadataBase URL "not-a-url"');
        }
    }
}
