<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

/**
 * Severity of a {@see MetadataIssue}.
 *
 * `Error` marks metadata that is broken or self-contradictory; `Warning` marks
 * advisory findings such as missing optional metadata or unusual lengths.
 *
 * @api
 */
enum MetadataIssueSeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
}
