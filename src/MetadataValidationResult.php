<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

/**
 * Findings produced by a single {@see MetadataValidator::validate()} call.
 *
 * @api
 */
final readonly class MetadataValidationResult
{
    /** @param list<MetadataIssue> $issues */
    public function __construct(private array $issues = []) {}

    /** @return list<MetadataIssue> */
    public function getIssues(): array
    {
        return $this->issues;
    }

    /** @return list<MetadataIssue> */
    public function getErrors(): array
    {
        return array_values(array_filter($this->issues, static fn(MetadataIssue $issue): bool => $issue->isError()));
    }

    /** @return list<MetadataIssue> */
    public function getWarnings(): array
    {
        return array_values(array_filter($this->issues, static fn(MetadataIssue $issue): bool => !$issue->isError()));
    }

    public function hasIssues(): bool
    {
        return $this->issues !== [];
    }

    public function hasErrors(): bool
    {
        return $this->getErrors() !== [];
    }

    /**
     * Metadata is valid when nothing was reported as an error. Warnings are
     * advisory and do not make the result invalid.
     */
    public function isValid(): bool
    {
        return !$this->hasErrors();
    }
}
