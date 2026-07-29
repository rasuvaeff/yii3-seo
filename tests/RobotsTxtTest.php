<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Seo\RobotsTxt;
use Rasuvaeff\Yii3Seo\RobotsTxtGroup;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(RobotsTxt::class)]
final class RobotsTxtTest
{
    public function rendersGroupsInOrderFollowedByTheSitemaps(): void
    {
        $robotsTxt = new RobotsTxt(
            groups: [
                new RobotsTxtGroup(['*'], allow: ['/admin/public/'], disallow: ['/admin/'], crawlDelay: 10),
                new RobotsTxtGroup(['BadBot', 'WorseBot'], disallow: ['/']),
            ],
            sitemaps: ['/sitemap.xml'],
            metadataBase: 'https://example.com',
        );

        Assert::same(
            $robotsTxt->toString(),
            "User-agent: *\n"
            . "Allow: /admin/public/\n"
            . "Disallow: /admin/\n"
            . "Crawl-delay: 10\n"
            . "\n"
            . "User-agent: BadBot\n"
            . "User-agent: WorseBot\n"
            . "Disallow: /\n"
            . "\n"
            . "Sitemap: https://example.com/sitemap.xml\n",
        );
    }

    public function aGroupWithoutRulesRendersTheEmptyDisallowLine(): void
    {
        Assert::same(
            (new RobotsTxt([new RobotsTxtGroup(['*'])]))->toString(),
            "User-agent: *\nDisallow:\n",
        );
    }

    public function aGroupWithOnlyAllowRulesDoesNotGetTheEmptyDisallowLine(): void
    {
        Assert::same(
            (new RobotsTxt([new RobotsTxtGroup(['*'], allow: ['/public/'])]))->toString(),
            "User-agent: *\nAllow: /public/\n",
        );
    }

    public function anEmptyDocumentRendersNothing(): void
    {
        Assert::same((new RobotsTxt())->toString(), '');
    }

    public function sitemapsAloneStillRender(): void
    {
        Assert::same(
            (new RobotsTxt(sitemaps: ['https://example.com/sitemap.xml']))->toString(),
            "Sitemap: https://example.com/sitemap.xml\n",
        );
    }

    public function everySitemapIsRendered(): void
    {
        $robotsTxt = new RobotsTxt(
            sitemaps: ['/sitemap.xml', '/news-sitemap.xml'],
            metadataBase: 'https://example.com',
        );

        Assert::same(
            $robotsTxt->toString(),
            "Sitemap: https://example.com/sitemap.xml\nSitemap: https://example.com/news-sitemap.xml\n",
        );
    }

    public function allowAllPermitsEverythingAndKeepsTheSitemaps(): void
    {
        $robotsTxt = RobotsTxt::allowAll(['/sitemap.xml'], 'https://example.com');

        Assert::same(
            $robotsTxt->toString(),
            "User-agent: *\nDisallow:\n\nSitemap: https://example.com/sitemap.xml\n",
        );
    }

    public function disallowAllBlocksEverythingAndAdvertisesNoSitemap(): void
    {
        $robotsTxt = RobotsTxt::disallowAll();

        Assert::same($robotsTxt->toString(), "User-agent: *\nDisallow: /\n");
        Assert::same($robotsTxt->getSitemaps(), []);
    }

    public function exposesGroupsAndSitemapsAsLists(): void
    {
        $group = new RobotsTxtGroup(['*']);
        $robotsTxt = new RobotsTxt([5 => $group], [7 => '/sitemap.xml'], 'https://example.com');

        Assert::same($robotsTxt->getGroups(), [$group]);
        Assert::same($robotsTxt->getSitemaps(), ['/sitemap.xml']);
    }

    public function throwsWhenARelativeSitemapHasNoBase(): void
    {
        try {
            (new RobotsTxt(sitemaps: ['/sitemap.xml']))->toString();
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('requires a metadataBase');
        }
    }
}
