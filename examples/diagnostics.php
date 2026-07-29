<?php

declare(strict_types=1);

/**
 * Diagnostics and export: resolve metadata once, then validate it and dump the
 * same result as a normalized array. Useful in a development toolbar, a preview
 * endpoint or a CI check.
 *
 * Run:
 *   docker run --rm -v "$PWD":/app -w /app composer:2 php examples/diagnostics.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Rasuvaeff\Yii3Seo\Alternates;
use Rasuvaeff\Yii3Seo\Metadata;
use Rasuvaeff\Yii3Seo\MetadataDefaults;
use Rasuvaeff\Yii3Seo\MetadataIssue;
use Rasuvaeff\Yii3Seo\MetadataResolver;
use Rasuvaeff\Yii3Seo\MetadataValidator;
use Rasuvaeff\Yii3Seo\OgImage;
use Rasuvaeff\Yii3Seo\OpenGraph;
use Rasuvaeff\Yii3Seo\Robots;
use Rasuvaeff\Yii3Seo\Title;

$defaults = new MetadataDefaults(
    metadataBase: 'https://example.com',
    title: Title::template('%s | My Store', default: 'My Store'),
    openGraph: new OpenGraph(siteName: 'My Store'),
);

// Deliberately flawed page metadata: no description, an image without alt and
// dimensions, contradictory robots directives and og:url pointing elsewhere.
$metadata = new Metadata(
    title: 'Blue running shoes',
    robots: new Robots(['index', 'noindex']),
    alternates: new Alternates(canonical: '/products/blue-running-shoes'),
    openGraph: new OpenGraph(
        url: '/products/1',
        images: [new OgImage(url: '/og/shoes.jpg')],
    ),
);

$resolved = (new MetadataResolver())->resolve(metadata: $metadata, defaults: $defaults);
$result = (new MetadataValidator())->validate($resolved);

echo "Validation\n";
echo '  valid: ', $result->isValid() ? 'yes' : 'no', "\n";
echo '  errors: ', count($result->getErrors()), ', warnings: ', count($result->getWarnings()), "\n\n";

foreach ($result->getIssues() as $issue) {
    assert($issue instanceof MetadataIssue);
    printf("  [%-7s] %-25s %s\n", $issue->getSeverity()->value, $issue->getCode(), $issue->getMessage());
}

echo "\nExport (URLs resolved against metadataBase)\n";
echo json_encode($resolved->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
