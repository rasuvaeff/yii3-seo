<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use Generator;

/**
 * A `<sitemapindex>` document listing the sitemap files of a site.
 *
 * A sitemap index is what a site with more than 50 000 URLs submits to search
 * engines; {@see SitemapFileExporter} produces one automatically when the URL
 * stream does not fit into a single file.
 *
 * @api
 */
final readonly class SitemapIndex implements SitemapDocumentInterface
{
    /**
     * @param iterable<SitemapIndexEntry> $entries
     * @param string|null $metadataBase base for relative URLs, usually {@see MetadataDefaults::getMetadataBase()}
     */
    public function __construct(
        private iterable $entries,
        private ?string $metadataBase = null,
    ) {}

    /** @return Generator<int, string> */
    #[\Override]
    public function toChunks(): Generator
    {
        $writer = new SitemapXmlWriter(new UrlResolver($this->metadataBase));

        yield SitemapXmlWriter::SITEMAP_INDEX_HEADER;

        foreach ($this->entries as $entry) {
            yield $writer->renderIndexEntry($entry);
        }

        yield SitemapXmlWriter::SITEMAP_INDEX_FOOTER;
    }
}
