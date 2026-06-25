<?php
/**
 * Document Validation Service
 *
 * Pure computation backend for document quality control. Runs a fixed catalogue
 * of file-level checks (format, extension/mime mismatch, readability, PDF
 * encryption, text-layer presence) and a record-level check (metadata
 * completeness) against a file + its document record, producing a
 * `validationStatus` verdict and `validationFindings[]`.
 *
 * Following the ADR-031 calculation pattern (mirroring MetadataService): this
 * service ONLY judges. It MUST NOT write derived fields, create objects, or
 * modify files — OR's calculation engine (or the event-listener fallback)
 * invokes it and stores the result.
 *
 * Findings reference check IDs, severities, localised messages, and metadata
 * field names only — never extracted document text or entity values.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-validation-checks/specs/document-validation-checks/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCP\Files\File;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Computes document validation verdicts (pure, no side effects).
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class DocumentValidationService
{
    /**
     * Check identifiers.
     */
    public const CHECK_FORMAT_NOT_ALLOWED  = 'format-not-allowed';
    public const CHECK_EXTENSION_MIME      = 'extension-mime-mismatch';
    public const CHECK_FILE_UNREADABLE     = 'file-unreadable';
    public const CHECK_PDF_ENCRYPTED       = 'pdf-encrypted';
    public const CHECK_TEXT_LAYER_MISSING  = 'text-layer-missing';
    public const CHECK_METADATA_INCOMPLETE = 'metadata-incomplete';

    /**
     * Severity values.
     */
    public const SEVERITY_OFF      = 'off';
    public const SEVERITY_WARNING  = 'warning';
    public const SEVERITY_BLOCKING = 'blocking';

    /**
     * Verdict values.
     */
    public const STATUS_PASSED   = 'passed';
    public const STATUS_WARNINGS = 'warnings';
    public const STATUS_FAILED   = 'failed';

    /**
     * App config key for the validation profiles JSON.
     *
     * @var string
     */
    private const CONFIG_PROFILES = 'validation.profiles';

    /**
     * App config key for the minimum chars-per-page threshold.
     *
     * @var string
     */
    private const CONFIG_TEXT_LAYER_MIN = 'validation.text_layer_min_chars_per_page';

    /**
     * Default minimum extracted characters per page.
     *
     * @var integer
     */
    private const DEFAULT_TEXT_LAYER_MIN = 32;

    /**
     * The five file-level checks plus the metadata check, in catalogue order.
     *
     * @var array<int, string>
     */
    private const ALL_CHECKS = [
        self::CHECK_FORMAT_NOT_ALLOWED,
        self::CHECK_EXTENSION_MIME,
        self::CHECK_FILE_UNREADABLE,
        self::CHECK_PDF_ENCRYPTED,
        self::CHECK_TEXT_LAYER_MISSING,
        self::CHECK_METADATA_INCOMPLETE,
    ];

    /**
     * Default mime allowlist shipped with the default profile.
     *
     * @var array<int, string>
     */
    private const DEFAULT_ALLOWED_MIMES = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword',
        'application/vnd.oasis.opendocument.text',
        'text/plain',
        'text/markdown',
        'text/html',
    ];

    /**
     * Extension → expected mime prefixes used by the mismatch check.
     *
     * @var array<string, array<int, string>>
     */
    private const EXTENSION_MIME_MAP = [
        'pdf'  => ['application/pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'doc'  => ['application/msword'],
        'odt'  => ['application/vnd.oasis.opendocument.text'],
        'txt'  => ['text/plain'],
        'md'   => ['text/markdown', 'text/plain'],
        'html' => ['text/html'],
        'htm'  => ['text/html'],
    ];

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger    Logger.
     * @param IAppConfig      $appConfig App configuration.
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly IAppConfig $appConfig
    ) {

    }//end __construct()

    /**
     * Validate a file + its document record against the resolved profile.
     *
     * @param File                 $file         The file to validate.
     * @param array<string, mixed> $record       The document record (metadata).
     * @param string|null          $documentType Optional document-type hint.
     *
     * @return array{validationStatus:string, validationFindings:array<int, array<string,mixed>>}
     *
     * @spec openspec/changes/document-validation-checks/specs/document-validation-checks/spec.md
     */
    public function validate(File $file, array $record=[], ?string $documentType=null): array
    {
        $type    = ($documentType ?? (string) ($record['documentType'] ?? ''));
        $profile = $this->resolveProfile(documentType: $type);

        $findings = [];

        $mime = $this->safeMimeType(file: $file);
        $name = $this->safeName(file: $file);

        // 1. format-not-allowed.
        if ($this->checkSeverity(profile: $profile, check: self::CHECK_FORMAT_NOT_ALLOWED) !== self::SEVERITY_OFF) {
            $allowed = $profile['allowedMimes'];
            if ($mime !== '' && in_array($mime, $allowed, true) === false) {
                $findings[] = $this->finding(
                    checkId: self::CHECK_FORMAT_NOT_ALLOWED,
                    profile: $profile,
                    messageKey: 'The file format {mime} is not allowed for this document type.',
                    params: ['mime' => $mime]
                );
            }
        }

        // 2. extension-mime-mismatch.
        if ($this->checkSeverity(profile: $profile, check: self::CHECK_EXTENSION_MIME) !== self::SEVERITY_OFF) {
            if ($this->extensionMismatches(name: $name, mime: $mime) === true) {
                $findings[] = $this->finding(
                    checkId: self::CHECK_EXTENSION_MIME,
                    profile: $profile,
                    messageKey: 'The file extension does not match its detected content type.'
                );
            }
        }

        // Read content once for the readability / encryption / text-layer checks.
        $content       = null;
        $contentFailed = false;
        try {
            $content = $file->getContent();
        } catch (Throwable $e) {
            $contentFailed = true;
        }

        // 3. file-unreadable.
        if ($this->checkSeverity(profile: $profile, check: self::CHECK_FILE_UNREADABLE) !== self::SEVERITY_OFF) {
            if ($contentFailed === true || $content === null) {
                $findings[] = $this->finding(
                    checkId: self::CHECK_FILE_UNREADABLE,
                    profile: $profile,
                    messageKey: 'The file could not be read or parsed.'
                );
            }
        }

        // 4. pdf-encrypted.
        if ($this->checkSeverity(profile: $profile, check: self::CHECK_PDF_ENCRYPTED) !== self::SEVERITY_OFF) {
            if ($mime === 'application/pdf' && $content !== null && $this->isPdfEncrypted(content: $content) === true) {
                $findings[] = $this->finding(
                    checkId: self::CHECK_PDF_ENCRYPTED,
                    profile: $profile,
                    messageKey: 'The PDF is encrypted or password-protected and cannot be anonymised.'
                );
            }
        }

        // 5. text-layer-missing (page-bearing formats only: PDF here).
        if ($this->checkSeverity(profile: $profile, check: self::CHECK_TEXT_LAYER_MISSING) !== self::SEVERITY_OFF) {
            if ($mime === 'application/pdf' && $content !== null) {
                $missing = $this->textLayerMissing(content: $content);
                if ($missing === true) {
                    $finding = $this->finding(
                        checkId: self::CHECK_TEXT_LAYER_MISSING,
                        profile: $profile,
                        messageKey: 'The document has little or no extractable text; OCR may be required.'
                    );
                    $finding['suggestedAction'] = 'ocr';
                    $findings[] = $finding;
                }
            }
        }

        // 6. metadata-incomplete (one finding per missing required field).
        if ($this->checkSeverity(profile: $profile, check: self::CHECK_METADATA_INCOMPLETE) !== self::SEVERITY_OFF) {
            foreach ($profile['requiredFields'] as $field) {
                if ($this->fieldMissing(record: $record, field: (string) $field) === true) {
                    $finding          = $this->finding(
                        checkId: self::CHECK_METADATA_INCOMPLETE,
                        profile: $profile,
                        messageKey: 'Required metadata field "{field}" is missing.',
                        params: ['field' => (string) $field]
                    );
                    $finding['field'] = (string) $field;
                    $findings[]       = $finding;
                }
            }
        }

        return [
            'validationStatus'   => $this->aggregate(findings: $findings),
            'validationFindings' => $findings,
        ];

    }//end validate()

    /**
     * Resolve the validation profile for a document type, falling back to default.
     *
     * @param string $documentType The document type.
     *
     * @return array{allowedMimes:array<int,string>, requiredFields:array<int,string>, severities:array<string,string>}
     *
     * @spec openspec/changes/document-validation-checks/specs/document-validation-checks/spec.md
     */
    public function resolveProfile(string $documentType): array
    {
        $profiles = $this->loadProfiles();

        $raw = null;
        if ($documentType !== '' && isset($profiles[$documentType]) === true) {
            $raw = $profiles[$documentType];
        } else if (isset($profiles['default']) === true) {
            $raw = $profiles['default'];
        }

        $defaults = $this->defaultProfile();
        if (is_array($raw) === false) {
            return $defaults;
        }

        $severities = $defaults['severities'];
        if (isset($raw['severities']) === true && is_array($raw['severities']) === true) {
            foreach ($raw['severities'] as $check => $sev) {
                if (in_array($check, self::ALL_CHECKS, true) === true
                    && in_array($sev, [self::SEVERITY_OFF, self::SEVERITY_WARNING, self::SEVERITY_BLOCKING], true) === true
                ) {
                    $severities[$check] = $sev;
                }
            }
        }

        $allowedMimes = $defaults['allowedMimes'];
        if (isset($raw['allowedMimes']) === true && is_array($raw['allowedMimes']) === true) {
            $allowedMimes = array_values($raw['allowedMimes']);
        }

        $requiredFields = $defaults['requiredFields'];
        if (isset($raw['requiredFields']) === true && is_array($raw['requiredFields']) === true) {
            $requiredFields = array_values($raw['requiredFields']);
        }

        return [
            'allowedMimes'   => $allowedMimes,
            'requiredFields' => $requiredFields,
            'severities'     => $severities,
        ];

    }//end resolveProfile()

    /**
     * Aggregate findings into a verdict.
     *
     * @param array<int, array<string, mixed>> $findings The findings.
     *
     * @return string The verdict (passed|warnings|failed).
     *
     * @spec openspec/changes/document-validation-checks/specs/document-validation-checks/spec.md
     */
    public function aggregate(array $findings): string
    {
        $hasWarning = false;
        foreach ($findings as $finding) {
            if (($finding['severity'] ?? '') === self::SEVERITY_BLOCKING) {
                return self::STATUS_FAILED;
            }

            if (($finding['severity'] ?? '') === self::SEVERITY_WARNING) {
                $hasWarning = true;
            }
        }

        if ($hasWarning === true) {
            return self::STATUS_WARNINGS;
        }

        return self::STATUS_PASSED;

    }//end aggregate()

    /**
     * The shipped default profile (every check warn-only).
     *
     * @return array{allowedMimes:array<int,string>, requiredFields:array<int,string>, severities:array<string,string>}
     */
    private function defaultProfile(): array
    {
        $severities = [];
        foreach (self::ALL_CHECKS as $check) {
            $severities[$check] = self::SEVERITY_WARNING;
        }

        return [
            'allowedMimes'   => self::DEFAULT_ALLOWED_MIMES,
            'requiredFields' => [],
            'severities'     => $severities,
        ];

    }//end defaultProfile()

    /**
     * Load and decode the configured profiles JSON.
     *
     * @return array<string, mixed> The decoded profiles (empty on error).
     */
    private function loadProfiles(): array
    {
        $raw = $this->appConfig->getValueString('docudesk', self::CONFIG_PROFILES, '');
        if ($raw === '') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $this->logger->warning('Invalid docudesk.validation.profiles JSON; using defaults.');
            return [];
        }

        if (is_array($decoded) === false) {
            return [];
        }

        return $decoded;

    }//end loadProfiles()

    /**
     * Resolve the severity for a check under a profile.
     *
     * @param array<string, mixed> $profile The resolved profile.
     * @param string               $check   The check id.
     *
     * @return string The severity.
     */
    private function checkSeverity(array $profile, string $check): string
    {
        return (string) ($profile['severities'][$check] ?? self::SEVERITY_WARNING);

    }//end checkSeverity()

    /**
     * Build a finding entry.
     *
     * @param string               $checkId    The check id.
     * @param array<string, mixed> $profile    The resolved profile.
     * @param string               $messageKey The English message source (translated by the UI).
     * @param array<string, mixed> $params     Message placeholder params (non-content).
     *
     * @return array<string, mixed> The finding.
     */
    private function finding(string $checkId, array $profile, string $messageKey, array $params=[]): array
    {
        return [
            'checkId'  => $checkId,
            'severity' => $this->checkSeverity(profile: $profile, check: $checkId),
            'message'  => $messageKey,
            'params'   => $params,
        ];

    }//end finding()

    /**
     * Whether a required field is absent or empty on the record.
     *
     * @param array<string, mixed> $record The record.
     * @param string               $field  The field name.
     *
     * @return bool True when missing/empty.
     */
    private function fieldMissing(array $record, string $field): bool
    {
        if (array_key_exists($field, $record) === false) {
            return true;
        }

        $value = $record[$field];
        if ($value === null || $value === '' || $value === []) {
            return true;
        }

        return false;

    }//end fieldMissing()

    /**
     * Whether the file extension contradicts the detected mime type.
     *
     * @param string $name The file name.
     * @param string $mime The detected mime type.
     *
     * @return bool True when a known extension maps to a different mime.
     */
    private function extensionMismatches(string $name, string $mime): bool
    {
        if ($mime === '') {
            return false;
        }

        $dot = strrpos($name, '.');
        if ($dot === false) {
            return false;
        }

        $ext = strtolower(substr($name, ($dot + 1)));
        if (isset(self::EXTENSION_MIME_MAP[$ext]) === false) {
            return false;
        }

        return in_array($mime, self::EXTENSION_MIME_MAP[$ext], true) === false;

    }//end extensionMismatches()

    /**
     * Heuristic: whether a PDF byte stream is encrypted.
     *
     * Looks for an `/Encrypt` entry in the trailer, which a non-encrypted PDF
     * does not carry. Cheap and parser-free.
     *
     * @param string $content The PDF bytes.
     *
     * @return bool True when encrypted.
     */
    private function isPdfEncrypted(string $content): bool
    {
        if (str_starts_with($content, '%PDF') === false) {
            return false;
        }

        return str_contains($content, '/Encrypt');

    }//end isPdfEncrypted()

    /**
     * Heuristic: whether a PDF lacks a usable text layer.
     *
     * Counts PDF page objects (`/Type /Page`) and the text-show operators
     * (`Tj`/`TJ`); when the average extractable signal per page falls below the
     * configured threshold, the text layer is considered missing (scan-only).
     *
     * @param string $content The PDF bytes.
     *
     * @return bool True when the text layer is missing.
     */
    private function textLayerMissing(string $content): bool
    {
        if (str_starts_with($content, '%PDF') === false) {
            return false;
        }

        $pages = preg_match_all('/\/Type\s*\/Page[^s]/', $content);
        if ($pages === false || $pages === 0) {
            $pages = 1;
        }

        // Count text-show operators as a proxy for extractable characters.
        $tj = preg_match_all('/\b(Tj|TJ)\b/', $content);
        if ($tj === false) {
            $tj = 0;
        }

        // Approximate extractable signal per page; ~1 operator ≈ a text run.
        $perPage = ($tj * 8) / $pages;

        return $perPage < $this->getTextLayerMin();

    }//end textLayerMissing()

    /**
     * Read the configured minimum chars-per-page threshold.
     *
     * @return int The threshold.
     */
    private function getTextLayerMin(): int
    {
        $value = $this->appConfig->getValueInt('docudesk', self::CONFIG_TEXT_LAYER_MIN, self::DEFAULT_TEXT_LAYER_MIN);
        if ($value <= 0) {
            return self::DEFAULT_TEXT_LAYER_MIN;
        }

        return $value;

    }//end getTextLayerMin()

    /**
     * Read a file's mime type defensively.
     *
     * @param File $file The file.
     *
     * @return string The mime type or ''.
     */
    private function safeMimeType(File $file): string
    {
        try {
            return (string) $file->getMimeType();
        } catch (Throwable $e) {
            return '';
        }

    }//end safeMimeType()

    /**
     * Read a file's name defensively.
     *
     * @param File $file The file.
     *
     * @return string The name or ''.
     */
    private function safeName(File $file): string
    {
        try {
            return (string) $file->getName();
        } catch (Throwable $e) {
            return '';
        }

    }//end safeName()
}//end class
