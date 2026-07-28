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
`WebViewRenderer`.

> Используете AI-ассистента? В [llms.txt](llms.txt) — компактный API-справочник,
> готовый к вставке в контекст.

## Требования

- PHP 8.3+, `ext-filter`, `ext-mbstring`
- `yiisoft/html` ^3.13 || ^4.0
- `yiisoft/view` ^12.0
- `yiisoft/yii-view-renderer` ^7.4
- `psr/http-message`, `psr/http-server-handler`, `psr/http-server-middleware` (middleware для self-canonical)

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

## Безопасность

- URL-ы для краулеров (canonical, hreflang, `og:image`, `og:url`,
  `twitter:image`) резолвятся против `metadataBase`; абсолютные URL-ы
  валидируются через `FILTER_VALIDATE_URL`. Относительный URL без базы бросает
  `InvalidArgumentException`.
- HTML-экранирование выполняется `Yiisoft\Html` — без конкатенации сырых строк.
- JSON-LD использует `JSON_HEX_TAG` для защиты от инъекции `</script>`.

## Примеры

См. [`examples/`](examples/) — запускаемые скрипты и эскиз Yii3-интеграции:
[`examples/yii3-app.php`](examples/yii3-app.php).

План автоматических canonical URL, диагностики, sitemap, `robots.txt` и
типизированных structured-data builders находится в [ROADMAP.md](ROADMAP.md).

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
