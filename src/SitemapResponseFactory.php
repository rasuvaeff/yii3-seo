<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Serves a sitemap document as a PSR-7 response.
 *
 * The document is emitted into a `php://temp` stream, which keeps small
 * sitemaps in memory and spills large ones to disk, so serving a 50 MiB sitemap
 * does not cost 50 MiB of PHP memory.
 *
 * @api
 */
final readonly class SitemapResponseFactory
{
    public const string CONTENT_TYPE = 'application/xml; charset=UTF-8';

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    public function create(SitemapDocumentInterface $document): ResponseInterface
    {
        $body = $this->streamFactory->createStreamFromFile('php://temp', 'wb+');

        foreach ($document->toChunks() as $chunk) {
            $body->write($chunk);
        }

        $body->rewind();

        return $this->responseFactory
            ->createResponse()
            ->withHeader('Content-Type', self::CONTENT_TYPE)
            ->withBody($body);
    }
}
