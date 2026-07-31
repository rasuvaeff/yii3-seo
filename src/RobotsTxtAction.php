<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Ready-to-route handler for `/robots.txt`. Both dependencies come from the
 * container, so wiring the route is the whole integration:
 *
 * ```php
 * Route::get('/robots.txt')->action(RobotsTxtAction::class);
 * ```
 *
 * The request is not inspected: what a crawler is told does not depend on how
 * it asked.
 *
 * @api
 */
final readonly class RobotsTxtAction implements RequestHandlerInterface
{
    public function __construct(
        private RobotsTxt $robotsTxt,
        private RobotsTxtResponseFactory $responseFactory,
    ) {}

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->create($this->robotsTxt);
    }
}
