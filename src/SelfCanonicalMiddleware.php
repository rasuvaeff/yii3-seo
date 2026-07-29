<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Records the current request path in {@see SeoInjection} so the opt-in
 * {@see SelfCanonical} policy can derive a canonical URL for pages that do not
 * declare one.
 *
 * Only the path and query string are taken from the request; the authority
 * (scheme, host, port) is ignored, so a request arriving on an alternative host
 * cannot redirect crawlers away from the configured `metadataBase`.
 *
 * Add it to the application middleware stack anywhere before the route handler.
 *
 * @api
 */
final readonly class SelfCanonicalMiddleware implements MiddlewareInterface
{
    public function __construct(private SeoInjection $seoInjection) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $query = $uri->getQuery();

        $this->seoInjection->setRequestPath($query === '' ? $uri->getPath() : "{$uri->getPath()}?{$query}");

        return $handler->handle($request);
    }
}
