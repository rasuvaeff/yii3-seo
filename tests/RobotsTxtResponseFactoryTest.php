<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\StreamFactory;
use Rasuvaeff\Yii3Seo\RobotsTxt;
use Rasuvaeff\Yii3Seo\RobotsTxtResponseFactory;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(RobotsTxtResponseFactory::class)]
final class RobotsTxtResponseFactoryTest
{
    public function servesTheDocumentAsPlainText(): void
    {
        $factory = new RobotsTxtResponseFactory(new ResponseFactory(), new StreamFactory());

        $response = $factory->create(RobotsTxt::disallowAll());

        Assert::same($response->getStatusCode(), 200);
        Assert::same($response->getHeaderLine('Content-Type'), 'text/plain; charset=UTF-8');
        Assert::same((string) $response->getBody(), "User-agent: *\nDisallow: /\n");
    }
}
