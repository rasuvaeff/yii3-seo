# rasuvaeff/yii3-seo

[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-seo.svg)](https://packagist.org/packages/rasuvaeff/yii3-seo)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-seo.svg)](https://packagist.org/packages/rasuvaeff/yii3-seo)
[![Build](https://github.com/rasuvaeff/yii3-seo/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/yii3-seo/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/yii3-seo/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/yii3-seo/actions/workflows/static-analysis.yml)
[![Psalm level](https://shepherd.dev/github/rasuvaeff/yii3-seo/level.svg)](https://shepherd.dev/github/rasuvaeff/yii3-seo)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-seo/php)](https://packagist.org/packages/rasuvaeff/yii3-seo)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-seo.svg)](LICENSE.md)

Next.js-style typed SEO metadata for Yii3. Describe a page with one declarative
`Metadata` object — title templates, OpenGraph, Twitter cards, hreflang,
canonical URL, robots directives, icons, verification and JSON-LD — and a
single `MetadataDefaults` instance supplies site-wide values. Tags land in
`<head>` automatically via `WebViewRenderer`.

> Using an AI coding assistant? [llms.txt](llms.txt) has a compact API reference ready to paste into context.

## Requirements

- PHP 8.3+
- `yiisoft/html` ^3.13
- `yiisoft/yii-view-renderer` ^7.4

## Installation

```bash
composer require rasuvaeff/yii3-seo
```

## Concept

The API mirrors the Next.js Metadata API:

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

## Usage

### 1. Site-wide defaults (params)

```php
// config/common/params.php
use Rasuvaeff\Yii3Seo\MetadataDefaults;
use Rasuvaeff\Yii3Seo\OpenGraph;
use Rasuvaeff\Yii3Seo\Title;
use Rasuvaeff\Yii3Seo\TwitterCard;

return [
    'rasuvaeff/yii3-seo' => [
        'defaults' => new MetadataDefaults(
            metadataBase: 'https://example.com',
            title: Title::template('%s | My Store', default: 'My Store'),
            openGraph: new OpenGraph(siteName: 'My Store', locale: 'en_US'),
            twitter: new TwitterCard(card: 'summary_large_image', site: '@mystore'),
        ),
    ],
];
```

### 2. Register `SeoInjection` in the view DI config

```php
// config/common/di.php
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

### 3. Wire the event handler

```php
// config/common/events.php
use Rasuvaeff\Yii3Seo\SeoMetadataEvent;
use Rasuvaeff\Yii3Seo\SetSeoMetadataEventHandler;

return [
    SeoMetadataEvent::class => [[SetSeoMetadataEventHandler::class, '__invoke']],
];
```

### 4. Dispatch `SeoMetadataEvent` from your action

```php
use Psr\EventDispatcher\EventDispatcherInterface;
use Rasuvaeff\Yii3Seo\Alternates;
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
                alternates: new Alternates(
                    canonical: '/products/awesome',        // resolved against metadataBase
                    languages: [
                        'en'        => '/en/products/awesome',
                        'ru'        => '/ru/products/awesome',
                        'x-default' => '/products/awesome',
                    ],
                ),
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

`og:title`/`og:description` fall back to the page title/description, and
`twitter:*` falls back to OpenGraph — no need to repeat them.

### 5. Title and JSON-LD in layout

`<title>` and `<script type="application/ld+json">` are not covered by the
injection interfaces. Inject `SeoInjection` into your layout and render manually:

```php
<!-- layout.php -->
<title><?= htmlspecialchars($seoInjection->getTitle(), ENT_QUOTES) ?></title>
<?= $seoInjection->getJsonLdHtml() ?>
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

Singleton registered in DI. Implements `MetaTagsInjectionInterface` +
`LinkTagsInjectionInterface`. The package DI config also registers a service
`reset` hook, so stale per-request metadata is cleared between requests in
reusable runtimes.

| Method | Description |
|---|---|
| `setMetadata(Metadata)` | Set metadata for the current request |
| `clear()` | Reset (useful in tests) |
| `getTitle(): string` | Resolved title for `<title>` |
| `getMetaTags(): list<Meta>` | Called by `WebViewRenderer` |
| `getLinkTags(): array<Link>` | Called by `WebViewRenderer` |
| `getJsonLdHtml(): string` | Rendered JSON-LD `<script>` blocks |

## Security

- Crawler-facing URLs (canonical, hreflang, `og:image`, `og:url`, `twitter:image`) are resolved against `metadataBase`; absolute URLs are validated with `FILTER_VALIDATE_URL`. A relative URL with no base throws `InvalidArgumentException`.
- HTML escaping is handled by `Yiisoft\Html` — no raw string concatenation.
- JSON-LD uses `JSON_HEX_TAG` to prevent `</script>` injection.

## Examples

See [`examples/`](examples/) for runnable scripts and a Yii3 integration sketch:
[`examples/yii3-app.php`](examples/yii3-app.php).

## Development

```bash
make install    # composer install
make build      # full gate: validate + normalize + require-checker + cs + psalm + test
make cs-fix     # fix code style
make test       # run phpunit
make test-coverage  # run phpunit with pcov coverage
make mutation       # run infection with pcov coverage
```

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
