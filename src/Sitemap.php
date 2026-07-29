<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use Generator;

/**
 * A `<urlset>` sitemap document.
 *
 * URLs are pulled one at a time while the document is emitted, so a provider
 * backed by a database cursor never materialises its result set. Splitting at
 * the protocol limits is not this class's job — it renders exactly what it is
 * given; use {@see SitemapFileExporter} when the URL count is unbounded.
 *
 * @api
 */
final readonly class Sitemap implements SitemapDocumentInterface
{
    /**
     * @param iterable<SitemapUrl> $urls
     * @param string|null $metadataBase base for relative URLs, usually {@see MetadataDefaults::getMetadataBase()}
     */
    public function __construct(
        private iterable $urls,
        private ?string $metadataBase = null,
    ) {}

    /** @return Generator<int, string> */
    #[\Override]
    public function toChunks(): Generator
    {
        $writer = new SitemapXmlWriter(new UrlResolver($this->metadataBase));

        yield SitemapXmlWriter::URLSET_HEADER;

        foreach ($this->urls as $url) {
            yield $writer->renderUrl($url);
        }

        yield SitemapXmlWriter::URLSET_FOOTER;
    }
}
