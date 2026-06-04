<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Seo\Alternates;
use Rasuvaeff\Yii3Seo\Author;
use Rasuvaeff\Yii3Seo\Icon;
use Rasuvaeff\Yii3Seo\Icons;
use Rasuvaeff\Yii3Seo\JsonLd;
use Rasuvaeff\Yii3Seo\Metadata;
use Rasuvaeff\Yii3Seo\MetadataDefaults;
use Rasuvaeff\Yii3Seo\MetaTag;
use Rasuvaeff\Yii3Seo\OgImage;
use Rasuvaeff\Yii3Seo\OpenGraph;
use Rasuvaeff\Yii3Seo\Robots;
use Rasuvaeff\Yii3Seo\SeoInjection;
use Rasuvaeff\Yii3Seo\Title;
use Rasuvaeff\Yii3Seo\TwitterCard;
use Rasuvaeff\Yii3Seo\Verification;
use Yiisoft\Html\Tag\Link;
use Yiisoft\Html\Tag\Meta;

#[CoversClass(SeoInjection::class)]
final class SeoInjectionTest extends TestCase
{
    #[Test]
    public function returnsEmptyWhenNothingSet(): void
    {
        $injection = new SeoInjection();

        $this->assertSame([], $injection->getMetaTags());
        $this->assertSame([], $injection->getLinkTags());
        $this->assertSame('', $injection->getTitle());
        $this->assertSame('', $injection->getJsonLdHtml());
    }

    #[Test]
    public function titleTemplateFromDefaultsIsApplied(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(title: Title::template('%s | Acme', default: 'Acme')));
        $injection->setMetadata(new Metadata(title: 'Home'));

        $this->assertSame('Home | Acme', $injection->getTitle());
    }

    #[Test]
    public function absoluteTitleBypassesTemplate(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(title: Title::template('%s | Acme')));
        $injection->setMetadata(new Metadata(title: Title::absolute('Exact')));

        $this->assertSame('Exact', $injection->getTitle());
    }

    #[Test]
    public function defaultTitleUsedWhenPageHasNone(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(title: Title::template('%s | Acme', default: 'Acme')));
        $injection->setMetadata(new Metadata());

        $this->assertSame('Acme', $injection->getTitle());
    }

    #[Test]
    public function plainTitleWhenNoTemplate(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(title: 'Home'));

        $this->assertSame('Home', $injection->getTitle());
    }

    #[Test]
    public function descriptionKeywordsAndAuthorsAreRendered(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            description: 'My desc',
            keywords: ['php', 'seo'],
            authors: [new Author(name: 'Alice', url: 'https://example.com/alice')],
        ));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta name="description" content="My desc">', $meta);
        $this->assertContains('<meta name="keywords" content="php, seo">', $meta);
        $this->assertContains('<meta name="author" content="Alice">', $meta);
        $this->assertStringContainsString('rel="author"', $this->renderLinks($injection));
    }

    #[Test]
    public function applicationNameComesFromDefaultsAndIsOverridable(): void
    {
        $defaults = new MetadataDefaults(applicationName: 'Site', generator: 'Yii');

        $fromDefaults = new SeoInjection($defaults);
        $fromDefaults->setMetadata(new Metadata());
        $this->assertContains('<meta name="application-name" content="Site">', $this->renderMeta($fromDefaults));
        $this->assertContains('<meta name="generator" content="Yii">', $this->renderMeta($fromDefaults));

        $overridden = new SeoInjection($defaults);
        $overridden->setMetadata(new Metadata(applicationName: 'Page'));
        $this->assertContains('<meta name="application-name" content="Page">', $this->renderMeta($overridden));
    }

    #[Test]
    public function creatorPublisherThemeColorAndColorSchemeAreRendered(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            creator: 'Creator',
            publisher: 'Publisher',
            themeColor: '#ffffff',
            colorScheme: 'dark light',
        ));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta name="creator" content="Creator">', $meta);
        $this->assertContains('<meta name="publisher" content="Publisher">', $meta);
        $this->assertContains('<meta name="theme-color" content="#ffffff">', $meta);
        $this->assertContains('<meta name="color-scheme" content="dark light">', $meta);
    }

    #[Test]
    public function robotsAndGoogleBotAreRendered(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            robots: Robots::none()->withMaxImagePreview('large')->withGoogleBot('noindex'),
        ));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta name="robots" content="noindex, nofollow, max-image-preview:large">', $meta);
        $this->assertContains('<meta name="googlebot" content="noindex">', $meta);
    }

    #[Test]
    public function verificationTagsAreRendered(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            verification: new Verification(google: 'g', yandex: 'y', bing: 'b', other: ['me' => 'm']),
        ));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta name="google-site-verification" content="g">', $meta);
        $this->assertContains('<meta name="yandex-verification" content="y">', $meta);
        $this->assertContains('<meta name="msvalidate.01" content="b">', $meta);
        $this->assertContains('<meta name="me" content="m">', $meta);
    }

    #[Test]
    public function customMetaTagsFromDefaultsAndPageAreRendered(): void
    {
        $defaults = new MetadataDefaults(other: [MetaTag::property('fb:app_id', '123')]);
        $injection = new SeoInjection($defaults);
        $injection->setMetadata(new Metadata(other: [
            MetaTag::name('rating', 'general'),
            MetaTag::httpEquiv('x-ua-compatible', 'IE=edge'),
        ]));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta property="fb:app_id" content="123">', $meta);
        $this->assertContains('<meta name="rating" content="general">', $meta);
        $this->assertContains('<meta http-equiv="x-ua-compatible" content="IE=edge">', $meta);
    }

    #[Test]
    public function openGraphIsRenderedWithImageDimensions(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(metadataBase: 'https://example.com'));
        $injection->setMetadata(new Metadata(
            openGraph: new OpenGraph(
                title: 'OG Title',
                description: 'OG Desc',
                type: 'article',
                url: '/article',
                siteName: 'My Site',
                locale: 'en_US',
                images: [new OgImage(url: '/og.jpg', width: 1200, height: 630, alt: 'Alt', type: 'image/jpeg')],
            ),
        ));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta property="og:title" content="OG Title">', $meta);
        $this->assertContains('<meta property="og:type" content="article">', $meta);
        $this->assertContains('<meta property="og:description" content="OG Desc">', $meta);
        $this->assertContains('<meta property="og:url" content="https://example.com/article">', $meta);
        $this->assertContains('<meta property="og:site_name" content="My Site">', $meta);
        $this->assertContains('<meta property="og:locale" content="en_US">', $meta);
        $this->assertContains('<meta property="og:image" content="https://example.com/og.jpg">', $meta);
        $this->assertContains('<meta property="og:image:width" content="1200">', $meta);
        $this->assertContains('<meta property="og:image:height" content="630">', $meta);
        $this->assertContains('<meta property="og:image:alt" content="Alt">', $meta);
        $this->assertContains('<meta property="og:image:type" content="image/jpeg">', $meta);
    }

    #[Test]
    public function openGraphTitleAndDescriptionFallBackToMetadata(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            title: 'Home',
            description: 'Page desc',
            openGraph: new OpenGraph(),
        ));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta property="og:title" content="Home">', $meta);
        $this->assertContains('<meta property="og:description" content="Page desc">', $meta);
    }

    #[Test]
    public function openGraphInheritsSiteNameFromDefaults(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(openGraph: new OpenGraph(siteName: 'Brand')));
        $injection->setMetadata(new Metadata(title: 'Home', openGraph: new OpenGraph(title: 'Page OG')));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta property="og:title" content="Page OG">', $meta);
        $this->assertContains('<meta property="og:site_name" content="Brand">', $meta);
    }

    #[Test]
    public function twitterCardFallsBackToOpenGraph(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            openGraph: new OpenGraph(title: 'OG Title', description: 'OG Desc'),
            twitter: new TwitterCard(card: 'summary', site: '@site'),
        ));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta name="twitter:card" content="summary">', $meta);
        $this->assertContains('<meta name="twitter:site" content="@site">', $meta);
        $this->assertContains('<meta name="twitter:title" content="OG Title">', $meta);
        $this->assertContains('<meta name="twitter:description" content="OG Desc">', $meta);
    }

    #[Test]
    public function twitterImagesFallBackToOpenGraphImages(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(metadataBase: 'https://example.com'));
        $injection->setMetadata(new Metadata(
            openGraph: new OpenGraph(images: [new OgImage(url: '/og.jpg')]),
            twitter: new TwitterCard(),
        ));

        $this->assertContains('<meta name="twitter:image" content="https://example.com/og.jpg">', $this->renderMeta($injection));
    }

    #[Test]
    public function canonicalAndHreflangAreResolvedAgainstBase(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(metadataBase: 'https://example.com'));
        $injection->setMetadata(new Metadata(
            alternates: new Alternates(
                canonical: '/products/1',
                languages: ['en' => '/en/products/1', 'x-default' => '/products/1'],
            ),
        ));

        $links = $injection->getLinkTags();
        $this->assertArrayHasKey('canonical', $links);
        $this->assertStringContainsString('href="https://example.com/products/1"', $links['canonical']->render());

        $combined = $this->renderLinks($injection);
        $this->assertStringContainsString('hreflang="en"', $combined);
        $this->assertStringContainsString('href="https://example.com/en/products/1"', $combined);
        $this->assertStringContainsString('hreflang="x-default"', $combined);
    }

    #[Test]
    public function absoluteUrlsPassThroughUnchanged(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(metadataBase: 'https://example.com'));
        $injection->setMetadata(new Metadata(alternates: new Alternates(canonical: 'https://other.com/x')));

        $links = $injection->getLinkTags();

        $this->assertStringContainsString('href="https://other.com/x"', $links['canonical']->render());
    }

    #[Test]
    public function relativeUrlWithoutBaseThrows(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(alternates: new Alternates(canonical: '/products/1')));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Relative URL "/products/1" requires a metadataBase to be configured');

        $injection->getLinkTags();
    }

    #[Test]
    public function iconsAndManifestAreRendered(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            icons: new Icons(icon: '/favicon.ico', apple: '/apple.png'),
            manifest: '/site.webmanifest',
        ));

        $combined = $this->renderLinks($injection);

        $this->assertStringContainsString('rel="icon"', $combined);
        $this->assertStringContainsString('href="/favicon.ico"', $combined);
        $this->assertStringContainsString('rel="apple-touch-icon"', $combined);
        $this->assertStringContainsString('rel="manifest"', $combined);
        $this->assertStringContainsString('href="/site.webmanifest"', $combined);
    }

    #[Test]
    public function iconsComeFromDefaults(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(icons: new Icons(icon: '/favicon.ico')));
        $injection->setMetadata(new Metadata(title: 'Home'));

        $this->assertStringContainsString('rel="icon"', $this->renderLinks($injection));
    }

    #[Test]
    public function jsonLdConcatenatesDefaultsAndPage(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(
            jsonLd: [JsonLd::fromArray(['@type' => 'Organization'])],
        ));
        $injection->setMetadata(new Metadata(jsonLd: [JsonLd::fromArray(['@type' => 'WebPage'])]));

        $html = $injection->getJsonLdHtml();

        $this->assertStringContainsString('Organization', $html);
        $this->assertStringContainsString('WebPage', $html);
    }

    #[Test]
    public function clearResetsState(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(title: 'Home', description: 'Desc'));
        $injection->clear();

        $this->assertSame('', $injection->getTitle());
        $this->assertSame([], $injection->getMetaTags());
    }

    #[Test]
    public function pageOverridesDefaultsForScalarMetaFields(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(
            applicationName: 'DApp',
            generator: 'DGen',
            themeColor: '#000000',
            colorScheme: 'light',
            robots: Robots::noindex(),
            verification: new Verification(google: 'DG'),
        ));
        $injection->setMetadata(new Metadata(
            description: 'Page desc',
            applicationName: 'PApp',
            generator: 'PGen',
            themeColor: '#ffffff',
            colorScheme: 'dark',
            robots: Robots::index(),
            verification: new Verification(google: 'PG'),
        ));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta name="description" content="Page desc">', $meta);
        $this->assertContains('<meta name="application-name" content="PApp">', $meta);
        $this->assertContains('<meta name="generator" content="PGen">', $meta);
        $this->assertContains('<meta name="theme-color" content="#ffffff">', $meta);
        $this->assertContains('<meta name="color-scheme" content="dark">', $meta);
        $this->assertContains('<meta name="robots" content="index, follow">', $meta);
        $this->assertContains('<meta name="google-site-verification" content="PG">', $meta);
    }

    #[Test]
    public function scalarMetaFieldsFallBackToDefaults(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(
            generator: 'DGen',
            themeColor: '#000000',
            colorScheme: 'light',
            robots: Robots::noindex(),
            verification: new Verification(google: 'DG'),
        ));
        $injection->setMetadata(new Metadata(description: 'Desc'));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta name="generator" content="DGen">', $meta);
        $this->assertContains('<meta name="theme-color" content="#000000">', $meta);
        $this->assertContains('<meta name="color-scheme" content="light">', $meta);
        $this->assertContains('<meta name="robots" content="noindex">', $meta);
        $this->assertContains('<meta name="google-site-verification" content="DG">', $meta);
    }

    #[Test]
    public function openGraphPageOverridesDefaultsFieldByField(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(openGraph: new OpenGraph(
            title: 'DT',
            description: 'DD',
            type: 'website',
            url: 'https://d.com/d',
            siteName: 'DS',
            locale: 'de_DE',
            images: [new OgImage(url: 'https://d.com/di.jpg')],
        )));
        $injection->setMetadata(new Metadata(openGraph: new OpenGraph(
            title: 'PT',
            description: 'PD',
            type: 'article',
            url: 'https://p.com/p',
            siteName: 'PS',
            locale: 'en_US',
            images: [new OgImage(url: 'https://p.com/pi.jpg')],
        )));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta property="og:title" content="PT">', $meta);
        $this->assertContains('<meta property="og:description" content="PD">', $meta);
        $this->assertContains('<meta property="og:type" content="article">', $meta);
        $this->assertContains('<meta property="og:url" content="https://p.com/p">', $meta);
        $this->assertContains('<meta property="og:site_name" content="PS">', $meta);
        $this->assertContains('<meta property="og:locale" content="en_US">', $meta);
        $this->assertContains('<meta property="og:image" content="https://p.com/pi.jpg">', $meta);
    }

    #[Test]
    public function openGraphInheritsFieldsFromDefaultsWhenPageOmitsThem(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(openGraph: new OpenGraph(
            title: 'DT',
            description: 'DD',
            type: 'profile',
            url: 'https://d.com/d',
            siteName: 'DS',
            locale: 'de_DE',
            images: [new OgImage(url: 'https://d.com/di.jpg')],
        )));
        $injection->setMetadata(new Metadata(openGraph: new OpenGraph()));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta property="og:title" content="DT">', $meta);
        $this->assertContains('<meta property="og:description" content="DD">', $meta);
        $this->assertContains('<meta property="og:type" content="profile">', $meta);
        $this->assertContains('<meta property="og:url" content="https://d.com/d">', $meta);
        $this->assertContains('<meta property="og:site_name" content="DS">', $meta);
        $this->assertContains('<meta property="og:locale" content="de_DE">', $meta);
        $this->assertContains('<meta property="og:image" content="https://d.com/di.jpg">', $meta);
    }

    #[Test]
    public function openGraphComesFromDefaultsWhenPageHasNoOpenGraph(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(openGraph: new OpenGraph(
            title: 'DT',
            siteName: 'DS',
            images: [new OgImage(url: 'https://d.com/di.jpg')],
        )));
        $injection->setMetadata(new Metadata());

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta property="og:title" content="DT">', $meta);
        $this->assertContains('<meta property="og:site_name" content="DS">', $meta);
        $this->assertContains('<meta property="og:image" content="https://d.com/di.jpg">', $meta);
    }

    #[Test]
    public function openGraphWorksWithEmptyPageObjectAndNoDefaults(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(openGraph: new OpenGraph()));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta property="og:type" content="website">', $meta);
    }

    #[Test]
    public function openGraphDescriptionPrefersOwnValueOverMetadataDescription(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            description: 'Page desc',
            openGraph: new OpenGraph(title: 'OG', description: 'OG desc'),
        ));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta name="description" content="Page desc">', $meta);
        $this->assertContains('<meta property="og:description" content="OG desc">', $meta);
    }

    #[Test]
    public function twitterPageOverridesDefaultsFieldByField(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(twitter: new TwitterCard(
            card: 'summary',
            site: '@dsite',
            creator: '@dcreator',
            title: 'DT',
            description: 'DD',
            images: ['https://d.com/di.jpg'],
        )));
        $injection->setMetadata(new Metadata(
            description: 'Page desc',
            twitter: new TwitterCard(
                card: 'summary_large_image',
                site: '@psite',
                creator: '@pcreator',
                title: 'PT',
                description: 'PD',
                images: ['https://p.com/pi.jpg'],
            ),
        ));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta name="description" content="Page desc">', $meta);
        $this->assertContains('<meta name="twitter:card" content="summary_large_image">', $meta);
        $this->assertContains('<meta name="twitter:site" content="@psite">', $meta);
        $this->assertContains('<meta name="twitter:creator" content="@pcreator">', $meta);
        $this->assertContains('<meta name="twitter:title" content="PT">', $meta);
        $this->assertContains('<meta name="twitter:description" content="PD">', $meta);
        $this->assertContains('<meta name="twitter:image" content="https://p.com/pi.jpg">', $meta);
    }

    #[Test]
    public function twitterInheritsFromDefaultsWhenPageOmitsFields(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(twitter: new TwitterCard(
            card: 'summary',
            site: '@dsite',
            creator: '@dcreator',
            images: ['https://d.com/di.jpg'],
        )));
        $injection->setMetadata(new Metadata(twitter: new TwitterCard()));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta name="twitter:card" content="summary">', $meta);
        $this->assertContains('<meta name="twitter:site" content="@dsite">', $meta);
        $this->assertContains('<meta name="twitter:creator" content="@dcreator">', $meta);
        $this->assertContains('<meta name="twitter:image" content="https://d.com/di.jpg">', $meta);
    }

    #[Test]
    public function twitterComesFromDefaultsWhenPageHasNoTwitter(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(twitter: new TwitterCard(card: 'summary', site: '@dsite')));
        $injection->setMetadata(new Metadata());

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta name="twitter:card" content="summary">', $meta);
        $this->assertContains('<meta name="twitter:site" content="@dsite">', $meta);
    }

    #[Test]
    public function twitterWorksWithEmptyPageObjectAndNoDefaults(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(twitter: new TwitterCard()));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta name="twitter:card" content="summary_large_image">', $meta);
    }

    #[Test]
    public function twitterTitleAndDescriptionPreferOwnValuesOverOpenGraph(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            openGraph: new OpenGraph(title: 'OG title', description: 'OG desc'),
            twitter: new TwitterCard(title: 'TW title', description: 'TW desc'),
        ));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta name="twitter:title" content="TW title">', $meta);
        $this->assertContains('<meta name="twitter:description" content="TW desc">', $meta);
    }

    #[Test]
    public function twitterUsesOwnImagesInsteadOfOpenGraphImages(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            openGraph: new OpenGraph(images: [new OgImage(url: 'https://og.com/og.jpg')]),
            twitter: new TwitterCard(images: ['https://tw.com/tw.jpg']),
        ));

        $meta = $this->renderMeta($injection);

        $this->assertContains('<meta name="twitter:image" content="https://tw.com/tw.jpg">', $meta);
        $this->assertNotContains('<meta name="twitter:image" content="https://og.com/og.jpg">', $meta);
    }

    #[Test]
    public function iconsPageOverridesDefaults(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(icons: new Icons(icon: '/default.ico')));
        $injection->setMetadata(new Metadata(icons: new Icons(icon: '/page.ico')));

        $combined = $this->renderLinks($injection);

        $this->assertStringContainsString('href="/page.ico"', $combined);
        $this->assertStringNotContainsString('href="/default.ico"', $combined);
    }

    #[Test]
    public function iconTypeAndSizesAttributesAreRendered(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(icons: new Icons(other: [
            new Icon(rel: 'icon', url: '/favicon-32.png', sizes: '32x32', type: 'image/png'),
        ])));

        $combined = $this->renderLinks($injection);

        $this->assertStringContainsString('type="image/png"', $combined);
        $this->assertStringContainsString('sizes="32x32"', $combined);
    }

    /**
     * @return list<string>
     */
    private function renderMeta(SeoInjection $injection): array
    {
        return array_map(static fn(Meta $tag): string => $tag->render(), $injection->getMetaTags());
    }

    private function renderLinks(SeoInjection $injection): string
    {
        return implode('', array_map(static fn(Link $tag): string => $tag->render(), $injection->getLinkTags()));
    }
}
