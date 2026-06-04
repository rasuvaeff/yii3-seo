<?php

declare(strict_types=1);

/**
 * Basic event-based flow: action dispatches SeoMetadataEvent, handler populates
 * SeoInjection. In a real Yii3 app the event dispatcher wires these via DI +
 * events.php. Here we call the handler directly to show the same result.
 *
 * Run:
 *   docker run --rm -v "$PWD":/app -w /app composer:2 php examples/basic.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Rasuvaeff\Yii3Seo\Metadata;
use Rasuvaeff\Yii3Seo\SeoInjection;
use Rasuvaeff\Yii3Seo\SeoMetadataEvent;
use Rasuvaeff\Yii3Seo\SetSeoMetadataEventHandler;

// This singleton is registered in DI and shared between handler and WebViewRenderer.
// The package DI config resets it between requests to avoid stale metadata leakage.
$seoInjection = new SeoInjection();

// In Yii3: wire once in config/common/events.php:
//   SeoMetadataEvent::class => [[SetSeoMetadataEventHandler::class, '__invoke']]
$handler = new SetSeoMetadataEventHandler(seoInjection: $seoInjection);

// In Yii3: the action dispatches this event via EventDispatcherInterface.
$handler(new SeoMetadataEvent(
    metadata: new Metadata(
        title: 'Home — My Site',
        description: 'Welcome to my site. Find great products and more.',
    ),
));

// WebViewRenderer calls getMetaTags() and getLinkTags() automatically.
// getTitle() and getJsonLdHtml() are rendered manually in the layout.
echo '<title>' . htmlspecialchars($seoInjection->getTitle(), ENT_QUOTES) . '</title>' . PHP_EOL;

foreach ($seoInjection->getMetaTags() as $tag) {
    echo $tag->render() . PHP_EOL;
}
