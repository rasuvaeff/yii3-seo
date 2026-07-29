<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo\Tests;

use Rasuvaeff\Yii3Seo\MetadataIssue;
use Rasuvaeff\Yii3Seo\MetadataValidationResult;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(MetadataValidationResult::class)]
final class MetadataValidationResultTest
{
    public function separatesErrorsFromWarningsAsReindexedLists(): void
    {
        $firstWarning = MetadataIssue::warning('canonical.missing', 'Canonical URL is not set');
        $error = MetadataIssue::error('title.missing', 'Page title is empty');
        $secondWarning = MetadataIssue::warning('image.missing', 'No image');
        $result = new MetadataValidationResult([$firstWarning, $error, $secondWarning]);

        Assert::same($result->getIssues(), [$firstWarning, $error, $secondWarning]);
        Assert::same($result->getErrors(), [$error]);
        Assert::same($result->getWarnings(), [$firstWarning, $secondWarning]);
        Assert::true($result->hasIssues());
        Assert::true($result->hasErrors());
        Assert::false($result->isValid());
    }

    public function warningsAloneKeepTheResultValid(): void
    {
        $result = new MetadataValidationResult([MetadataIssue::warning('image.missing', 'No image')]);

        Assert::same($result->getErrors(), []);
        Assert::true($result->hasIssues());
        Assert::false($result->hasErrors());
        Assert::true($result->isValid());
    }

    public function emptyResultIsValid(): void
    {
        $result = new MetadataValidationResult();

        Assert::same($result->getIssues(), []);
        Assert::same($result->getErrors(), []);
        Assert::same($result->getWarnings(), []);
        Assert::false($result->hasIssues());
        Assert::false($result->hasErrors());
        Assert::true($result->isValid());
    }
}
