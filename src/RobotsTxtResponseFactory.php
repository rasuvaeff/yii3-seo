<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Serves a {@see RobotsTxt} document as a PSR-7 response.
 *
 * @api
 */
final readonly class RobotsTxtResponseFactory
{
    public const string CONTENT_TYPE = 'text/plain; charset=UTF-8';

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    public function create(RobotsTxt $robotsTxt): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse()
            ->withHeader('Content-Type', self::CONTENT_TYPE)
            ->withBody($this->streamFactory->createStream($robotsTxt->toString()));
    }
}
