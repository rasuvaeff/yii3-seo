<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

/**
 * A single finding produced by {@see MetadataValidator}.
 *
 * @api
 */
final readonly class MetadataIssue
{
    public function __construct(
        private MetadataIssueSeverity $severity,
        private string $code,
        private string $message,
    ) {}

    public static function error(string $code, string $message): self
    {
        return new self(severity: MetadataIssueSeverity::Error, code: $code, message: $message);
    }

    public static function warning(string $code, string $message): self
    {
        return new self(severity: MetadataIssueSeverity::Warning, code: $code, message: $message);
    }

    public function getSeverity(): MetadataIssueSeverity
    {
        return $this->severity;
    }

    /**
     * Stable machine-readable identifier such as `title.missing`, meant for
     * filtering and for suppressing known findings.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isError(): bool
    {
        return $this->severity === MetadataIssueSeverity::Error;
    }
}
