<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Seo\MetadataDefaults;
use Rasuvaeff\Yii3Seo\Title;

#[CoversClass(MetadataDefaults::class)]
final class MetadataDefaultsTest extends TestCase
{
    #[Test]
    public function stringTitleIsNormalisedToTitleObject(): void
    {
        $title = (new MetadataDefaults(title: 'Acme'))->getTitle();

        $this->assertInstanceOf(Title::class, $title);
        $this->assertSame('Acme', $title->getValue());
    }

    #[Test]
    public function metadataBaseIsStored(): void
    {
        $this->assertSame('https://example.com', (new MetadataDefaults(metadataBase: 'https://example.com'))->getMetadataBase());
    }

    #[Test]
    public function defaultsAreEmpty(): void
    {
        $defaults = new MetadataDefaults();

        $this->assertNull($defaults->getMetadataBase());
        $this->assertNull($defaults->getTitle());
        $this->assertNull($defaults->getApplicationName());
        $this->assertNull($defaults->getGenerator());
        $this->assertNull($defaults->getThemeColor());
        $this->assertNull($defaults->getColorScheme());
        $this->assertNull($defaults->getRobots());
        $this->assertNull($defaults->getOpenGraph());
        $this->assertNull($defaults->getTwitter());
        $this->assertNull($defaults->getIcons());
        $this->assertNull($defaults->getVerification());
        $this->assertSame([], $defaults->getJsonLd());
        $this->assertSame([], $defaults->getOther());
    }

    #[Test]
    public function throwsOnInvalidMetadataBase(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid metadataBase URL "not-a-url"');

        new MetadataDefaults(metadataBase: 'not-a-url');
    }
}
