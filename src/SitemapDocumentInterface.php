<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

/**
 * An XML document that is emitted as a sequence of chunks rather than a single
 * string, so a sitemap of any size can be written to a response or a file
 * without being held in memory.
 *
 * @api
 */
interface SitemapDocumentInterface
{
    /**
     * Emits the document. Chunks are already XML-escaped and are concatenated
     * verbatim; the first chunk carries the XML declaration and the root
     * element, the last one closes it.
     *
     * A document built from a `Generator` can be emitted once. Pass an array or
     * an `IteratorAggregate` to make it re-renderable.
     *
     * @return iterable<string>
     */
    public function toChunks(): iterable;
}
