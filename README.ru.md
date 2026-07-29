# rasuvaeff/yii3-seo

[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-seo.svg)](https://packagist.org/packages/rasuvaeff/yii3-seo)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-seo.svg)](https://packagist.org/packages/rasuvaeff/yii3-seo)
[![Build](https://github.com/rasuvaeff/yii3-seo/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/yii3-seo/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/yii3-seo/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/yii3-seo/actions/workflows/static-analysis.yml)
[![Quality](https://github.com/rasuvaeff/yii3-seo/actions/workflows/code-quality.yml/badge.svg)](https://github.com/rasuvaeff/yii3-seo/actions/workflows/code-quality.yml)
[![Security](https://github.com/rasuvaeff/yii3-seo/actions/workflows/security.yml/badge.svg)](https://github.com/rasuvaeff/yii3-seo/actions/workflows/security.yml)
[![Psalm level](https://shepherd.dev/github/rasuvaeff/yii3-seo/level.svg)](https://shepherd.dev/github/rasuvaeff/yii3-seo)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-seo/php)](https://packagist.org/packages/rasuvaeff/yii3-seo)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-seo.svg)](LICENSE.md)
[English version](README.md)

Типизированные SEO-метаданные, вдохновлённые Next.js и нативные для Yii3.
Опишите страницу одним декларативным объектом `Metadata` — шаблоны title,
OpenGraph, Twitter cards, hreflang, canonical URL, robots-директивы, иконки,
verification и JSON-LD — а единственный инстанс `MetadataDefaults` предоставит
значения для всего сайта. Теги попадают в `<head>` автоматически через
`WebViewRenderer`. XML-карты сайта генерируются из того же настроенного
origin-а.

> Используете AI-ассистента? В [llms.txt](llms.txt) — компактный API-справочник,
> готовый к вставке в контекст.
> Проекты с Composer-плагином [llm/skills](https://github.com/roxblnfk/skills)
> дополнительно получают agent-скилл этого пакета в `.agents/skills/`
> автоматически при установке.

## Требования

- PHP 8.3+, `ext-filter`, `ext-mbstring`, `ext-xmlwriter`
- `yiisoft/html` ^3.13 || ^4.0
- `yiisoft/view` ^12.0
- `yiisoft/yii-view-renderer` ^7.4
- `psr/http-message`, `psr/http-server-handler`, `psr/http-server-middleware` (middleware для self-canonical)
- `psr/http-factory` (ответы с картой сайта)

## Установка

```bash
composer require rasuvaeff/yii3-seo
```

## Концепция

API переносит декларативный стиль Next.js Metadata API в Yii3:

| Next.js | yii3-seo |
|---|---|
| `export const metadata = { ... }` (страница) | `new Metadata(...)`, диспатчится на запрос |
| `metadata` layout-а (defaults) | `MetadataDefaults` в DI-params |
| `title.template` / `default` / `absolute` | `Title::template()` / `Title::absolute()` |
| `alternates.canonical` / `languages` | `Alternates` |
| `openGraph` / `twitter` | `OpenGraph` + `OgImage` / `TwitterCard` |
| `metadataBase` | `MetadataDefaults(metadataBase: ...)` |

Defaults мержатся с метаданными страницы: шаблон title оборачивает title
страницы, OpenGraph/Twitter наследуют незаданные поля, а относительные URL-ы
резолвятся против `metadataBase`.

В отличие от Next.js, вложенные значения OpenGraph/Twitter мержатся по полям,
а fallback-и social title, description и image включены по умолчанию. Эти
нативные для Yii3 правила уменьшают дублирование; явные значения страницы
всегда имеют приоритет.

## Быстрый старт

Две правки конфигурации, один dispatch и две строки в layout-е.

**1. Defaults для всего сайта** — `config/common/params.php`

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
            selfCanonical: SelfCanonical::enabled(),   // опционально
        ),
    ],

    'middlewares' => [
        SelfCanonicalMiddleware::class,               // только для selfCanonical
        // ... роутер и остальной стек
    ],
];
```

**2. Добавьте injection в view renderer** — `config/common/di.php`

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

**3. Опишите страницу** — в action-е

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

Это вся интеграция. Запрос `https://example.com/products/awesome?utm_source=mail`
отрендерит:

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

`og:title`/`og:description` взялись из title и description страницы,
`twitter:*` — из OpenGraph, canonical — из пути запроса с выброшенным
tracking-параметром, а layout-у осталось только вывести `<title>` и блок JSON-LD
(шаг 4).

### Что пакет подключает сам

| Элемент | Механизм |
|---|---|
| Meta- и link-теги | `SeoInjection` через injection-интерфейсы `WebViewRenderer` (шаг 2) |
| Обработчик `SeoMetadataEvent` | `config/events-web.php`, авторегистрация через `yiisoft/config` — в приложении ничего писать не нужно |
| Параметр layout-а `$seo` | `LayoutParametersInjectionInterface`, объект в layout инжектить не нужно |
| Сброс между запросами | `config/di.php` вызывает `SeoInjection::reset()`, поэтому ничего не утекает в RoadRunner и подобных рантаймах |

`HttpApplicationRunner` читает группу `events-web` с `RecursiveMerge`, поэтому
эти слушатели складываются со слушателями приложения и других пакетов на то же
событие, а не конфликтуют.

**Апгрейд с 1.0.x:** уберите запись `SeoMetadataEvent` из собственного
`config/common/events-web.php` приложения. Слушатели складываются, а не
дедуплицируются, поэтому обработчик будет вызываться дважды на dispatch.
Сегодня это безвредно — `setMetadata()` пересчитывает тот же вход — но это
мёртвая конфигурация.

**4. Выведите title и JSON-LD** — в layout-е

У `WebViewRenderer` есть injection-интерфейсы только для meta- и link-тегов,
поэтому эти два элемента рендерятся из автоматически инжектируемого `$seo`:

```php
<!-- layout.php -->
<?php use Yiisoft\Html\Html; ?>
<title><?= Html::encode($seo->getTitle()) ?></title>
<?= $seo->getJsonLdHtml() ?>
```

## Публичный API

### `Metadata`

Иммутабельный декларативный объект (все поля опциональны). Title типа `string`
нормализуется в `Title::of()`.

| Поле | Тип | Рендерится в |
|---|---|---|
| `title` | `string\|Title` | `<title>` (с применением шаблона) |
| `description` | `string` | `<meta name="description">` |
| `keywords` | `list<string>` | `<meta name="keywords">` |
| `authors` | `list<Author>` | `<meta name="author">` + `<link rel="author">` |
| `applicationName`, `generator`, `creator`, `publisher` | `string` | соответствующие `<meta name>` |
| `themeColor`, `colorScheme` | `string` | `theme-color`, `color-scheme` |
| `robots` | `Robots` | `<meta name="robots">` / `googlebot` |
| `alternates` | `Alternates` | canonical + hreflang ссылки |
| `openGraph` | `OpenGraph` | `og:*` |
| `twitter` | `TwitterCard` | `twitter:*` |
| `icons` | `Icons` | `<link rel="icon">` и т.д. |
| `manifest` | `string` | `<link rel="manifest">` |
| `verification` | `Verification` | verification `<meta>` |
| `jsonLd` | `list<JsonLd>` | `<script type="application/ld+json">` |
| `other` | `list<MetaTag>` | кастомные `<meta>` |

### `MetadataDefaults`

Defaults для всего сайта: `metadataBase`, `title` (шаблон/default),
`applicationName`, `generator`, `themeColor`, `colorScheme`, `robots`,
`openGraph`, `twitter`, `icons`, `verification`, `jsonLd`, `other`. Задаются
через параметр `rasuvaeff/yii3-seo` → `defaults`.

### `MetadataResolver` + `ResolvedMetadata`

`MetadataResolver` — единый источник правил для defaults, шаблонов title и
social fallback-ов. Он возвращает иммутабельный `ResolvedMetadata`, который
можно анализировать независимо от Yii-рендеринга:

```php
use Rasuvaeff\Yii3Seo\MetadataResolver;

$resolved = (new MetadataResolver())->resolve(
    metadata: new Metadata(title: 'Product', description: 'Description'),
    defaults: $defaults,
);

$resolved->getTitle();       // "Product | My Store"
$resolved->getOpenGraph();   // объединённый OpenGraph с fallback title/description
$resolved->getTwitter();     // объединённая Twitter card с fallback из OpenGraph
```

Настроенные относительные crawler-facing URL остаются относительными в
`ResolvedMetadata`; при рендеринге они резолвятся относительно `metadataBase`.
Обычно приложение получает текущий результат через
`SeoInjection::getResolvedMetadata()`.

`ResolvedMetadata::toArray()` экспортирует тот же результат нормализованным
массивом — для JSON API, SPA-payload-ов, preview-инструментов и отладки:

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

| Правило | Поведение |
|---|---|
| Crawler-facing URL | Canonical, hreflang, `og:url` и изображения резолвятся относительно `metadataBase` — так же, как при рендеринге |
| Icons и manifest | Экспортируются как настроены, совпадая с отрендеренными `<link>` |
| Относительный URL без `metadataBase` | Бросает `InvalidArgumentException` — та же ошибка, что и при рендеринге |
| Пустые значения | `null` и пустые коллекции опускаются; `title` присутствует всегда |
| Defaults | `openGraph.type` по умолчанию `website`, `twitter.card` — `summary_large_image`, как в отрендеренном head |

### `MetadataValidator`

`MetadataValidator` анализирует `ResolvedMetadata` и возвращает типизированные
issue-объекты. Он никогда не бросает исключений и не меняет метаданные, поэтому
его безопасно запускать в dev-панели, на preview-странице или в CI-проверке:

```php
use Rasuvaeff\Yii3Seo\MetadataValidator;

$result = (new MetadataValidator())->validate($seo->getResolvedMetadata());

$result->isValid();      // true, если нет errors (warnings — рекомендательные)
$result->hasErrors();
$result->getErrors();    // list<MetadataIssue>
$result->getWarnings();  // list<MetadataIssue>

foreach ($result->getIssues() as $issue) {
    echo $issue->getSeverity()->value, ' ', $issue->getCode(), ': ', $issue->getMessage(), "\n";
}
// error title.missing: Page title is empty
// warning canonical.missing: Canonical URL is not set
```

Каждый `MetadataIssue` несёт `MetadataIssueSeverity` (`Error` или `Warning`),
стабильный машиночитаемый `code` для фильтрации и человекочитаемое сообщение.

| Code | Severity | Когда сообщается |
|---|---|---|
| `title.missing` | Error | Итоговый title пуст |
| `title.too_long` | Warning | Title длиннее 60 символов |
| `description.missing` | Warning | Meta description не задан |
| `description.too_short` | Warning | Description короче 50 символов |
| `description.too_long` | Warning | Description длиннее 160 символов |
| `canonical.missing` | Warning | Canonical URL не задан |
| `canonical.og_url_mismatch` | Error | Canonical URL и `og:url` резолвятся в разные URL |
| `url.unresolvable` | Error | Crawler-facing URL невалиден или относителен без `metadataBase` |
| `image.missing` | Warning | Не задано ни Open Graph, ни Twitter изображение |
| `image.alt_missing` | Warning | У Open Graph изображения нет alt |
| `image.dimensions_missing` | Warning | У Open Graph изображения нет width и height |
| `robots.conflicting` | Error | В `robots`/`googlebot` противоречивые директивы, например `index` и `noindex` |
| `other.duplicate` | Warning | Один и тот же кастомный meta-тег объявлен дважды |

Длины — рекомендательные: они никогда не делают результат невалидным.

### `Title`

| Фабрика | Назначение |
|---|---|
| `Title::of('Home')` | title страницы, шаблон применяется |
| `Title::absolute('Home')` | title страницы, шаблон обходится |
| `Title::template('%s | Acme', default: 'Acme')` | defaults: шаблон + фолбэк |

### `Alternates`

```php
new Alternates(
    canonical: '/page',
    languages: ['en' => '/en', 'en-US' => '/us', 'x-default' => '/'],
)
```

Локали матчатся по `/^(?:[a-z]{2}(?:-[A-Z]{2})?|x-default)$/`.

### `SelfCanonical` + `SelfCanonicalMiddleware`

Опциональная стратегия: canonical URL для страниц, которые не задали его сами,
выводится из `metadataBase` и текущего пути запроса.

```php
// config/common/params.php
use Rasuvaeff\Yii3Seo\MetadataDefaults;
use Rasuvaeff\Yii3Seo\SelfCanonical;

new MetadataDefaults(
    metadataBase: 'https://example.com',
    selfCanonical: SelfCanonical::enabled(),                 // выбросить все query-параметры
    // selfCanonical: SelfCanonical::keepingQuery('page'),   // оставить явный allow-list
);
```

```php
// config/common/params.php — стек middleware приложения
use Rasuvaeff\Yii3Seo\SelfCanonicalMiddleware;

'middlewares' => [
    SelfCanonicalMiddleware::class,
    // ... роутер и остальной стек
],
```

Запрос `https://example.com/products/1?page=2&utm_source=mail` отрендерит
`<link rel="canonical" href="https://example.com/products/1?page=2">`.

| Правило | Поведение |
|---|---|
| Authority запроса | Игнорируется. Читаются только path и query, поэтому запрос на другой хост, порт или схему всё равно даёт URL настроенного сайта |
| Query-параметры | По умолчанию выбрасываются. `SelfCanonical::keepingQuery(...)` оставляет явный allow-list |
| Порядок параметров | Порядок allow-list, а не запроса: `?sort=a&page=1` и `?page=1&sort=a` дают одинаковый canonical |
| Параметры-массивы | Никогда не попадают в canonical (`?tag[]=a` выбрасывается, даже если `tag` в allow-list) |
| Явный `Alternates::canonical` | Всегда побеждает; заданные страницей hreflang `languages` сохраняются |
| `metadataBase` | Обязателен — `MetadataDefaults` бросает исключение, если `selfCanonical` задан без него |
| Без middleware | Путь запроса не записывается, и стратегия ничего не делает |

`SelfCanonicalMiddleware` вызывает `SeoInjection::setRequestPath()`; порядок
относительно установки метаданных страницы значения не имеет.

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

| Фабрика / метод | Директива |
|---|---|
| `Robots::index()` | `index, follow` |
| `Robots::noindex()` / `nofollow()` / `none()` / `noarchive()` | соответствующие директивы |
| `new Robots(['noindex', 'nosnippet'])` | произвольная комбинация |
| `->withNoSnippet()` / `->withNoImageIndex()` | добавить директиву |
| `->withMaxSnippet(-1)` / `->withMaxImagePreview('large')` / `->withMaxVideoPreview(30)` | Google `max-*` |
| `->withGoogleBot('noindex', ...)` | отдельный `<meta name="googlebot">` |

### `Icons` / `Icon`, `Verification`, `Author`

```php
new Icons(icon: '/favicon.ico', shortcut: '/favicon.ico', apple: '/apple.png', other: [
    new Icon(rel: 'mask-icon', url: '/safari.svg'),
]);

new Verification(google: 'g-token', yandex: 'y-token', bing: 'b-token', other: ['me' => 'token']);

new Author(name: 'Alice', url: 'https://example.com/alice');
```

### `MetaTag`

| Фабрика | Атрибут |
|---|---|
| `MetaTag::name(name, content)` | `name="..."` |
| `MetaTag::property(property, content)` | `property="..."` |
| `MetaTag::httpEquiv(httpEquiv, content)` | `http-equiv="..."` |

### `JsonLd`

```php
JsonLd::fromArray(['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => 'Home'])
```

Рендерится как `<script type="application/ld+json">` с `JSON_HEX_TAG` для защиты
от инъекции `</script>`.

### `SeoInjection`

Singleton, регистрируемый в DI. Реализует `LayoutParametersInjectionInterface`,
`MetaTagsInjectionInterface` и `LinkTagsInjectionInterface`. DI-конфиг пакета
также регистрирует сервисный хук `reset`, поэтому устаревшие метаданные
per-request очищаются между запросами в переиспользуемых runtime-ах.

| Метод | Описание |
|---|---|
| `setMetadata(Metadata)` | Установить метаданные для текущего запроса |
| `clear()` | Сброс (полезно в тестах) |
| `getLayoutParameters(): array` | Передаёт injection в layout как `$seo` |
| `getResolvedMetadata(): ResolvedMetadata` | Полностью объединённые логические metadata |
| `getTitle(): string` | Резолвленный title для `<title>` |
| `getMetaTags(): list<Meta>` | Вызывается `WebViewRenderer`-ом |
| `getLinkTags(): array<Link>` | Вызывается `WebViewRenderer`-ом |
| `getJsonLdHtml(): string` | HTML JSON-LD `<script>`-блоков |

## Карты сайта

Sitemap-часть пакета не зависит от метаданных `<head>`: ей нужен только
источник URL-ов и тот же `metadataBase`. Пакет никогда не обходит сайт сам —
что попадает в карту, всегда решает приложение.

### `SitemapUrl`

| Аргумент | Тип | Примечания |
|---|---|---|
| `loc` | `string` | Обязателен. Абсолютный либо относительный — резолвится против `metadataBase` |
| `lastModified` | `?DateTimeImmutable` | Рендерится как `<lastmod>` в формате W3C datetime |
| `changeFrequency` | `?ChangeFrequency` | `Always`…`Never`; подсказка, которую краулер вправе игнорировать |
| `priority` | `?float` | `0.0`–`1.0`, рендерится с одним знаком после точки |
| `images` | `SitemapImage[]` | Элементы `<image:image>` |
| `alternates` | `array<string, string>` | `локаль => url`, рендерится как `<xhtml:link rel="alternate">` |

Локали подчиняются тем же правилам, что и в `Alternates`: `de`, `de-DE` или
`x-default`.

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

Именно `yield`, а не возврат массива: все потребители ниже забирают по одному
URL за раз, поэтому провайдер поверх курсора БД не материализует выборку.

### `Sitemap` и `SitemapIndex`

Оба реализуют `SitemapDocumentInterface` и отдают документ чанками:

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

Документ, построенный из `Generator`, отдаётся один раз; передайте массив,
чтобы его можно было рендерить повторно. `SitemapIndex` принимает объекты
`SitemapIndexEntry` (`loc` плюс необязательный `lastModified`) и выдаёт
`<sitemapindex>`.

### `SitemapFileExporter`

Пишет поток URL-ов на диск, соблюдая оба лимита протокола — 50 000 URL и
50 MiB без сжатия — измеряя каждую отрендеренную запись до записи в файл:

```php
$exporter = new SitemapFileExporter(
    metadataBase: 'https://example.com',
    limits: new SitemapLimits(),   // по умолчанию — максимумы протокола
    publicPath: '/',               // URL-путь, по которому отдаются файлы
);

$files = $exporter->export($provider->getUrls(), '/var/www/public');
```

Раскладка зависит только от потока URL-ов:

| URL-ы | Записанные файлы |
|---|---|
| Помещаются в один файл | `sitemap.xml` — обычный `<urlset>` |
| Не помещаются | `sitemap-1.xml` … `sitemap-N.xml` плюс индекс `sitemap.xml` |

`export()` возвращает пути записанных файлов в порядке записи. Ничего не
удаляется: chunk-файлы от прошлого, более крупного экспорта остаются на диске,
и свежий индекс на них не ссылается. Один URL, не влезающий в байтовый лимит,
бросает `InvalidArgumentException`, а не создаёт файл сверх лимита — открытый в
этот момент chunk остаётся на диске без закрывающего тега, поэтому упавший
экспорт нужно перезапускать, а не публиковать как частичный результат.

`publicPath` обязан совпадать с тем, откуда файлы реально отдаются: экспорт в
`/var/www/public/sitemaps` при `publicPath: '/'` даст индекс со ссылками на
`https://example.com/sitemap-1.xml`, которые вернут 404. Проверить это
автоматически нельзя — экспортёр не знает URL, по которому опубликован каталог.

`SitemapFileExporter` зарегистрирован в DI и наследует `metadataBase` из
настроенного `MetadataDefaults`; `publicPath` берётся из параметра
`rasuvaeff/yii3-seo` → `sitemap` → `publicPath`.

### `SitemapResponseFactory`

Отдаёт документ из роута. Тело — поток `php://temp`, поэтому карта на 50 MiB
не стоит 50 MiB памяти PHP:

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

Нужны PSR-17 `ResponseFactoryInterface` и `StreamFactoryInterface` — оба уже
есть в контейнере Yii3-приложения.

## robots.txt

`RobotsTxt` — типизированный документ, а не шаблон: каждый user agent и путь
валидируются, а значение с управляющим символом отклоняется, а не пишется в
файл, — поэтому конфигурация не может подделать лишнюю строку `Disallow: /`.

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

| Правило | Детали |
|---|---|
| Пути | Должны начинаться с `/` или `*` |
| Группа без правил | Рендерит пустую строку `Disallow:` — протокольное «ограничений нет» |
| URL-ы карт сайта | Резолвятся против `metadataBase`, как и любой другой URL для краулеров |
| Порядок | `User-agent`, `Allow`, `Disallow`, `Crawl-delay`; sitemap-ы последними |

### Как отдавать

`RobotsTxtAction` — PSR-15 handler; обе зависимости приходят из контейнера,
поэтому вся интеграция — это маршрут:

```php
Route::get('/robots.txt')->action(RobotsTxtAction::class);
```

Отдаваемый документ биндится в DI из параметров:

```php
'rasuvaeff/yii3-seo' => [
    'robotsTxt' => [
        'indexable' => $_ENV['APP_ENV'] === 'prod',   // решает приложение
        'robots' => new RobotsTxt(...),               // опционально; без него — «разрешено всё»
    ],
],
```

**Пакет никогда не смотрит на окружение сам.** При `indexable: false`
биндится `RobotsTxt::disallowAll()` — все краулеры заблокированы, карта сайта
не анонсируется, — независимо от содержимого `robots`. Ошибка здесь либо
выбросит прод из индекса, либо откроет staging, поэтому решение всегда за
приложением и всегда явное. `RobotsTxtResponseFactory` доступен напрямую, если
маршруту нужно собирать документ на каждый запрос.

### `X-Robots-Tag`

Ответы без `<head>` — сгенерированные PDF, изображения, выгрузки — несут ту же
политику в заголовке. `Robots::toHeaderValues()` возвращает по значению на
строку заголовка:

```php
foreach (Robots::noindex()->withGoogleBot('noindex', 'noimageindex')->toHeaderValues() as $value) {
    $response = $response->withAddedHeader('X-Robots-Tag', $value);
}
```

```
X-Robots-Tag: noindex
X-Robots-Tag: googlebot: noindex, noimageindex
```

## Безопасность

- URL-ы для краулеров (canonical, hreflang, `og:image`, `og:url`,
  `twitter:image`, а также все URL-ы карты сайта) резолвятся против
  `metadataBase`; абсолютные URL-ы валидируются через `FILTER_VALIDATE_URL`.
  Относительный URL без базы бросает `InvalidArgumentException`.
- HTML-экранирование выполняется `Yiisoft\Html` — без конкатенации сырых строк.
- XML-экранирование карты сайта выполняет `XMLWriter`; литералами остаются
  только фиксированные заголовок и футер документа, данных они не содержат.
- Значения `robots.txt` отклоняются, если содержат управляющий символ, — так
  конфигурация не может подделать лишнюю директиву. Индексируем ли сайт,
  никогда не угадывается по окружению: это передаёт приложение.
- JSON-LD использует `JSON_HEX_TAG` для защиты от инъекции `</script>`.

## Примеры

См. [`examples/`](examples/) — запускаемые скрипты и эскиз Yii3-интеграции:
[`examples/yii3-app.php`](examples/yii3-app.php). Генерация карты сайта и
экспорт в файлы: [`examples/sitemap.php`](examples/sitemap.php); политика
обхода: [`examples/robots-txt.php`](examples/robots-txt.php).

План типизированных structured-data builders находится в
[ROADMAP.md](ROADMAP.md).

## Разработка

```bash
make install    # composer install
make build      # полный gate: validate + normalize + require-checker + cs + psalm + test
make cs-fix     # починить стиль кода
make test       # запустить testo
make test-coverage  # запустить testo с pcov coverage
make mutation       # запустить infection с pcov coverage
```

## Лицензия

BSD-3-Clause. См. [LICENSE.md](LICENSE.md).
