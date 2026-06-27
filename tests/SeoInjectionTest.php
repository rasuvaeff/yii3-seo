<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
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
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Yiisoft\Html\Tag\Link;
use Yiisoft\Html\Tag\Meta;

#[Test]
#[Covers(SeoInjection::class)]
final class SeoInjectionTest
{
    public function returnsEmptyWhenNothingSet(): void
    {
        $injection = new SeoInjection();

        Assert::same($injection->getMetaTags(), []);
        Assert::same($injection->getLinkTags(), []);
        Assert::same($injection->getTitle(), '');
        Assert::same($injection->getJsonLdHtml(), '');
    }

    public function titleTemplateFromDefaultsIsApplied(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(title: Title::template('%s | Acme', default: 'Acme')));
        $injection->setMetadata(new Metadata(title: 'Home'));

        Assert::same($injection->getTitle(), 'Home | Acme');
    }

    public function absoluteTitleBypassesTemplate(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(title: Title::template('%s | Acme')));
        $injection->setMetadata(new Metadata(title: Title::absolute('Exact')));

        Assert::same($injection->getTitle(), 'Exact');
    }

    public function defaultTitleUsedWhenPageHasNone(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(title: Title::template('%s | Acme', default: 'Acme')));
        $injection->setMetadata(new Metadata());

        Assert::same($injection->getTitle(), 'Acme');
    }

    public function plainTitleWhenNoTemplate(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(title: 'Home'));

        Assert::same($injection->getTitle(), 'Home');
    }

    public function descriptionKeywordsAndAuthorsAreRendered(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            description: 'My desc',
            keywords: ['php', 'seo'],
            authors: [new Author(name: 'Alice', url: 'https://example.com/alice')],
        ));

        $meta = $this->renderMeta($injection);

        Assert::contains($meta, '<meta name="description" content="My desc">');
        Assert::contains($meta, '<meta name="keywords" content="php, seo">');
        Assert::contains($meta, '<meta name="author" content="Alice">');
        Assert::string($this->renderLinks($injection))->contains('rel="author"');
    }

    public function applicationNameComesFromDefaultsAndIsOverridable(): void
    {
        $defaults = new MetadataDefaults(applicationName: 'Site', generator: 'Yii');

        $fromDefaults = new SeoInjection($defaults);
        $fromDefaults->setMetadata(new Metadata());
        Assert::contains($this->renderMeta($fromDefaults), '<meta name="application-name" content="Site">');
        Assert::contains($this->renderMeta($fromDefaults), '<meta name="generator" content="Yii">');

        $overridden = new SeoInjection($defaults);
        $overridden->setMetadata(new Metadata(applicationName: 'Page'));
        Assert::contains($this->renderMeta($overridden), '<meta name="application-name" content="Page">');
    }

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

        Assert::contains($meta, '<meta name="creator" content="Creator">');
        Assert::contains($meta, '<meta name="publisher" content="Publisher">');
        Assert::contains($meta, '<meta name="theme-color" content="#ffffff">');
        Assert::contains($meta, '<meta name="color-scheme" content="dark light">');
    }

    public function robotsAndGoogleBotAreRendered(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            robots: Robots::none()->withMaxImagePreview('large')->withGoogleBot('noindex'),
        ));

        $meta = $this->renderMeta($injection);

        Assert::contains($meta, '<meta name="robots" content="noindex, nofollow, max-image-preview:large">');
        Assert::contains($meta, '<meta name="googlebot" content="noindex">');
    }

    public function verificationTagsAreRendered(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            verification: new Verification(google: 'g', yandex: 'y', bing: 'b', other: ['me' => 'm']),
        ));

        $meta = $this->renderMeta($injection);

        Assert::contains($meta, '<meta name="google-site-verification" content="g">');
        Assert::contains($meta, '<meta name="yandex-verification" content="y">');
        Assert::contains($meta, '<meta name="msvalidate.01" content="b">');
        Assert::contains($meta, '<meta name="me" content="m">');
    }

    public function customMetaTagsFromDefaultsAndPageAreRendered(): void
    {
        $defaults = new MetadataDefaults(other: [MetaTag::property('fb:app_id', '123')]);
        $injection = new SeoInjection($defaults);
        $injection->setMetadata(new Metadata(other: [
            MetaTag::name('rating', 'general'),
            MetaTag::httpEquiv('x-ua-compatible', 'IE=edge'),
        ]));

        $meta = $this->renderMeta($injection);

        Assert::contains($meta, '<meta property="fb:app_id" content="123">');
        Assert::contains($meta, '<meta name="rating" content="general">');
        Assert::contains($meta, '<meta http-equiv="x-ua-compatible" content="IE=edge">');
    }

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

        Assert::contains($meta, '<meta property="og:title" content="OG Title">');
        Assert::contains($meta, '<meta property="og:type" content="article">');
        Assert::contains($meta, '<meta property="og:description" content="OG Desc">');
        Assert::contains($meta, '<meta property="og:url" content="https://example.com/article">');
        Assert::contains($meta, '<meta property="og:site_name" content="My Site">');
        Assert::contains($meta, '<meta property="og:locale" content="en_US">');
        Assert::contains($meta, '<meta property="og:image" content="https://example.com/og.jpg">');
        Assert::contains($meta, '<meta property="og:image:width" content="1200">');
        Assert::contains($meta, '<meta property="og:image:height" content="630">');
        Assert::contains($meta, '<meta property="og:image:alt" content="Alt">');
        Assert::contains($meta, '<meta property="og:image:type" content="image/jpeg">');
    }

    public function openGraphTitleAndDescriptionFallBackToMetadata(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            title: 'Home',
            description: 'Page desc',
            openGraph: new OpenGraph(),
        ));

        $meta = $this->renderMeta($injection);

        Assert::contains($meta, '<meta property="og:title" content="Home">');
        Assert::contains($meta, '<meta property="og:description" content="Page desc">');
    }

    public function openGraphInheritsSiteNameFromDefaults(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(openGraph: new OpenGraph(siteName: 'Brand')));
        $injection->setMetadata(new Metadata(title: 'Home', openGraph: new OpenGraph(title: 'Page OG')));

        $meta = $this->renderMeta($injection);

        Assert::contains($meta, '<meta property="og:title" content="Page OG">');
        Assert::contains($meta, '<meta property="og:site_name" content="Brand">');
    }

    public function twitterCardFallsBackToOpenGraph(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            openGraph: new OpenGraph(title: 'OG Title', description: 'OG Desc'),
            twitter: new TwitterCard(card: 'summary', site: '@site'),
        ));

        $meta = $this->renderMeta($injection);

        Assert::contains($meta, '<meta name="twitter:card" content="summary">');
        Assert::contains($meta, '<meta name="twitter:site" content="@site">');
        Assert::contains($meta, '<meta name="twitter:title" content="OG Title">');
        Assert::contains($meta, '<meta name="twitter:description" content="OG Desc">');
    }

    public function twitterImagesFallBackToOpenGraphImages(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(metadataBase: 'https://example.com'));
        $injection->setMetadata(new Metadata(
            openGraph: new OpenGraph(images: [new OgImage(url: '/og.jpg')]),
            twitter: new TwitterCard(),
        ));

        Assert::contains($this->renderMeta($injection), '<meta name="twitter:image" content="https://example.com/og.jpg">');
    }

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
        Assert::array($links)->hasKeys('canonical');
        Assert::string($links['canonical']->render())->contains('href="https://example.com/products/1"');

        $combined = $this->renderLinks($injection);
        Assert::string($combined)->contains('hreflang="en"');
        Assert::string($combined)->contains('href="https://example.com/en/products/1"');
        Assert::string($combined)->contains('hreflang="x-default"');
    }

    public function absoluteUrlsPassThroughUnchanged(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(metadataBase: 'https://example.com'));
        $injection->setMetadata(new Metadata(alternates: new Alternates(canonical: 'https://other.com/x')));

        $links = $injection->getLinkTags();

        Assert::string($links['canonical']->render())->contains('href="https://other.com/x"');
    }

    public function relativeUrlWithoutBaseThrows(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(alternates: new Alternates(canonical: '/products/1')));

        try {
            $injection->getLinkTags();
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Relative URL "/products/1" requires a metadataBase to be configured');
        }
    }

    public function iconsAndManifestAreRendered(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            icons: new Icons(icon: '/favicon.ico', apple: '/apple.png'),
            manifest: '/site.webmanifest',
        ));

        $combined = $this->renderLinks($injection);

        Assert::string($combined)->contains('rel="icon"');
        Assert::string($combined)->contains('href="/favicon.ico"');
        Assert::string($combined)->contains('rel="apple-touch-icon"');
        Assert::string($combined)->contains('rel="manifest"');
        Assert::string($combined)->contains('href="/site.webmanifest"');
    }

    public function iconsComeFromDefaults(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(icons: new Icons(icon: '/favicon.ico')));
        $injection->setMetadata(new Metadata(title: 'Home'));

        Assert::string($this->renderLinks($injection))->contains('rel="icon"');
    }

    public function jsonLdConcatenatesDefaultsAndPage(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(
            jsonLd: [JsonLd::fromArray(['@type' => 'Organization'])],
        ));
        $injection->setMetadata(new Metadata(jsonLd: [JsonLd::fromArray(['@type' => 'WebPage'])]));

        $html = $injection->getJsonLdHtml();

        Assert::string($html)->contains('Organization');
        Assert::string($html)->contains('WebPage');
    }

    public function clearResetsState(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(title: 'Home', description: 'Desc'));
        $injection->clear();

        Assert::same($injection->getTitle(), '');
        Assert::same($injection->getMetaTags(), []);
    }

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

        Assert::contains($meta, '<meta name="description" content="Page desc">');
        Assert::contains($meta, '<meta name="application-name" content="PApp">');
        Assert::contains($meta, '<meta name="generator" content="PGen">');
        Assert::contains($meta, '<meta name="theme-color" content="#ffffff">');
        Assert::contains($meta, '<meta name="color-scheme" content="dark">');
        Assert::contains($meta, '<meta name="robots" content="index, follow">');
        Assert::contains($meta, '<meta name="google-site-verification" content="PG">');
    }

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

        Assert::contains($meta, '<meta name="generator" content="DGen">');
        Assert::contains($meta, '<meta name="theme-color" content="#000000">');
        Assert::contains($meta, '<meta name="color-scheme" content="light">');
        Assert::contains($meta, '<meta name="robots" content="noindex">');
        Assert::contains($meta, '<meta name="google-site-verification" content="DG">');
    }

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

        Assert::contains($meta, '<meta property="og:title" content="PT">');
        Assert::contains($meta, '<meta property="og:description" content="PD">');
        Assert::contains($meta, '<meta property="og:type" content="article">');
        Assert::contains($meta, '<meta property="og:url" content="https://p.com/p">');
        Assert::contains($meta, '<meta property="og:site_name" content="PS">');
        Assert::contains($meta, '<meta property="og:locale" content="en_US">');
        Assert::contains($meta, '<meta property="og:image" content="https://p.com/pi.jpg">');
    }

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

        Assert::contains($meta, '<meta property="og:title" content="DT">');
        Assert::contains($meta, '<meta property="og:description" content="DD">');
        Assert::contains($meta, '<meta property="og:type" content="profile">');
        Assert::contains($meta, '<meta property="og:url" content="https://d.com/d">');
        Assert::contains($meta, '<meta property="og:site_name" content="DS">');
        Assert::contains($meta, '<meta property="og:locale" content="de_DE">');
        Assert::contains($meta, '<meta property="og:image" content="https://d.com/di.jpg">');
    }

    public function openGraphComesFromDefaultsWhenPageHasNoOpenGraph(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(openGraph: new OpenGraph(
            title: 'DT',
            siteName: 'DS',
            images: [new OgImage(url: 'https://d.com/di.jpg')],
        )));
        $injection->setMetadata(new Metadata());

        $meta = $this->renderMeta($injection);

        Assert::contains($meta, '<meta property="og:title" content="DT">');
        Assert::contains($meta, '<meta property="og:site_name" content="DS">');
        Assert::contains($meta, '<meta property="og:image" content="https://d.com/di.jpg">');
    }

    public function openGraphWorksWithEmptyPageObjectAndNoDefaults(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(openGraph: new OpenGraph()));

        $meta = $this->renderMeta($injection);

        Assert::contains($meta, '<meta property="og:type" content="website">');
    }

    public function openGraphDescriptionPrefersOwnValueOverMetadataDescription(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            description: 'Page desc',
            openGraph: new OpenGraph(title: 'OG', description: 'OG desc'),
        ));

        $meta = $this->renderMeta($injection);

        Assert::contains($meta, '<meta name="description" content="Page desc">');
        Assert::contains($meta, '<meta property="og:description" content="OG desc">');
    }

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

        Assert::contains($meta, '<meta name="description" content="Page desc">');
        Assert::contains($meta, '<meta name="twitter:card" content="summary_large_image">');
        Assert::contains($meta, '<meta name="twitter:site" content="@psite">');
        Assert::contains($meta, '<meta name="twitter:creator" content="@pcreator">');
        Assert::contains($meta, '<meta name="twitter:title" content="PT">');
        Assert::contains($meta, '<meta name="twitter:description" content="PD">');
        Assert::contains($meta, '<meta name="twitter:image" content="https://p.com/pi.jpg">');
    }

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

        Assert::contains($meta, '<meta name="twitter:card" content="summary">');
        Assert::contains($meta, '<meta name="twitter:site" content="@dsite">');
        Assert::contains($meta, '<meta name="twitter:creator" content="@dcreator">');
        Assert::contains($meta, '<meta name="twitter:image" content="https://d.com/di.jpg">');
    }

    public function twitterComesFromDefaultsWhenPageHasNoTwitter(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(twitter: new TwitterCard(card: 'summary', site: '@dsite')));
        $injection->setMetadata(new Metadata());

        $meta = $this->renderMeta($injection);

        Assert::contains($meta, '<meta name="twitter:card" content="summary">');
        Assert::contains($meta, '<meta name="twitter:site" content="@dsite">');
    }

    public function twitterWorksWithEmptyPageObjectAndNoDefaults(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(twitter: new TwitterCard()));

        $meta = $this->renderMeta($injection);

        Assert::contains($meta, '<meta name="twitter:card" content="summary_large_image">');
    }

    public function twitterTitleAndDescriptionPreferOwnValuesOverOpenGraph(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            openGraph: new OpenGraph(title: 'OG title', description: 'OG desc'),
            twitter: new TwitterCard(title: 'TW title', description: 'TW desc'),
        ));

        $meta = $this->renderMeta($injection);

        Assert::contains($meta, '<meta name="twitter:title" content="TW title">');
        Assert::contains($meta, '<meta name="twitter:description" content="TW desc">');
    }

    public function twitterUsesOwnImagesInsteadOfOpenGraphImages(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(
            openGraph: new OpenGraph(images: [new OgImage(url: 'https://og.com/og.jpg')]),
            twitter: new TwitterCard(images: ['https://tw.com/tw.jpg']),
        ));

        $meta = $this->renderMeta($injection);

        Assert::contains($meta, '<meta name="twitter:image" content="https://tw.com/tw.jpg">');
        Assert::false(in_array('<meta name="twitter:image" content="https://og.com/og.jpg">', $meta, true));
    }

    public function iconsPageOverridesDefaults(): void
    {
        $injection = new SeoInjection(new MetadataDefaults(icons: new Icons(icon: '/default.ico')));
        $injection->setMetadata(new Metadata(icons: new Icons(icon: '/page.ico')));

        $combined = $this->renderLinks($injection);

        Assert::string($combined)->contains('href="/page.ico"');
        Assert::string($combined)->notContains('href="/default.ico"');
    }

    public function iconTypeAndSizesAttributesAreRendered(): void
    {
        $injection = new SeoInjection();
        $injection->setMetadata(new Metadata(icons: new Icons(other: [
            new Icon(rel: 'icon', url: '/favicon-32.png', sizes: '32x32', type: 'image/png'),
        ])));

        $combined = $this->renderLinks($injection);

        Assert::string($combined)->contains('type="image/png"');
        Assert::string($combined)->contains('sizes="32x32"');
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
