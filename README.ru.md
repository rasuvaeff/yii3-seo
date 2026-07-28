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

- PHP 8.3+
- `yiisoft/html` ^3.13
- `yiisoft/yii-view-renderer` ^7.4

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

## Использование

### 1. Defaults для всего сайта (params)

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

### 2. Зарегистрируйте `SeoInjection` в DI-конфиге view

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

### 3. Подключите event handler

```php
// config/common/events.php
use Rasuvaeff\Yii3Seo\SeoMetadataEvent;
use Rasuvaeff\Yii3Seo\SetSeoMetadataEventHandler;

return [
    SeoMetadataEvent::class => [[SetSeoMetadataEventHandler::class, '__invoke']],
];
```

### 4. Диспатчьте `SeoMetadataEvent` из action-а

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

`og:title`/`og:description` фолбэчатся на title/description страницы, а
`twitter:*` — на OpenGraph: дублировать их не нужно.

### 5. Title и JSON-LD в layout-е

`<title>` и `<script type="application/ld+json">` не покрыты injection
интерфейсами для meta/link. После регистрации `SeoInjection` в
`WebViewRenderer` он автоматически доступен в layout-е как `$seo`:

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
