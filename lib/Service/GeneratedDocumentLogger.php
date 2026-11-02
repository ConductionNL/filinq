<?php

/**
 * Generated Document Logger
 *
 * Writes the audit-trail entry for every generated document into the document
 * register (DCS-072). Extracted from `DocumentService`.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use Psr\Log\LoggerInterface;

/**
 * Persists generated-document audit entries in the document register.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class GeneratedDocumentLogger {
	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService
	 * @param LoggerInterface $logger Logger for error reporting
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Log a generated document to the document register for audit trail (DCS-072).
	 *
	 * Stores template UUID + version number per DCS-051, data sources, format,
	 * status, and generating user. What was generated and how it went are each
	 * passed as one cohesive value rather than as a dozen loose scalars.
	 *
	 * @param DocumentGenerationRecord $generation The template, data, format, user and extra fields.
	 * @param array                    $outcome    The generation outcome: {status: string, warnings: string[],
	 *                                             caseId: ?string, errorMessage: ?string, fileId: ?int, filePath: ?string}
	 *
	 * @return array The created document register entry
	 *
	 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
	 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-004
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
	 */
	public function log(DocumentGenerationRecord $generation, array $outcome): array {
		try {
			$result = $this->objectResolver->resolve()->saveObject(
				object: $this->entryFor(generation: $generation, outcome: $outcome),
				register: 'filinq',
				schema: 'generatedDocument'
			);

			return $this->asEntry(result: $result);
		} catch (Exception $e) {
			$this->logger->error(
				message: 'Failed to log generated document: ' . $e->getMessage(),
				context: [
					'templateId' => $generation->template['id'],
					'templateVersion' => $generation->template['version'],
					'status' => $outcome['status'],
				]
			);
			return [];
		}//end try

	}//end log()

	/**
	 * The audit entry of one generation.
	 *
	 * @param DocumentGenerationRecord $generation The template, data, format, user and extra fields.
	 * @param array                    $outcome    The generation outcome.
	 *
	 * @return array<string, mixed> The entry.
	 */
	private function entryFor(DocumentGenerationRecord $generation, array $outcome): array {
		// 🔑 THE CANONICAL FIELDS WIN OVER THE EXTRA ONES. `+` keeps the LEFT
		// operand's keys, so a caller passing `templateId` or `status` in
		// `extra` adds nothing and overwrites nothing: the audit trail's own
		// account of what was generated is the one thing this entry exists
		// to be trusted about, and it is not a caller's to rewrite.
		$entry = [
			'templateId' => $generation->template['id'],
			'templateVersion' => $generation->template['version'],
			'templateName' => $generation->template['name'],
			'dataRefs' => $generation->dataRefs,
			'format' => $generation->format,
			'status' => $outcome['status'],
			'generatedAt' => date('c'),
			'generatedBy' => $generation->userId,
			'warnings' => $outcome['warnings'],
			'caseId' => $outcome['caseId'],
			'errorMessage' => $outcome['errorMessage'],
			'fileId' => ($outcome['fileId'] ?? null),
			'filePath' => ($outcome['filePath'] ?? null),
		] + $generation->extra;

		// An unknown template version is left out, never written as a number
		// (REQ-DDTVP-002): a default that reads as a fact is the defect.
		if ($entry['templateVersion'] === null) {
			unset($entry['templateVersion']);
		}

		return $entry;

	}//end entryFor()

	/**
	 * The saved entry as an array, whatever shape the register answered in.
	 *
	 * @param mixed $result What saveObject returned.
	 *
	 * @return array The entry; empty when the answer is unreadable.
	 */
	private function asEntry(mixed $result): array {
		if (is_object($result) === true
			&& method_exists(object_or_class: $result, method: 'jsonSerialize') === true
		) {
			return $result->jsonSerialize();
		}

		if (is_array($result) === true) {
			return $result;
		}

		return [];

	}//end asEntry()

	/**
	 * Write the audit entry of a generation that failed before it produced a document.
	 *
	 * @param array{templateId: string, dataRefs: array, format: string, userId: string} $attempt      What was attempted.
	 * @param string                                                                      $errorMessage Why it failed.
	 *
	 * @return array The created document register entry
	 *
	 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
	 */
	public function logFailure(array $attempt, string $errorMessage): array {
		return $this->log(
			generation: new DocumentGenerationRecord(
				template: ['id' => $attempt['templateId'], 'version' => null, 'name' => ''],
				dataRefs: $attempt['dataRefs'],
				format: $attempt['format'],
				userId: $attempt['userId']
			),
			outcome: [
				'status' => 'failed',
				'warnings' => [],
				'caseId' => null,
				'errorMessage' => $errorMessage,
				'fileId' => null,
				'filePath' => null,
			]
		);
	}//end logFailure()
}//end class
