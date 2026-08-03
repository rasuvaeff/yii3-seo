# rasuvaeff/yii3-seo

[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-seo.svg)](https://packagist.org/packages/rasuvaeff/yii3-seo)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-seo.svg)](https://packagist.org/packages/rasuvaeff/yii3-seo)
[![Build](https://github.com/rasuvaeff/yii3-seo/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/yii3-seo/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/yii3-seo/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/yii3-seo/actions/workflows/static-analysis.yml)
[![Security](https://github.com/rasuvaeff/yii3-seo/actions/workflows/security.yml/badge.svg)](https://github.com/rasuvaeff/yii3-seo/actions/workflows/security.yml)
[![Psalm level](https://shepherd.dev/github/rasuvaeff/yii3-seo/level.svg)](https://shepherd.dev/github/rasuvaeff/yii3-seo)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-seo/php)](https://packagist.org/packages/rasuvaeff/yii3-seo)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-seo.svg)](LICENSE.md)
[Русская версия](README.ru.md)

Next.js-inspired, Yii3-native typed SEO metadata. Describe a page with one
declarative `Metadata` object — title templates, OpenGraph, Twitter cards,
hreflang, canonical URL, robots directives, icons, verification and JSON-LD —
and a single `MetadataDefaults` instance supplies site-wide values. Tags land
in `<head>` automatically via `WebViewRenderer`. XML sitemaps are generated from
the same configured origin.

> Using an AI coding assistant? [llms.txt](llms.txt) has a compact API reference ready to paste into context.
> Projects using the [llm/skills](https://github.com/roxblnfk/skills) Composer plugin also get this package's agent skill synced into `.agents/skills/` automatically on install.

## Requirements

- PHP 8.3+, `ext-filter`, `ext-mbstring`, `ext-xmlwriter`
- `yiisoft/html` ^3.13 || ^4.0
- `yiisoft/view` ^12.0
- `yiisoft/yii-view-renderer` ^7.4
- `psr/http-message`, `psr/http-server-handler`, `psr/http-server-middleware` (self-canonical middleware)
- `psr/http-factory` (sitemap responses)

## Installation

```bash
composer require rasuvaeff/yii3-seo
```

## Concept

The API brings the declarative style of the Next.js Metadata API to Yii3:

| Next.js | yii3-seo |
|---|---|
| `export const metadata = { ... }` (page) | `new Metadata(...)` dispatched per request |
| layout `metadata` (defaults) | `MetadataDefaults` in DI params |
| `title.template` / `default` / `absolute` | `Title::template()` / `Title::absolute()` |
| `alternates.canonical` / `languages` | `Alternates` |
| `openGraph` / `twitter` | `OpenGraph` + `OgImage` / `TwitterCard` |
| `metadataBase` | `MetadataDefaults(metadataBase: ...)` |

Defaults are merged with the page metadata: the title template wraps the page
title, OpenGraph/Twitter inherit unset fields, and relative URLs are resolved
against `metadataBase`.

Unlike Next.js, nested OpenGraph/Twitter values are merged field by field and
social title, description and image fallbacks are enabled by default. These
Yii3-native rules reduce repetition while explicit page values always win.

## Quickstart

Two configuration edits, one dispatch and two lines in the layout.

**1. Site-wide defaults** — `config/common/params.php`

```php
use Rasuvaeff\Yii3Seo\MetadataDefaults;
use Rasuvaeff\Yii3Seo\OpenGraph;
use Rasuvaeff\Yii3Seo\SelfCanonical;
use Rasuvaeff\Yii3Seo\SelfCanonicalMiddleware;
use Rasuvaeff\Yii3Seo\Title;
use Rasuvaeff\Yii3Seo\TwitterCard;

return [
    'rasuvaeff/yii3-seo' => [
        'defaults' => new MetadataDefaults(
            metadataBase: 'https://example.com',
            title: Title::template('%s | My Store', default: 'My Store'),
            openGraph: new OpenGraph(siteName: 'My Store', locale: 'en_US'),
            twitter: new TwitterCard(card: 'summary_large_image', site: '@mystore'),
            selfCanonical: SelfCanonical::enabled(),   // optional
        ),
    ],

    'middlewares' => [
        SelfCanonicalMiddleware::class,               // only for selfCanonical
        // ... router and the rest of the stack
    ],
];
```

**2. Add the injection to the view renderer** — `config/common/di.php`

```php
use Rasuvaeff\Yii3Seo\SeoInjection;
use Yiisoft\Yii\View\Renderer\CsrfViewInjection;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

return [
    WebViewRenderer::class => [
        '__construct()' => [
            'injections' => [
                CsrfViewInjection::class,
                SeoInjection::class,
            ],
        ],
    ],
];
```

**3. Describe the page** — in an action

```php
use Psr\EventDispatcher\EventDispatcherInterface;
use Rasuvaeff\Yii3Seo\Metadata;
use Rasuvaeff\Yii3Seo\OgImage;
use Rasuvaeff\Yii3Seo\OpenGraph;
use Rasuvaeff\Yii3Seo\SeoMetadataEvent;

final readonly class ProductAction
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private ProductResponder $responder,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $this->eventDispatcher->dispatch(new SeoMetadataEvent(
            metadata: new Metadata(
                title: 'Awesome Product',                 // -> "Awesome Product | My Store"
                description: 'Buy the awesome product.',
                openGraph: new OpenGraph(
                    type: 'product',
                    images: [new OgImage(url: '/og/awesome.jpg', width: 1200, height: 630, alt: 'Awesome')],
                ),
            ),
        ));

        return $this->responder->render('product/view');
    }
}
```

That is the whole integration. A request for
`https://example.com/products/awesome?utm_source=mail` renders:

```html
<title>Awesome Product | My Store</title>
<meta name="description" content="Buy the awesome product.">
<meta property="og:title" content="Awesome Product | My Store">
<meta property="og:type" content="product">
<meta property="og:description" content="Buy the awesome product.">
<meta property="og:site_name" content="My Store">
<meta property="og:locale" content="en_US">
<meta property="og:image" content="https://example.com/og/awesome.jpg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Awesome">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="@mystore">
<meta name="twitter:title" content="Awesome Product | My Store">
<meta name="twitter:description" content="Buy the awesome product.">
<meta name="twitter:image" content="https://example.com/og/awesome.jpg">
<link rel="canonical" href="https://example.com/products/awesome">
```

`og:title`/`og:description` fell back to the page title and description,
`twitter:*` fell back to OpenGraph, the canonical URL came from the request path
with the tracking parameter stripped, and the layout only had to print
`<title>` and the JSON-LD block (step 4).

### What the package wires for you

| Element | Mechanism |
|---|---|
| Meta and link tags | `SeoInjection` through `WebViewRenderer` injection interfaces (step 2) |
| `SeoMetadataEvent` handler | `config/events-web.php`, auto-registered through `yiisoft/config` — no `events-web.php` entry in the application |
| `$seo` layout parameter | `LayoutParametersInjectionInterface`, so the layout needs no object injection |
| Reset between requests | `config/di.php` wires `SeoInjection::reset()`, so nothing leaks in RoadRunner or similar runtimes |

`HttpApplicationRunner` reads the `events-web` group with `RecursiveMerge`, so
these listeners merge with the application's own and with other packages'
listeners for the same event instead of colliding.

**Upgrading from 1.0.x:** remove the `SeoMetadataEvent` entry from the
application's own `config/common/events-web.php`. Merged listeners stack rather
than deduplicate, so keeping it invokes the handler twice per dispatch. That is
harmless today — `setMetadata()` re-resolves the same input — but it is dead
configuration.

**4. Print title and JSON-LD** — in the layout

`WebViewRenderer` has injection interfaces for meta and link tags only, so these
two elements are rendered from the automatically injected `$seo` parameter:

```php
<!-- layout.php -->
<?php use Yiisoft\Html\Html; ?>
<title><?= Html::encode($seo->getTitle()) ?></title>
<?= $seo->getJsonLdHtml() ?>
```

## Public API

### `Metadata`

Immutable declarative object (all fields optional). A `string` title is
normalized to `Title::of()`.

| Field | Type | Renders |
|---|---|---|
| `title` | `string\|Title` | `<title>` (template applied) |
| `description` | `string` | `<meta name="description">` |
| `keywords` | `list<string>` | `<meta name="keywords">` |
| `authors` | `list<Author>` | `<meta name="author">` + `<link rel="author">` |
| `applicationName`, `generator`, `creator`, `publisher` | `string` | matching `<meta name>` |
| `themeColor`, `colorScheme` | `string` | `theme-color`, `color-scheme` |
| `robots` | `Robots` | `<meta name="robots">` / `googlebot` |
| `alternates` | `Alternates` | canonical + hreflang links |
| `openGraph` | `OpenGraph` | `og:*` |
| `twitter` | `TwitterCard` | `twitter:*` |
| `icons` | `Icons` | `<link rel="icon">` etc. |
| `manifest` | `string` | `<link rel="manifest">` |
| `verification` | `Verification` | verification `<meta>` |
| `jsonLd` | `list<JsonLd>` | `<script type="application/ld+json">` |
| `other` | `list<MetaTag>` | custom `<meta>` |

### `MetadataDefaults`

Site-wide defaults: `metadataBase`, `title` (template/default),
`applicationName`, `generator`, `themeColor`, `colorScheme`, `robots`,
`openGraph`, `twitter`, `icons`, `verification`, `jsonLd`, `other`. Provide via
the `rasuvaeff/yii3-seo` → `defaults` parameter.

### `MetadataResolver` + `ResolvedMetadata`

`MetadataResolver` is the single source of truth for defaults, title templates
and social fallback rules. It returns an immutable `ResolvedMetadata` that can
be inspected independently of Yii rendering:

```php
use Rasuvaeff\Yii3Seo\MetadataResolver;

$resolved = (new MetadataResolver())->resolve(
    metadata: new Metadata(title: 'Product', description: 'Description'),
    defaults: $defaults,
);

$resolved->getTitle();       // "Product | My Store"
$resolved->getOpenGraph();   // merged OpenGraph with title/description fallback
$resolved->getTwitter();     // merged Twitter card with OpenGraph fallback
```

Configured relative crawler-facing URLs remain relative in
`ResolvedMetadata`; rendering resolves them against `metadataBase`. Normally
applications obtain the current result through
`SeoInjection::getResolvedMetadata()`.

`ResolvedMetadata::toArray()` exports the same result as a normalized array for
JSON APIs, SPA payloads, preview tooling and debugging:

```php
$resolved->toArray();
// [
//     'metadataBase' => 'https://example.com',
//     'title' => 'Product | My Store',
//     'description' => 'Description',
//     'openGraph' => [
//         'title' => 'Product | My Store',
//         'type' => 'website',
//         'images' => [['url' => 'https://example.com/og.jpg', 'width' => 1200]],
//     ],
//     'twitter' => ['card' => 'summary_large_image', ...],
// ]
```

| Rule | Behavior |
|---|---|
| Crawler-facing URLs | Canonical, hreflang, `og:url` and images are resolved against `metadataBase`, exactly as rendering resolves them |
| Icons and manifest | Exported as configured, matching the rendered `<link>` tags |
| Relative URL without `metadataBase` | Throws `InvalidArgumentException`, the same failure rendering would produce |
| Empty values | `null` values and empty collections are omitted; `title` is always present |
| Defaults | `openGraph.type` defaults to `website` and `twitter.card` to `summary_large_image`, as in the rendered head |

### `MetadataValidator`

`MetadataValidator` inspects a `ResolvedMetadata` and reports typed issues. It
never throws and never modifies metadata, so it is safe to run in a development
toolbar, a preview page or a CI check:

```php
use Rasuvaeff\Yii3Seo\MetadataValidator;

$result = (new MetadataValidator())->validate($seo->getResolvedMetadata());

$result->isValid();      // true when there are no errors (warnings are advisory)
$result->hasErrors();
$result->getErrors();    // list<MetadataIssue>
$result->getWarnings();  // list<MetadataIssue>

foreach ($result->getIssues() as $issue) {
    echo $issue->getSeverity()->value, ' ', $issue->getCode(), ': ', $issue->getMessage(), "\n";
}
// error title.missing: Page title is empty
// warning canonical.missing: Canonical URL is not set
```

Each `MetadataIssue` carries a `MetadataIssueSeverity` (`Error` or `Warning`), a
stable machine-readable `code` for filtering, and a human-readable message.

| Code | Severity | Reported when |
|---|---|---|
| `title.missing` | Error | The resolved title is empty |
| `title.too_long` | Warning | The title exceeds 60 characters |
| `description.missing` | Warning | No meta description is set |
| `description.too_short` | Warning | The description is shorter than 50 characters |
| `description.too_long` | Warning | The description exceeds 160 characters |
| `canonical.missing` | Warning | No canonical URL is set |
| `canonical.og_url_mismatch` | Error | The canonical URL and `og:url` resolve to different URLs |
| `url.unresolvable` | Error | A crawler-facing URL is invalid or relative without a `metadataBase` |
| `image.missing` | Warning | Neither an Open Graph nor a Twitter image is set |
| `image.alt_missing` | Warning | An Open Graph image has no alt text |
| `image.dimensions_missing` | Warning | An Open Graph image has no width and height |
| `robots.conflicting` | Error | `robots`/`googlebot` carry contradictory directives such as `index` and `noindex` |
| `other.duplicate` | Warning | The same custom meta tag is declared twice |

Lengths are advisory: they never make the result invalid.

### `Title`

| Factory | Use |
|---|---|
| `Title::of('Home')` | page title, template applied |
| `Title::absolute('Home')` | page title, template bypassed |
| `Title::template('%s | Acme', default: 'Acme')` | defaults: template + fallback |

### `Alternates`

```php
new Alternates(
    canonical: '/page',
    languages: ['en' => '/en', 'en-US' => '/us', 'x-default' => '/'],
)
```

Locales match `/^(?:[a-z]{2}(?:-[A-Z]{2})?|x-default)$/`.

### `SelfCanonical` + `SelfCanonicalMiddleware`

Opt-in: derive the canonical URL of pages that do not declare one from
`metadataBase` plus the current request path.

```php
// config/common/params.php
use Rasuvaeff\Yii3Seo\MetadataDefaults;
use Rasuvaeff\Yii3Seo\SelfCanonical;

new MetadataDefaults(
    metadataBase: 'https://example.com',
    selfCanonical: SelfCanonical::enabled(),                 // drop every query parameter
    // selfCanonical: SelfCanonical::keepingQuery('page'),   // keep an explicit allow-list
);
```

```php
// config/common/params.php — application middleware stack
use Rasuvaeff\Yii3Seo\SelfCanonicalMiddleware;

'middlewares' => [
    SelfCanonicalMiddleware::class,
    // ... router and the rest of the stack
],
```

A request for `https://example.com/products/1?page=2&utm_source=mail` then
renders `<link rel="canonical" href="https://example.com/products/1?page=2">`.

| Rule | Behavior |
|---|---|
| Request authority | Ignored. Only the path and query are read, so a request on an alternative host, port or scheme still yields the configured site's URL |
| Query parameters | Dropped by default. `SelfCanonical::keepingQuery(...)` keeps an explicit allow-list |
| Parameter order | The allow-list order, not the request order, so `?sort=a&page=1` and `?page=1&sort=a` produce the same canonical URL |
| Array-valued parameters | Never enter a canonical URL (`?tag[]=a` is dropped even if `tag` is allow-listed) |
| Explicit `Alternates::canonical` | Always wins; hreflang `languages` declared by the page are preserved |
| `metadataBase` | Required — `MetadataDefaults` throws when `selfCanonical` is set without it |
| Without the middleware | No request path is recorded and the policy does nothing |

`SelfCanonicalMiddleware` calls `SeoInjection::setRequestPath()`; it works
regardless of whether the action sets page metadata before or after it.

### `OpenGraph` + `OgImage`

```php
new OpenGraph(
    title: null,            // falls back to Metadata title
    description: null,      // falls back to Metadata description
    type: null,             // inherits defaults; renders og:type "website" if unset everywhere
    url: '/page',           // resolved against metadataBase
    siteName: 'My Site',
    locale: 'en_US',
    images: [new OgImage(url: '/og.jpg', width: 1200, height: 630, alt: 'Alt', type: 'image/jpeg')],
)
```

### `TwitterCard`

```php
new TwitterCard(
    card: null,                     // summary | summary_large_image | app | player; inherits defaults, renders "summary_large_image" if unset everywhere
    site: '@site',
    creator: '@creator',
    title: null,                    // falls back to OpenGraph/title
    description: null,              // falls back to OpenGraph/description
    images: [],                     // falls back to OpenGraph images
)
```

### `Robots`

| Factory / method | Directive |
|---|---|
| `Robots::index()` | `index, follow` |
| `Robots::noindex()` / `nofollow()` / `none()` / `noarchive()` | matching directives |
| `new Robots(['noindex', 'nosnippet'])` | custom combination |
| `->withNoSnippet()` / `->withNoImageIndex()` | append directive |
| `->withMaxSnippet(-1)` / `->withMaxImagePreview('large')` / `->withMaxVideoPreview(30)` | Google `max-*` |
| `->withGoogleBot('noindex', ...)` | separate `<meta name="googlebot">` |

### `Icons` / `Icon`, `Verification`, `Author`

```php
new Icons(icon: '/favicon.ico', shortcut: '/favicon.ico', apple: '/apple.png', other: [
    new Icon(rel: 'mask-icon', url: '/safari.svg'),
]);

new Verification(google: 'g-token', yandex: 'y-token', bing: 'b-token', other: ['me' => 'token']);

new Author(name: 'Alice', url: 'https://example.com/alice');
```

### `MetaTag`

| Factory | Attribute |
|---|---|
| `MetaTag::name(name, content)` | `name="..."` |
| `MetaTag::property(property, content)` | `property="..."` |
| `MetaTag::httpEquiv(httpEquiv, content)` | `http-equiv="..."` |

### `JsonLd`

```php
JsonLd::fromArray(['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => 'Home'])
```

Renders as `<script type="application/ld+json">` with `JSON_HEX_TAG` to prevent
`</script>` injection.

### `SeoInjection`

Singleton registered in DI. Implements `LayoutParametersInjectionInterface`,
`MetaTagsInjectionInterface` and `LinkTagsInjectionInterface`. The package DI
config also registers a service `reset` hook, so stale per-request metadata is
cleared between requests in reusable runtimes.

| Method | Description |
|---|---|
| `setMetadata(Metadata)` | Set metadata for the current request |
| `clear()` | Reset (useful in tests) |
| `getLayoutParameters(): array` | Exposes the injection to layouts as `$seo` |
| `getResolvedMetadata(): ResolvedMetadata` | Fully merged logical metadata |
| `getTitle(): string` | Resolved title for `<title>` |
| `getMetaTags(): list<Meta>` | Called by `WebViewRenderer` |
| `getLinkTags(): array<Link>` | Called by `WebViewRenderer` |
| `getJsonLdHtml(): string` | Rendered JSON-LD `<script>` blocks |

## Sitemaps

The sitemap side of the package is independent of the head metadata: it needs
nothing but a source of URLs and the same `metadataBase`. The package never
crawls the site — the application always says what belongs in the sitemap.

### `SitemapUrl`

| Argument | Type | Notes |
|---|---|---|
| `loc` | `string` | Required. Absolute, or relative and resolved against `metadataBase` |
| `lastModified` | `?DateTimeImmutable` | Rendered as `<lastmod>` in W3C datetime format |
| `changeFrequency` | `?ChangeFrequency` | `Always`…`Never`; a hint crawlers may ignore |
| `priority` | `?float` | `0.0`–`1.0`, rendered with one decimal |
| `images` | `SitemapImage[]` | `<image:image>` entries |
| `alternates` | `array<string, string>` | `locale => url`, rendered as `<xhtml:link rel="alternate">` |

Locales use the same rules as `Alternates`: `de`, `de-DE` or `x-default`.

### `SitemapProviderInterface`

```php
final class ProductSitemapProvider implements SitemapProviderInterface
{
    public function __construct(private ProductRepository $products) {}

    public function getUrls(): iterable
    {
        foreach ($this->products->each() as $product) {
            yield new SitemapUrl(
                loc: "/products/{$product->id}",
                lastModified: $product->updatedAt,
                priority: 0.8,
            );
        }
    }
}
```

Yield instead of returning an array: every consumer below pulls one URL at a
time, so a provider backed by a database cursor never materialises its result
set.

### `Sitemap` and `SitemapIndex`

Both implement `SitemapDocumentInterface` and emit the document as chunks:

```php
$sitemap = new Sitemap($provider->getUrls(), 'https://example.com');

foreach ($sitemap->toChunks() as $chunk) {
    echo $chunk;
}
```

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<url><loc>https://example.com/products/1</loc><lastmod>2026-07-21T10:00:00+00:00</lastmod><priority>0.8</priority></url>
</urlset>
```

A document built from a `Generator` can be emitted once; pass an array to make
it re-renderable. `SitemapIndex` takes `SitemapIndexEntry` objects (`loc` plus an
optional `lastModified`) and produces a `<sitemapindex>`.

### `SitemapFileExporter`

Writes a URL stream to disk, enforcing both protocol limits — 50 000 URLs and
50 MiB uncompressed — by measuring each rendered entry before appending it:

```php
$exporter = new SitemapFileExporter(
    metadataBase: 'https://example.com',
    limits: new SitemapLimits(),   // defaults to the protocol maximums
    publicPath: '/',               // URL path the files are served under
);

$files = $exporter->export($provider->getUrls(), '/var/www/public');
```

The layout depends only on the URL stream:

| URLs | Files written |
|---|---|
| Fit into one file | `sitemap.xml` — a plain `<urlset>` |
| Do not fit | `sitemap-1.xml` … `sitemap-N.xml` plus a `sitemap.xml` index |

`export()` returns the written file paths in write order. Nothing is deleted:
chunk files left by an earlier, larger export stay on disk and the fresh index
never references them. A single URL that cannot fit into the byte limit throws
`InvalidArgumentException` rather than producing an over-limit file — the chunk
opened at that moment is left on disk without its closing tag, so treat a failed
export as one to re-run, not as a partial result to publish.

`publicPath` must match where the written files are actually served from:
exporting into `/var/www/public/sitemaps` while leaving `publicPath` at `/`
produces an index pointing at `https://example.com/sitemap-1.xml`, which 404s.
Nothing can check this for you — the exporter never sees the URL the directory
is published under.

`SitemapFileExporter` is wired in DI and inherits `metadataBase` from the
configured `MetadataDefaults`; `publicPath` comes from the
`rasuvaeff/yii3-seo` → `sitemap` → `publicPath` parameter.

### `SitemapResponseFactory`

Serves a document from a route. The body is a `php://temp` stream, so a 50 MiB
sitemap does not cost 50 MiB of PHP memory:

```php
final class SitemapAction
{
    public function __construct(
        private SitemapResponseFactory $responses,
        private ProductSitemapProvider $provider,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->responses->create(new Sitemap($this->provider->getUrls(), 'https://example.com'));
    }
}
```

It needs a PSR-17 `ResponseFactoryInterface` and `StreamFactoryInterface`, both
of which a Yii3 application already has in its container.

## robots.txt

`RobotsTxt` is a typed document, not a template: every user agent and path is
validated, and a value carrying a control character is rejected rather than
written, so configuration can never forge an extra `Disallow: /` line.

```php
$robotsTxt = new RobotsTxt(
    groups: [
        new RobotsTxtGroup(
            userAgents: ['*'],
            allow: ['/admin/public/'],
            disallow: ['/admin/', '/cart/', '*.json'],
        ),
        new RobotsTxtGroup(userAgents: ['AhrefsBot'], disallow: ['/'], crawlDelay: 10),
    ],
    sitemaps: ['/sitemap.xml'],
    metadataBase: 'https://example.com',
);

echo $robotsTxt->toString();
```

```
User-agent: *
Allow: /admin/public/
Disallow: /admin/
Disallow: /cart/
Disallow: *.json

User-agent: AhrefsBot
Disallow: /
Crawl-delay: 10

Sitemap: https://example.com/sitemap.xml
```

| Rule | Detail |
|---|---|
| Paths | Must start with `/` or `*` |
| Group without rules | Renders the empty `Disallow:` line — the protocol's "nothing is restricted" |
| Sitemap URLs | Resolved against `metadataBase`, like every other crawler-facing URL |
| Order | `User-agent`, `Allow`, `Disallow`, `Crawl-delay`; sitemaps last |

### Serving it

`RobotsTxtAction` is a PSR-15 handler; both dependencies come from the
container, so the route is the whole integration:

```php
Route::get('/robots.txt')->action(RobotsTxtAction::class);
```

The document it serves is bound in DI from parameters:

```php
'rasuvaeff/yii3-seo' => [
    'robotsTxt' => [
        'indexable' => $_ENV['APP_ENV'] === 'prod',   // the application decides
        'robots' => new RobotsTxt(...),               // optional; omit for "allow everything"
    ],
],
```

**The package never inspects the environment.** When `indexable` is `false` the
bound document is `RobotsTxt::disallowAll()` — every crawler blocked, no sitemap
advertised — regardless of what `robots` contains. A wrong guess here would
either de-index production or expose staging, so the decision is always the
application's, stated explicitly. `RobotsTxtResponseFactory` is available
directly if a route needs to build the document per request.

### `X-Robots-Tag`

Responses without a `<head>` — generated PDFs, images, exports — carry the same
policy as a header. `Robots::toHeaderValues()` returns one value per header
line:

```php
foreach (Robots::noindex()->withGoogleBot('noindex', 'noimageindex')->toHeaderValues() as $value) {
    $response = $response->withAddedHeader('X-Robots-Tag', $value);
}
```

```
X-Robots-Tag: noindex
X-Robots-Tag: googlebot: noindex, noimageindex
```

## Security

- Crawler-facing URLs (canonical, hreflang, `og:image`, `og:url`, `twitter:image`, and every sitemap URL) are resolved against `metadataBase`; absolute URLs are validated with `FILTER_VALIDATE_URL`. A relative URL with no base throws `InvalidArgumentException`.
- HTML escaping is handled by `Yiisoft\Html` — no raw string concatenation.
- Sitemap XML escaping is handled by `XMLWriter`; only the fixed document header and footer are literals, and they carry no input.
- `robots.txt` values are rejected when they contain a control character, so configuration cannot forge an extra directive line. Whether a site may be indexed is never guessed from the environment — the application passes it in.
- JSON-LD uses `JSON_HEX_TAG` to prevent `</script>` injection.

## Examples

See [`examples/`](examples/) for runnable scripts and a Yii3 integration sketch:
[`examples/yii3-app.php`](examples/yii3-app.php). Sitemap generation and file
export: [`examples/sitemap.php`](examples/sitemap.php); crawl policy:
[`examples/robots-txt.php`](examples/robots-txt.php).

See [ROADMAP.md](ROADMAP.md) for planned curated structured-data builders.

## Development

```bash
make install    # composer install
make build      # full gate: validate + normalize + require-checker + cs + psalm + test
make cs-fix     # fix code style
make test       # run testo
make test-coverage  # run testo with pcov coverage
make mutation       # run infection with pcov coverage
```

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
