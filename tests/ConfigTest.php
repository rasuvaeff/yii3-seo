<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use Rasuvaeff\Yii3Seo\MetadataDefaults;
use Rasuvaeff\Yii3Seo\RobotsTxt;
use Rasuvaeff\Yii3Seo\SeoInjection;
use Rasuvaeff\Yii3Seo\SeoMetadataEvent;
use Rasuvaeff\Yii3Seo\SetSeoMetadataEventHandler;
use Rasuvaeff\Yii3Seo\SitemapFileExporter;
use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Test;

/**
 * Guards `config/*.php`, which no other gate covers: php-cs-fixer scans
 * src/tests/examples, psalm scans src, and testo never loads them.
 */
#[Test]
#[CoversNothing]
final class ConfigTest
{
    public function diConfigDefinesTheInjectionAndTheEventHandler(): void
    {
        $di = $this->di();

        Assert::array($di)->hasKeys(SeoInjection::class, SetSeoMetadataEventHandler::class);
    }

    public function sitemapExporterInheritsTheMetadataBaseAndThePublicPath(): void
    {
        $definition = $this->di()[SitemapFileExporter::class];

        Assert::null($definition['__construct()']['metadataBase']);
        Assert::same($definition['__construct()']['publicPath'], '/');
    }

    public function paramsDeclareTheSitemapPublicPath(): void
    {
        Assert::same($this->params()['rasuvaeff/yii3-seo']['sitemap']['publicPath'], '/');
    }

    public function anIndexableSiteGetsAPermissiveRobotsTxt(): void
    {
        $robotsTxt = $this->di()[RobotsTxt::class];

        Assert::instanceOf($robotsTxt, RobotsTxt::class);
        Assert::same($robotsTxt->toString(), "User-agent: *\nDisallow:\n");
    }

    public function aNonIndexableSiteGetsAFullDisallow(): void
    {
        $di = $this->diWith(['rasuvaeff/yii3-seo' => ['robotsTxt' => ['indexable' => false]]]);

        Assert::same($di[RobotsTxt::class]->toString(), "User-agent: *\nDisallow: /\n");
    }

    public function aConfiguredRobotsTxtWins(): void
    {
        $configured = RobotsTxt::allowAll(['https://example.com/sitemap.xml']);
        $di = $this->diWith(['rasuvaeff/yii3-seo' => ['robotsTxt' => ['robots' => $configured]]]);

        Assert::same($di[RobotsTxt::class], $configured);
    }

    public function aNonIndexableSiteIgnoresTheConfiguredRobotsTxt(): void
    {
        $di = $this->diWith([
            'rasuvaeff/yii3-seo' => [
                'robotsTxt' => ['indexable' => false, 'robots' => RobotsTxt::allowAll()],
            ],
        ]);

        Assert::same($di[RobotsTxt::class]->toString(), "User-agent: *\nDisallow: /\n");
    }

    public function seoInjectionFallsBackToEmptyDefaultsWhenTheParameterIsUnset(): void
    {
        $definition = $this->di()[SeoInjection::class];

        Assert::array($definition)->hasKeys('__construct()', 'reset');
        Assert::instanceOf($definition['__construct()']['defaults'], MetadataDefaults::class);
        Assert::true(is_callable($definition['reset']));
    }

    public function eventsWebConfigWiresTheMetadataHandlerToItsEvent(): void
    {
        $events = $this->eventsWeb();

        Assert::same($events, [SeoMetadataEvent::class => [[SetSeoMetadataEventHandler::class, '__invoke']]]);
        Assert::true(method_exists(SetSeoMetadataEventHandler::class, '__invoke'));
    }

    public function configGroupsDoNotShareKeys(): void
    {
        Assert::same(array_intersect_key($this->di(), $this->eventsWeb()), []);
    }

    public function composerDeclaresEveryConfigGroupFile(): void
    {
        $composer = json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        Assert::same($composer['extra']['config-plugin'], [
            'di' => 'di.php',
            'events-web' => 'events-web.php',
            'params' => 'params.php',
        ]);

        foreach ($composer['extra']['config-plugin'] as $file) {
            Assert::true(is_file(dirname(__DIR__) . '/config/' . $file), "config/{$file} is missing");
        }
    }

    /** @return array<string, mixed> */
    private function di(): array
    {
        $params = $this->params();

        return require dirname(__DIR__) . '/config/di.php';
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function diWith(array $params): array
    {
        $params = array_replace_recursive($this->params(), $params);

        return require dirname(__DIR__) . '/config/di.php';
    }

    /** @return array<string, mixed> */
    private function eventsWeb(): array
    {
        return require dirname(__DIR__) . '/config/events-web.php';
    }

    /** @return array<string, mixed> */
    private function params(): array
    {
        return require dirname(__DIR__) . '/config/params.php';
    }
}
