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
 * @spec openspec/specs/document-validation-checks/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCA\DocuDesk\Service\Validation\DocumentFileInspector;
use OCA\DocuDesk\Service\Validation\ValidationProfileResolver;
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
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */
class DocumentValidationService {
	/**
	 * Check identifiers.
	 */
	public const CHECK_FORMAT_NOT_ALLOWED = 'format-not-allowed';
	public const CHECK_EXTENSION_MIME = 'extension-mime-mismatch';
	public const CHECK_FILE_UNREADABLE = 'file-unreadable';
	public const CHECK_PDF_ENCRYPTED = 'pdf-encrypted';
	public const CHECK_TEXT_LAYER_MISSING = 'text-layer-missing';
	public const CHECK_METADATA_INCOMPLETE = 'metadata-incomplete';

	/**
	 * Severity values.
	 */
	public const SEVERITY_OFF = 'off';
	public const SEVERITY_WARNING = 'warning';
	public const SEVERITY_BLOCKING = 'blocking';

	/**
	 * Verdict values.
	 */
	public const STATUS_PASSED = 'passed';
	public const STATUS_WARNINGS = 'warnings';
	public const STATUS_FAILED = 'failed';

	/**
	 * Resolves the effective profile for a document type.
	 *
	 * @var ValidationProfileResolver
	 */
	private readonly ValidationProfileResolver $profiles;

	/**
	 * Runs the file-level probes the checks are built on.
	 *
	 * @var DocumentFileInspector
	 */
	private readonly DocumentFileInspector $inspector;

	/**
	 * Constructor.
	 *
	 * The two collaborators are composed here rather than injected so the
	 * constructor signature (and therefore the DI wiring) stays unchanged.
	 * The logger and app config are consumed only by those collaborators, so
	 * they are not retained as properties.
	 *
	 * @param LoggerInterface $logger Logger.
	 * @param IAppConfig $appConfig App configuration.
	 *
	 * @return void
	 */
	public function __construct(LoggerInterface $logger, IAppConfig $appConfig) {
		$this->profiles = new ValidationProfileResolver(logger: $logger, appConfig: $appConfig);
		$this->inspector = new DocumentFileInspector(appConfig: $appConfig);

	}//end __construct()

	/**
	 * Validate a file + its document record against the resolved profile.
	 *
	 * @param File $file The file to validate.
	 * @param array<string, mixed> $record The document record (metadata).
	 * @param string|null $documentType Optional document-type hint.
	 *
	 * @return array{validationStatus:string, validationFindings:array<int, array<string,mixed>>}
	 *
	 * @spec openspec/specs/document-validation-checks/spec.md
	 */
	public function validate(File $file, array $record = [], ?string $documentType = null): array {
		$profile = $this->resolveProfile(documentType: ($documentType ?? (string)($record['documentType'] ?? '')));

		$mime = $this->inspector->safeMimeType(file: $file);
		$name = $this->inspector->safeName(file: $file);

		// Read content once for the readability / encryption / text-layer checks.
		$read = $this->readContent(file: $file);

		$findings = array_merge(
			$this->formatFindings(profile: $profile, mime: $mime),
			$this->extensionFindings(profile: $profile, name: $name, mime: $mime),
			$this->readabilityFindings(profile: $profile, contentFailed: $read['failed']),
			$this->encryptionFindings(profile: $profile, mime: $mime, content: $read['content']),
			$this->textLayerFindings(profile: $profile, mime: $mime, content: $read['content']),
			$this->metadataFindings(profile: $profile, record: $record)
		);

		return [
			'validationStatus' => $this->aggregate(findings: $findings),
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
	 * @spec openspec/specs/document-validation-checks/spec.md
	 */
	public function resolveProfile(string $documentType): array {
		return $this->profiles->resolve(documentType: $documentType);
	}//end resolveProfile()

	/**
	 * Aggregate findings into a verdict.
	 *
	 * @param array<int, array<string, mixed>> $findings The findings.
	 *
	 * @return string The verdict (passed|warnings|failed).
	 *
	 * @spec openspec/specs/document-validation-checks/spec.md
	 */
	public function aggregate(array $findings): string {
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
	 * Read a file's bytes once, recording whether the read failed.
	 *
	 * @param File $file The file to read.
	 *
	 * @return array{content: mixed, failed: bool} The bytes (or '' on failure)
	 *                                             and the failure flag.
	 */
	private function readContent(File $file): array {
		try {
			return [
				'content' => $file->getContent(),
				'failed' => false,
			];
		} catch (Throwable $e) {
			return [
				'content' => '',
				'failed' => true,
			];
		}

	}//end readContent()

	/**
	 * Check 1 — the file format is not on the profile's allowlist.
	 *
	 * @param array<string, mixed> $profile The resolved profile.
	 * @param string $mime The detected mime type.
	 *
	 * @return array<int, array<string, mixed>> Zero or one finding.
	 */
	private function formatFindings(array $profile, string $mime): array {
		if ($this->checkSeverity(profile: $profile, check: self::CHECK_FORMAT_NOT_ALLOWED) === self::SEVERITY_OFF) {
			return [];
		}

		if ($mime === '' || in_array($mime, $profile['allowedMimes'], true) === true) {
			return [];
		}

		return [
			$this->finding(
				checkId: self::CHECK_FORMAT_NOT_ALLOWED,
				profile: $profile,
				messageKey: 'The file format {mime} is not allowed for this document type.',
				params: ['mime' => $mime]
			),
		];

	}//end formatFindings()

	/**
	 * Check 2 — the file extension contradicts the detected content type.
	 *
	 * @param array<string, mixed> $profile The resolved profile.
	 * @param string $name The file name.
	 * @param string $mime The detected mime type.
	 *
	 * @return array<int, array<string, mixed>> Zero or one finding.
	 */
	private function extensionFindings(array $profile, string $name, string $mime): array {
		if ($this->checkSeverity(profile: $profile, check: self::CHECK_EXTENSION_MIME) === self::SEVERITY_OFF) {
			return [];
		}

		if ($this->inspector->extensionMismatches(name: $name, mime: $mime) === false) {
			return [];
		}

		return [
			$this->finding(
				checkId: self::CHECK_EXTENSION_MIME,
				profile: $profile,
				messageKey: 'The file extension does not match its detected content type.'
			),
		];

	}//end extensionFindings()

	/**
	 * Check 3 — the file could not be read or parsed.
	 *
	 * @param array<string, mixed> $profile The resolved profile.
	 * @param bool $contentFailed Whether the content read failed.
	 *
	 * @return array<int, array<string, mixed>> Zero or one finding.
	 */
	private function readabilityFindings(array $profile, bool $contentFailed): array {
		if ($this->checkSeverity(profile: $profile, check: self::CHECK_FILE_UNREADABLE) === self::SEVERITY_OFF) {
			return [];
		}

		if ($contentFailed === false) {
			return [];
		}

		return [
			$this->finding(
				checkId: self::CHECK_FILE_UNREADABLE,
				profile: $profile,
				messageKey: 'The file could not be read or parsed.'
			),
		];

	}//end readabilityFindings()

	/**
	 * Check 4 — the PDF is encrypted or password-protected.
	 *
	 * @param array<string, mixed> $profile The resolved profile.
	 * @param string $mime The detected mime type.
	 * @param mixed $content The file bytes.
	 *
	 * @return array<int, array<string, mixed>> Zero or one finding.
	 */
	private function encryptionFindings(array $profile, string $mime, mixed $content): array {
		if ($this->checkSeverity(profile: $profile, check: self::CHECK_PDF_ENCRYPTED) === self::SEVERITY_OFF) {
			return [];
		}

		if ($mime !== 'application/pdf' || $content === null) {
			return [];
		}

		if ($this->inspector->isPdfEncrypted(content: $content) === false) {
			return [];
		}

		return [
			$this->finding(
				checkId: self::CHECK_PDF_ENCRYPTED,
				profile: $profile,
				messageKey: 'The PDF is encrypted or password-protected and cannot be anonymised.'
			),
		];

	}//end encryptionFindings()

	/**
	 * Check 5 — the document has little or no extractable text (page-bearing
	 * formats only: PDF here).
	 *
	 * @param array<string, mixed> $profile The resolved profile.
	 * @param string $mime The detected mime type.
	 * @param mixed $content The file bytes.
	 *
	 * @return array<int, array<string, mixed>> Zero or one finding.
	 */
	private function textLayerFindings(array $profile, string $mime, mixed $content): array {
		if ($this->checkSeverity(profile: $profile, check: self::CHECK_TEXT_LAYER_MISSING) === self::SEVERITY_OFF) {
			return [];
		}

		if ($mime !== 'application/pdf' || $content === null) {
			return [];
		}

		if ($this->inspector->textLayerMissing(content: $content) === false) {
			return [];
		}

		$finding = $this->finding(
			checkId: self::CHECK_TEXT_LAYER_MISSING,
			profile: $profile,
			messageKey: 'The document has little or no extractable text; OCR may be required.'
		);
		$finding['suggestedAction'] = 'ocr';

		return [$finding];
	}//end textLayerFindings()

	/**
	 * Check 6 — required metadata fields are missing (one finding per field).
	 *
	 * @param array<string, mixed> $profile The resolved profile.
	 * @param array<string, mixed> $record The document record.
	 *
	 * @return array<int, array<string, mixed>> Zero or more findings.
	 */
	private function metadataFindings(array $profile, array $record): array {
		if ($this->checkSeverity(profile: $profile, check: self::CHECK_METADATA_INCOMPLETE) === self::SEVERITY_OFF) {
			return [];
		}

		$findings = [];
		foreach ($profile['requiredFields'] as $field) {
			if ($this->fieldMissing(record: $record, field: (string)$field) === true) {
				$finding = $this->finding(
					checkId: self::CHECK_METADATA_INCOMPLETE,
					profile: $profile,
					messageKey: 'Required metadata field "{field}" is missing.',
					params: ['field' => (string)$field]
				);
				$finding['field'] = (string)$field;
				$findings[] = $finding;
			}
		}

		return $findings;
	}//end metadataFindings()

	/**
	 * Resolve the severity for a check under a profile.
	 *
	 * @param array<string, mixed> $profile The resolved profile.
	 * @param string $check The check id.
	 *
	 * @return string The severity.
	 */
	private function checkSeverity(array $profile, string $check): string {
		return (string)($profile['severities'][$check] ?? self::SEVERITY_WARNING);
	}//end checkSeverity()

	/**
	 * Build a finding entry.
	 *
	 * @param string $checkId The check id.
	 * @param array<string, mixed> $profile The resolved profile.
	 * @param string $messageKey The English message source (translated by the UI).
	 * @param array<string, mixed> $params Message placeholder params (non-content).
	 *
	 * @return array<string, mixed> The finding.
	 */
	private function finding(string $checkId, array $profile, string $messageKey, array $params = []): array {
		return [
			'checkId' => $checkId,
			'severity' => $this->checkSeverity(profile: $profile, check: $checkId),
			'message' => $messageKey,
			'params' => $params,
		];

	}//end finding()

	/**
	 * Whether a required field is absent or empty on the record.
	 *
	 * @param array<string, mixed> $record The record.
	 * @param string $field The field name.
	 *
	 * @return bool True when missing/empty.
	 */
	private function fieldMissing(array $record, string $field): bool {
		if (array_key_exists($field, $record) === false) {
			return true;
		}

		$value = $record[$field];
		if ($value === null || $value === '' || $value === []) {
			return true;
		}

		return false;
	}//end fieldMissing()
}//end class
