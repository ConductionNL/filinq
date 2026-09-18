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
	 * status, and generating user. The template identity and the generation
	 * outcome are each passed as one cohesive bag rather than as a dozen loose
	 * scalars.
	 *
	 * @param array $template The template identity: {id: string, version: int, name: string}
	 * @param array $dataRefs The data references used
	 * @param string $format The output format
	 * @param array $outcome The generation outcome: {status: string, warnings: string[],
	 *                       zaakId: ?string, errorMessage: ?string, fileId: ?int, filePath: ?string}
	 * @param string $userId The generating user's UID
	 * @param array $extra Extra fields to carry on the entry, such as the plain-language
	 *                     rendition a template declared. Merged over the entry, so a
	 *                     caller cannot quietly overwrite the template identity or the
	 *                     outcome: those are written after the merge.
	 *
	 * @return array The created document register entry
	 *
	 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
	 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-004
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
	 */
	public function log(
		array $template,
		array $dataRefs,
		string $format,
		array $outcome,
		string $userId,
		array $extra = [],
	): array {
		try {
			$objectService = $this->objectResolver->resolve();

			// 🔑 THE CANONICAL FIELDS WIN OVER THE EXTRA ONES. `+` keeps the LEFT
			// operand's keys, so a caller passing `templateId` or `status` in
			// `extra` adds nothing and overwrites nothing: the audit trail's own
			// account of what was generated is the one thing this entry exists
			// to be trusted about, and it is not a caller's to rewrite.
			$entry = [
				'templateId' => $template['id'],
				'templateVersion' => $template['version'],
				'templateName' => $template['name'],
				'dataRefs' => $dataRefs,
				'format' => $format,
				'status' => $outcome['status'],
				'generatedAt' => date('c'),
				'generatedBy' => $userId,
				'warnings' => $outcome['warnings'],
				'caseId' => $outcome['caseId'],
				'errorMessage' => $outcome['errorMessage'],
				'fileId' => ($outcome['fileId'] ?? null),
				'filePath' => ($outcome['filePath'] ?? null),
			] + $extra;

			$result = $objectService->saveObject(
				object: $entry,
				register: 'filinq',
				schema: 'generatedDocument'
			);

			if (is_object($result) === true
				&& method_exists(object_or_class: $result, method: 'jsonSerialize') === true
			) {
				return $result->jsonSerialize();
			}

			if (is_array($result) === true) {
				return $result;
			}

			return [];
		} catch (Exception $e) {
			$this->logger->error(
				message: 'Failed to log generated document: ' . $e->getMessage(),
				context: [
					'templateId' => $template['id'],
					'templateVersion' => $template['version'],
					'status' => $outcome['status'],
				]
			);
			return [];
		}//end try

	}//end log()
}//end class
