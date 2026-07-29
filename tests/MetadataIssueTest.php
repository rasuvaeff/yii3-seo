<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use Rasuvaeff\Yii3Seo\MetadataIssue;
use Rasuvaeff\Yii3Seo\MetadataIssueSeverity;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(MetadataIssue::class)]
#[Covers(MetadataIssueSeverity::class)]
final class MetadataIssueTest
{
    public function errorFactoryProducesAnError(): void
    {
        $issue = MetadataIssue::error('title.missing', 'Page title is empty');

        Assert::same($issue->getSeverity(), MetadataIssueSeverity::Error);
        Assert::same($issue->getCode(), 'title.missing');
        Assert::same($issue->getMessage(), 'Page title is empty');
        Assert::true($issue->isError());
    }

    public function warningFactoryProducesAWarning(): void
    {
        $issue = MetadataIssue::warning('canonical.missing', 'Canonical URL is not set');

        Assert::same($issue->getSeverity(), MetadataIssueSeverity::Warning);
        Assert::same($issue->getCode(), 'canonical.missing');
        Assert::same($issue->getMessage(), 'Canonical URL is not set');
        Assert::false($issue->isError());
    }

    public function severityHasStableStringValues(): void
    {
        Assert::same(MetadataIssueSeverity::Error->value, 'error');
        Assert::same(MetadataIssueSeverity::Warning->value, 'warning');
    }

    public function constructorAcceptsSeverityDirectly(): void
    {
        $issue = new MetadataIssue(
            severity: MetadataIssueSeverity::Error,
            code: 'custom.code',
            message: 'Custom message',
        );

        Assert::same($issue->getSeverity(), MetadataIssueSeverity::Error);
        Assert::same($issue->getCode(), 'custom.code');
        Assert::same($issue->getMessage(), 'Custom message');
    }
}
