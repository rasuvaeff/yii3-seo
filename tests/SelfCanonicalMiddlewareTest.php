<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rasuvaeff\Yii3Seo\MetadataDefaults;
use Rasuvaeff\Yii3Seo\SelfCanonical;
use Rasuvaeff\Yii3Seo\SelfCanonicalMiddleware;
use Rasuvaeff\Yii3Seo\SeoInjection;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(SelfCanonicalMiddleware::class)]
final class SelfCanonicalMiddlewareTest
{
    private SeoInjection $seoInjection;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->seoInjection = new SeoInjection(new MetadataDefaults(
            metadataBase: 'https://example.com',
            selfCanonical: SelfCanonical::keepingQuery('page'),
        ));
    }

    #[DataProvider('requestProvider')]
    public function recordsThePathAndQueryIgnoringTheRequestAuthority(string $uri, string $expected): void
    {
        $handler = $this->handler();
        $response = (new SelfCanonicalMiddleware($this->seoInjection))->process(
            new ServerRequest(uri: $uri),
            $handler,
        );

        Assert::instanceOf($response, ResponseInterface::class);
        Assert::same(
            $this->seoInjection->getResolvedMetadata()->getAlternates()?->getCanonical(),
            $expected,
        );
    }

    public static function requestProvider(): iterable
    {
        yield 'path only' => ['https://example.com/products/1', '/products/1'];

        yield 'foreign authority is ignored' => ['http://evil.test:8080/products/1', '/products/1'];

        yield 'allow-listed query survives' => ['https://example.com/products?page=2', '/products?page=2'];

        yield 'tracking parameters are dropped' => [
            'https://example.com/products?utm_source=mail&page=2',
            '/products?page=2',
        ];

        yield 'root path' => ['https://example.com/', '/'];
    }

    public function passesTheRequestToTheNextHandler(): void
    {
        $handler = new class implements RequestHandlerInterface {
            public ?ServerRequestInterface $handled = null;

            #[\Override]
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->handled = $request;

                return new Response();
            }
        };

        (new SelfCanonicalMiddleware($this->seoInjection))->process(
            new ServerRequest(uri: 'https://example.com/products/1'),
            $handler,
        );

        Assert::same($handler->handled?->getUri()->getPath(), '/products/1');
    }

    private function handler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            #[\Override]
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };
    }
}
