<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;
use SplFileObject;

/**
 * Writes a URL stream to sitemap files, splitting at the protocol limits and
 * generating the matching sitemap index.
 *
 * The stream is consumed one URL at a time and each rendered entry is measured
 * before it is appended, so both the 50 000-URL and the 50 MiB limit are
 * enforced without holding a file in memory.
 *
 * The output layout is deterministic and depends only on the URL stream:
 *
 * - one file  — `sitemap.xml`, a plain `<urlset>`;
 * - many files — `sitemap-1.xml` … `sitemap-N.xml` plus a `sitemap.xml` index.
 *
 * Nothing is deleted: chunk files left behind by an earlier, larger export stay
 * on disk, and the freshly written index never references them.
 *
 * @api
 */
final readonly class SitemapFileExporter
{
    public const string INDEX_FILE_NAME = 'sitemap.xml';

    private const string CHUNK_FILE_NAME_FORMAT = 'sitemap-%d.xml';

    /**
     * @param string|null $metadataBase base for relative URLs, usually {@see MetadataDefaults::getMetadataBase()}
     * @param string $publicPath URL path the exported files are served under
     */
    public function __construct(
        private ?string $metadataBase = null,
        private SitemapLimits $limits = new SitemapLimits(),
        private string $publicPath = '/',
    ) {
        if (!str_starts_with($publicPath, '/')) {
            throw new InvalidArgumentException("Sitemap public path \"{$publicPath}\" must start with \"/\"");
        }
    }

    /**
     * @param iterable<SitemapUrl> $urls
     * @param string $directory an existing directory the files are written to
     * @param DateTimeImmutable|null $lastModified `lastmod` of the index entries; omitted when null
     *
     * @return list<string> the written file paths, in write order
     */
    public function export(iterable $urls, string $directory, ?DateTimeImmutable $lastModified = null): array
    {
        if (!is_dir($directory)) {
            throw new InvalidArgumentException("Sitemap directory \"{$directory}\" does not exist");
        }

        $directory = rtrim($directory, '/');
        $writer = new SitemapXmlWriter(new UrlResolver($this->metadataBase));
        $footerLength = strlen(SitemapXmlWriter::URLSET_FOOTER);
        $headerLength = strlen(SitemapXmlWriter::URLSET_HEADER);
        $maxBytes = $this->limits->getMaxBytes();
        $maxUrls = $this->limits->getMaxUrls();

        /** @var list<string> $chunkFiles */
        $chunkFiles = [];
        $file = $this->openChunk($directory, $chunkFiles);
        $bytes = $headerLength;
        $count = 0;

        foreach ($urls as $url) {
            $entry = $writer->renderUrl($url);
            $length = strlen($entry);

            if ($count >= $maxUrls || $bytes + $length + $footerLength > $maxBytes) {
                $this->closeChunk($file);
                $file = $this->openChunk($directory, $chunkFiles);
                $bytes = $headerLength;
                $count = 0;
            }

            if ($bytes + $length + $footerLength > $maxBytes) {
                throw new InvalidArgumentException(
                    "Sitemap URL \"{$url->getLoc()}\" does not fit into the configured byte limit",
                );
            }

            $this->write($file, $entry);
            $bytes += $length;
            ++$count;
        }

        $this->closeChunk($file);
        unset($file);

        return $this->finish($chunkFiles, $directory, $lastModified);
    }

    /**
     * @param list<string> $chunkFiles
     *
     * @return list<string>
     */
    private function finish(array $chunkFiles, string $directory, ?DateTimeImmutable $lastModified): array
    {
        $indexPath = "{$directory}/" . self::INDEX_FILE_NAME;

        if (count($chunkFiles) === 1) {
            $this->rename("{$directory}/{$chunkFiles[0]}", $indexPath);

            return [$indexPath];
        }

        $prefix = rtrim($this->publicPath, '/');
        $entries = array_map(
            static fn(string $name): SitemapIndexEntry => new SitemapIndexEntry("{$prefix}/{$name}", $lastModified),
            $chunkFiles,
        );

        $index = new SplFileObject($indexPath, 'wb');

        foreach ((new SitemapIndex($entries, $this->metadataBase))->toChunks() as $chunk) {
            $this->write($index, $chunk);
        }

        return [
            ...array_map(static fn(string $name): string => "{$directory}/{$name}", $chunkFiles),
            $indexPath,
        ];
    }

    /**
     * Opens the next chunk file, writes the document header and records its name.
     *
     * @param list<string> $chunkFiles
     */
    private function openChunk(string $directory, array &$chunkFiles): SplFileObject
    {
        $name = sprintf(self::CHUNK_FILE_NAME_FORMAT, count($chunkFiles) + 1);
        $chunkFiles[] = $name;
        $file = new SplFileObject("{$directory}/{$name}", 'wb');

        $this->write($file, SitemapXmlWriter::URLSET_HEADER);

        return $file;
    }

    private function write(SplFileObject $file, string $data): void
    {
        if ($file->fwrite($data) === false) {
            throw new RuntimeException("Unable to write to sitemap file \"{$file->getPathname()}\"");
        }
    }

    private function closeChunk(SplFileObject $file): void
    {
        $this->write($file, SitemapXmlWriter::URLSET_FOOTER);
    }

    private function rename(string $from, string $to): void
    {
        if (!rename($from, $to)) {
            throw new RuntimeException("Unable to rename sitemap file \"{$from}\" to \"{$to}\"");
        }
    }
}
