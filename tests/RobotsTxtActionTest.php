<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\StreamFactory;
use Rasuvaeff\Yii3Seo\RobotsTxt;
use Rasuvaeff\Yii3Seo\RobotsTxtAction;
use Rasuvaeff\Yii3Seo\RobotsTxtGroup;
use Rasuvaeff\Yii3Seo\RobotsTxtResponseFactory;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(RobotsTxtAction::class)]
final class RobotsTxtActionTest
{
    public function servesTheConfiguredDocument(): void
    {
        $robotsTxt = new RobotsTxt([new RobotsTxtGroup(['*'], disallow: ['/admin/'])]);

        $response = $this->action($robotsTxt)->handle(new ServerRequest(uri: '/robots.txt'));

        Assert::same((string) $response->getBody(), "User-agent: *\nDisallow: /admin/\n");
        Assert::same($response->getHeaderLine('Content-Type'), 'text/plain; charset=UTF-8');
    }

    public function theAnswerDoesNotDependOnTheRequest(): void
    {
        $action = $this->action(RobotsTxt::disallowAll());

        Assert::same(
            (string) $action->handle(new ServerRequest(uri: '/robots.txt'))->getBody(),
            (string) $action->handle(new ServerRequest(uri: '/anything?x=1'))->getBody(),
        );
    }

    private function action(RobotsTxt $robotsTxt): RobotsTxtAction
    {
        return new RobotsTxtAction(
            $robotsTxt,
            new RobotsTxtResponseFactory(new ResponseFactory(), new StreamFactory()),
        );
    }
}
