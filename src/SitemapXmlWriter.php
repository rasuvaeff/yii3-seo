<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use DateTimeInterface;
use XMLWriter;

/**
 * Renders individual sitemap elements to XML.
 *
 * Every value that can come from an application goes through `XMLWriter`, which
 * escapes it; only the fixed document header and footer are literals, and they
 * carry no input. Elements are rendered one at a time so a caller can measure a
 * rendered entry before deciding which file it belongs to.
 *
 * @internal
 */
final class SitemapXmlWriter
{
    public const string URLSET_HEADER = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
        . ' xmlns:xhtml="http://www.w3.org/1999/xhtml"'
        . ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

    public const string URLSET_FOOTER = '</urlset>' . "\n";

    public const string SITEMAP_INDEX_HEADER = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    public const string SITEMAP_INDEX_FOOTER = '</sitemapindex>' . "\n";

    private readonly XMLWriter $writer;

    public function __construct(private readonly UrlResolver $urlResolver)
    {
        $this->writer = new XMLWriter();
        $this->writer->openMemory();
    }

    public function renderUrl(SitemapUrl $url): string
    {
        $this->writer->startElement('url');
        $this->writer->writeElement('loc', $this->urlResolver->resolve($url->getLoc()));

        $lastModified = $url->getLastModified();

        if ($lastModified !== null) {
            $this->writer->writeElement('lastmod', $lastModified->format(DateTimeInterface::ATOM));
        }

        $changeFrequency = $url->getChangeFrequency();

        if ($changeFrequency !== null) {
            $this->writer->writeElement('changefreq', $changeFrequency->value);
        }

        $priority = $url->getPriority();

        if ($priority !== null) {
            $this->writer->writeElement('priority', number_format($priority, 1, '.', ''));
        }

        foreach ($url->getAlternates() as $locale => $href) {
            $this->writer->startElement('xhtml:link');
            $this->writer->writeAttribute('rel', 'alternate');
            $this->writer->writeAttribute('hreflang', $locale);
            $this->writer->writeAttribute('href', $this->urlResolver->resolve($href));
            $this->writer->endElement();
        }

        foreach ($url->getImages() as $image) {
            $this->writer->startElement('image:image');
            $this->writer->writeElement('image:loc', $this->urlResolver->resolve($image->getLoc()));
            $this->writer->endElement();
        }

        $this->writer->endElement();

        return $this->flush();
    }

    public function renderIndexEntry(SitemapIndexEntry $entry): string
    {
        $this->writer->startElement('sitemap');
        $this->writer->writeElement('loc', $this->urlResolver->resolve($entry->getLoc()));

        $lastModified = $entry->getLastModified();

        if ($lastModified !== null) {
            $this->writer->writeElement('lastmod', $lastModified->format(DateTimeInterface::ATOM));
        }

        $this->writer->endElement();

        return $this->flush();
    }

    private function flush(): string
    {
        return $this->writer->flush() . "\n";
    }
}
