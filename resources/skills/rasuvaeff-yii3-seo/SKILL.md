---
name: rasuvaeff-yii3-seo
description: >-
  Declarative SEO metadata for Yii3 with rasuvaeff/yii3-seo — Metadata,
  MetadataDefaults, MetadataResolver, ResolvedMetadata, SeoInjection,
  SeoMetadataEvent, Title templates, Alternates/hreflang, OpenGraph, TwitterCard,
  Robots, JsonLd, SelfCanonical self-canonical URLs, MetadataValidator
  diagnostics and XML sitemaps (SitemapUrl, Sitemap, SitemapIndex,
  SitemapProviderInterface, SitemapFileExporter). Use when writing, reviewing or
  debugging page titles, canonical URLs, Open Graph/Twitter cards, hreflang,
  robots directives, JSON-LD or sitemap generation in a project that has this
  package installed.
---

# rasuvaeff/yii3-seo

One immutable `Metadata` object describes a page; site-wide `MetadataDefaults`
is merged into it by `MetadataResolver`, producing `ResolvedMetadata` that is
rendered, validated and exported from the same result. Namespace
`Rasuvaeff\Yii3Seo\`.

## Safety rules — verify these on every change

1. **Never build head HTML by hand.** Meta and link tags go through
   `Yiisoft\Html\Html::meta()` / `Html::link()`; structured data goes through
   `JsonLd`, which encodes with `JSON_HEX_TAG` so a value can never terminate
   the `<script>` element. String concatenation into `<head>` is an injection.

2. **Crawler-facing URLs are anchored to `metadataBase`, never to the request
   host.** Canonical, hreflang, `og:url` and images resolve against the
   configured origin. Do not read `Host`/`$_SERVER['HTTP_HOST']` to build them —
   an attacker-controlled Host would redirect crawlers off-site. A relative URL
   with no `metadataBase` throws `InvalidArgumentException` rather than
   guessing.

3. **Do not re-register the event handler.** The package ships
   `config/events-web.php` with
   `SeoMetadataEvent => SetSeoMetadataEventHandler`. `yiisoft/config` merges
   that group recursively, so an application entry *stacks* instead of
   replacing — the handler then runs twice per dispatch.

4. **`SeoInjection` is per-request mutable state.** It is a shared singleton in
   reusable runtimes (RoadRunner). Every new per-request field must be nulled in
   `clear()`, or request N leaks into request N+1. DI already wires `reset`.

5. **Do not listen to `Yiisoft\View\Event\WebView\*`.** Those events extend
   `WebViewEvent`, which is `@internal` to Yiisoft; calling `getView()` needs a
   psalm suppression. `<title>` and JSON-LD are rendered from the `$seo` layout
   parameter instead.

6. **Never build sitemap XML by hand.** Values go through `XMLWriter`
   (`SitemapXmlWriter`), which escapes them; only the fixed document header and
   footer are literals and they carry no input. Sitemap URLs obey rule 2 as
   well — they resolve against `metadataBase`, never the request host.

## Canonical usage

```php
// config/common/params.php
'rasuvaeff/yii3-seo' => [
    'defaults' => new MetadataDefaults(
        metadataBase: 'https://example.com',
        title: Title::template('%s | My Store', default: 'My Store'),
        openGraph: new OpenGraph(siteName: 'My Store', locale: 'en_US'),
        twitter: new TwitterCard(card: 'summary_large_image', site: '@mystore'),
    ),
],
```

```php
// config/common/di.php — add SeoInjection::class to WebViewRenderer injections
// action
$this->eventDispatcher->dispatch(new SeoMetadataEvent(metadata: new Metadata(
    title: 'Awesome Product',                    // -> "Awesome Product | My Store"
    description: 'Buy the awesome product.',
    openGraph: new OpenGraph(type: 'product', images: [
        new OgImage(url: '/og/awesome.jpg', width: 1200, height: 630, alt: 'Awesome'),
    ]),
)));
```

```php
<!-- layout.php: meta and link tags are injected; these two are not -->
<title><?= Html::encode($seo->getTitle()) ?></title>
<?= $seo->getJsonLdHtml() ?>
```

`og:title`/`og:description` fall back to the page title/description and
`twitter:*` falls back to OpenGraph — do not repeat them. This cascade is the
package's own ergonomics, **not** Next.js behavior.

## Merge rules — the usual source of surprises

| Field | Behavior |
|---|---|
| `openGraph` / `twitter` | Merged **field by field** with defaults; unset page fields inherit |
| `applicationName`, `generator`, `themeColor`, `colorScheme`, `robots`, `icons`, `verification` | Page value or default, whole value |
| `jsonLd`, `other` | Defaults **and** page, concatenated |
| `keywords`, `authors`, `creator`, `publisher`, `manifest` | Page only |
| `alternates` | Page only, except that a configured `selfCanonical` fills in a missing canonical from the request path and keeps the page's `languages` |
| `title` | `Title::of()` goes through the defaults template; `Title::absolute()` bypasses it |

## Choosing the entry point

| Situation | Use |
|---|---|
| Set metadata for a page | Dispatch `SeoMetadataEvent`; `SeoInjection::setMetadata()` directly is the no-events fallback |
| Need the merged result (API, SPA payload, preview) | `SeoInjection::getResolvedMetadata()`, then `ResolvedMetadata::toArray()` |
| Canonical URL for every page without repeating it | `MetadataDefaults(selfCanonical: SelfCanonical::enabled())` + `SelfCanonicalMiddleware` in the middleware stack; requires `metadataBase` |
| A canonical URL that legitimately includes query parameters | `SelfCanonical::keepingQuery('page')` — allow-list order wins over request order, array-valued parameters are dropped |
| Check a page for SEO problems in dev or CI | `MetadataValidator::validate($resolved)` — never throws; errors vs advisory warnings |
| A tag or schema the package does not model | `MetaTag::name()/property()/httpEquiv()` and `JsonLd::fromArray()` |
| Publish an XML sitemap | Implement `SitemapProviderInterface` (yield `SitemapUrl`), then `SitemapResponseFactory::create(new Sitemap(...))` from a route, or `SitemapFileExporter::export()` to write files |
| The site has more URLs than one sitemap file may hold | `SitemapFileExporter` — it splits at 50 000 URLs / 50 MiB and writes the `sitemap.xml` index itself |

## Validation and diagnostics

`MetadataValidator` returns `MetadataValidationResult` with typed
`MetadataIssue` objects. Errors: `title.missing`, `url.unresolvable`,
`canonical.og_url_mismatch`, `robots.conflicting`. Everything else is an
advisory warning, including the suggested title (60) and description (50-160)
character lengths. `isValid()` is false only when there are errors.

## Sitemaps

`SitemapUrl` (`loc`, `lastModified`, `changeFrequency`, `priority`, `images`,
`alternates`) is the entry; documents (`Sitemap`, `SitemapIndex`) implement
`SitemapDocumentInterface::toChunks(): iterable<string>` and pull one URL at a
time, so a provider over a database cursor keeps memory bounded — return a
generator from `getUrls()`, not an array. `SitemapFileExporter` writes
`sitemap.xml` alone when everything fits and `sitemap-1.xml` … `sitemap-N.xml`
plus a `sitemap.xml` index when it does not; it deletes nothing and never
invents a `lastmod`. The package never crawls the site.

## Full API

Constructor signatures, the `Robots` directive whitelist, the `Alternates`
locale pattern, the `TwitterCard` card whitelist and the complete issue-code
table: see the package `llms.txt` in `vendor/rasuvaeff/yii3-seo/llms.txt`.
