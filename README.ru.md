# Расуваефф/yii3-seo
[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-seo.svg)](https://packagist.org/packages/rasuvaeff/yii3-seo)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-seo.svg)](https://packagist.org/packages/rasuvaeff/yii3-seo)
[![Build](https://github.com/rasuvaeff/yii3-seo/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/yii3-seo/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/yii3-seo/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/yii3-seo/actions/workflows/static-analysis.yml)
[![Quality](https://github.com/rasuvaeff/yii3-seo/actions/workflows/code-quality.yml/badge.svg)](https://github.com/rasuvaeff/yii3-seo/actions/workflows/code-quality.yml)
[![Security](https://github.com/rasuvaeff/yii3-seo/actions/workflows/security.yml/badge.svg)](https://github.com/rasuvaeff/yii3-seo/actions/workflows/security.yml)
[![Psalm level](https://shepherd.dev/github/rasuvaeff/yii3-seo/level.svg)](https://shepherd.dev/github/rasuvaeff/yii3-seo)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-seo/php)](https://packagist.org/packages/rasuvaeff/yii3-seo)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-seo.svg)](LICENSE.md)
Типизированные SEO-метаданные в стиле Next.js для Yii3. Опишите страницу с одним декларативным объектом
 `Metadata` — шаблонами заголовков, OpenGraph, карточками Twitter, hreflang, каноническим URL-адресом
, директивами роботов, значками, проверкой и JSON-LD — и единственным экземпляром
 MetadataDefaults`, предоставляющим значения для всего сайта. Теги попадают в
 `<head>` автоматически через `WebViewRenderer`.

 > Используете помощника по программированию с искусственным интеллектом? [llms.txt](llms.txt) содержит компактную ссылку на API, готовую для вставки в контекст. @@ЛИНИЯ@@
## Требования
- PHP 8.3+
 - `yiisoft/html` ^3.13
 - `yiisoft/yii-view-renderer` ^7.4

## Установка
```bash
composer require rasuvaeff/yii3-seo
```
## Концепция
Этот API отражает API метаданных Next.js:

 | Next.js | yii3-сео |
 |---|---|
 | `экспортировать константные метаданные = { ... }` (страница) | `новые метаданные(...)` отправляются по запросу |
 | макет `метаданные` (по умолчанию) | `MetadataDefaults` в параметрах DI |
 | `title.template` / `default` / `absolute` | `Title::template()` / `Title::absolute()` |
 | `alternates.canonical` / `языки` | `Альтернативы` |
 | `openGraph` / `twitter` | `OpenGraph` + `OgImage` / `TwitterCard` |
 | `база метаданных` | `MetadataDefaults(metadataBase: ...)` |

 Значения по умолчанию объединяются с метаданными страницы: шаблон заголовка оборачивает заголовок страницы
, OpenGraph/Twitter наследует неустановленные поля, а относительные URL-адреса разрешаются
 по `metadataBase`. @@ЛИНИЯ@@
## Использование
### 1. Настройки по умолчанию для всего сайта (параметры)
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
### 2. Зарегистрируйте SeoInjection в конфигурации представления DI.
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
### 3. Подключите обработчик событий
```php
// config/common/events.php
use Rasuvaeff\Yii3Seo\SeoMetadataEvent;
use Rasuvaeff\Yii3Seo\SetSeoMetadataEventHandler;

return [
    SeoMetadataEvent::class => [[SetSeoMetadataEventHandler::class, '__invoke']],
];
```
### 4. Отправьте SeoMetadataEvent из вашего действия.
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
`og:title`/`og:description` возвращается к заголовку/описанию страницы, а
 `twitter:*` возвращается к OpenGraph — повторять их не нужно. @@ЛИНИЯ@@
### 5. Заголовок и JSON-LD в макете
`<title>` и `<script type="application/ld+json">` не поддерживаются интерфейсами внедрения
. Вставьте SeoInjection в свой макет и выполните рендеринг вручную:

```php
<!-- layout.php -->
<title><?= htmlspecialchars($seoInjection->getTitle(), ENT_QUOTES) ?></title>
<?= $seoInjection->getJsonLdHtml() ?>
```
## Публичный API
### `Метаданные`
Неизменяемый декларативный объект (все поля необязательны). Заголовок `string`
 нормализуется до `Title::of()`.

 | Поле | Тип | Рендеринг |
 |---|---|---|
 | `титул` | `строка\|Название` | `<title>` (шаблон применен) |
 | `описание` | `строка` | `<meta name="description">` |
 | `ключевые слова` | `список<строка>` | `<meta name="keywords">` |
 | `авторы` | `список<Автор>` | `<meta name="author">` + `<link rel="author">` |
 | `имя приложения`, `генератор`, `создатель`, `издатель` | `строка` | соответствие `<мета-имя>` |
 | `themeColor`, `colorScheme` | `строка` | `тема-цвет`, `цветовая схема` |
 | `роботы` | `Роботы` | `<meta name="robots">` / `googlebot` |
 | `заместители` | `Альтернативы` | ссылки канонические + hreflang |
 | `openGraph` | `ОпенГраф` | `ог:*` |
 | `твиттер` | `TwitterCard` | `твиттер:*` |
 | `значки` | `Иконки` | `<link rel="icon">` и т. д. |
 | `манифест` | `строка` | `<link rel="manifest">` |
 | `проверка` | `Верификация` | проверка `<meta>` |
 | `jsonLd` | `список<JsonLd>` | `<script type="application/ld+json">` |
 | `другое` | `list<MetaTag>` | пользовательский `<meta>` | @@ЛИНИЯ@@
### `Метаданные по умолчанию`
Значения по умолчанию для всего сайта: `metadataBase`, `title` (шаблон/по умолчанию),
 `applicationName`, `generator`, `themeColor`, `colorScheme`, `robots`,
 `openGraph`, `twitter`, `icons`, `verification`, `jsonLd`, `other`. Укажите через
 параметр `rasuvaeff/yii3-seo` → `defaults`. @@ЛИНИЯ@@
### `Название`
| Фабрика | Использование |
 |---|---|
 | `Title::of('Home')` | заголовок страницы, применен шаблон |
 | `Title::absolute('Home')` | заголовок страницы, шаблон пропущен |
 | `Title::template('%s | Acme', по умолчанию: 'Acme')` | по умолчанию: шаблон + резервный вариант | @@ЛИНИЯ@@
### `Альтернативы`
```php
new Alternates(
    canonical: '/page',
    languages: ['en' => '/en', 'en-US' => '/us', 'x-default' => '/'],
)
```
Локали соответствуют `/^(?:[a-z]{2}(?:-[A-Z]{2})?|x-default)$/`. @@ЛИНИЯ@@
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
### `Роботы`
| Фабрика/метод | Директива |
 |---|---|
 | `Роботы::index()` | `индекс, следовать` |
 | `Robots::noindex()` / `nofollow()` / `none()` / `noarchive()` | соответствующие директивы |
 | `новые роботы(['noindex', 'nosnippet'])` | индивидуальная комбинация |
 | `->withNoSnippet()` / `->withNoImageIndex()` | добавить директиву |
 | `->withMaxSnippet(-1)` / `->withMaxImagePreview('large')` / `->withMaxVideoPreview(30)` | Google `макс-*` |
 | `->withGoogleBot('noindex', ...)` | отдельный `<meta name="googlebot">` | @@ЛИНИЯ@@
### `Иконки` / `Иконка`, `Верификация`, `Автор`
```php
new Icons(icon: '/favicon.ico', shortcut: '/favicon.ico', apple: '/apple.png', other: [
    new Icon(rel: 'mask-icon', url: '/safari.svg'),
]);

new Verification(google: 'g-token', yandex: 'y-token', bing: 'b-token', other: ['me' => 'token']);

new Author(name: 'Alice', url: 'https://example.com/alice');
```
### `Метатег`
| Фабрика | Атрибут |
 |---|---|
 | `MetaTag::name(имя, содержимое)` | `name="..."` |
 | `MetaTag::property(свойство, содержимое)` | `property="..."` |
 | `MetaTag::httpEquiv(httpEquiv, content)` | `http-equiv="..."` | @@ЛИНИЯ@@
### `JsonLd`
```php
JsonLd::fromArray(['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => 'Home'])
```
Отрисовывается как `<script type="application/ld+json">` с `JSON_HEX_TAG` для предотвращения внедрения
 `</script>`. @@ЛИНИЯ@@
### `SeoInjection`
Синглтон зарегистрирован в DI. Реализует `MetaTagsInjectionInterface` +
 `LinkTagsInjectionInterface`. В конфигурации DI пакета также регистрируется перехватчик `reset` службы
, поэтому устаревшие метаданные каждого запроса очищаются между запросами в повторно используемых средах выполнения
.

 | Метод | Описание |
 |---|---|
 | `setMetadata(Метаданные)` | Установить метаданные для текущего запроса |
 | `очистить()` | Сброс (полезно при тестировании) |
 | `getTitle(): строка` | Разрешенный заголовок для `<title>` |
 | `getMetaTags(): list<Meta>` | Вызывается `WebViewRenderer` |
 | `getLinkTags(): array<Link>` | Вызывается `WebViewRenderer` |
 | `getJsonLdHtml(): строка` | Обработанные блоки JSON-LD `<script>` | @@ЛИНИЯ@@
## Безопасность
- URL-адреса, ориентированные на сканер (canonical, hreflang, og:image, og:url, twitter:image), разрешаются с помощью MetadataBase; абсолютные URL-адреса проверяются с помощью `FILTER_VALIDATE_URL`. Относительный URL-адрес без базы вызывает исключение InvalidArgumentException.
 — экранирование HTML обрабатывается `Yiisoft\Html` — без конкатенации необработанных строк.
 — JSON-LD использует JSON_HEX_TAG для предотвращения внедрения `</script>`. @@ЛИНИЯ@@
## Примеры
См. [`examples/`](examples/) для работоспособных скриптов и эскиз интеграции Yii3:
 [`examples/yii3-app.php`](examples/yii3-app.php). @@ЛИНИЯ@@
## Разработка
```bash
make install    # composer install
make build      # full gate: validate + normalize + require-checker + cs + psalm + test
make cs-fix     # fix code style
make test       # run testo
make test-coverage  # run testo with pcov coverage
make mutation       # run infection with pcov coverage
```
## Лицензия
BSD-3-пункт. См. [LICENSE.md](LICENSE.md).
