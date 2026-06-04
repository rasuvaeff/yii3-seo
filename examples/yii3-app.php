<?php

declare(strict_types=1);

/**
 * Minimal Yii3 integration sketch for rasuvaeff/yii3-seo.
 * This file is documentation-first: it shows how params, DI, events, action and
 * layout fit together.
 *
 * Run:
 *   docker run --rm -v "$PWD":/app -w /app composer:2 php examples/yii3-app.php
 */

echo <<<'TEXT'
config/common/params.php
------------------------
<?php

declare(strict_types=1);

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

config/common/di.php
--------------------
<?php

declare(strict_types=1);

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

config/common/events.php
------------------------
<?php

declare(strict_types=1);

use Rasuvaeff\Yii3Seo\SeoMetadataEvent;
use Rasuvaeff\Yii3Seo\SetSeoMetadataEventHandler;

return [
    SeoMetadataEvent::class => [[SetSeoMetadataEventHandler::class, '__invoke']],
];

src/Action/ProductAction.php
----------------------------
<?php

declare(strict_types=1);

namespace App\Action;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3Seo\Alternates;
use Rasuvaeff\Yii3Seo\JsonLd;
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
                title: 'Awesome Product',
                description: 'Buy the awesome product at the best price.',
                alternates: new Alternates(
                    canonical: '/products/awesome-product',
                    languages: [
                        'en'        => '/en/products/awesome-product',
                        'ru'        => '/ru/products/awesome-product',
                        'x-default' => '/products/awesome-product',
                    ],
                ),
                openGraph: new OpenGraph(
                    type: 'product',
                    images: [new OgImage(url: '/og/awesome-product.jpg', width: 1200, height: 630, alt: 'Awesome')],
                ),
                jsonLd: [JsonLd::fromArray([
                    '@context' => 'https://schema.org',
                    '@type' => 'Product',
                    'name' => 'Awesome Product',
                    'offers' => ['@type' => 'Offer', 'price' => '29.99', 'priceCurrency' => 'USD'],
                ])],
            ),
        ));

        return $this->responder->render('product/view');
    }
}

templates/layout/main.php
-------------------------
<title><?= htmlspecialchars($seoInjection->getTitle(), ENT_QUOTES) ?></title>
<?= $seoInjection->getJsonLdHtml() ?>
TEXT;
