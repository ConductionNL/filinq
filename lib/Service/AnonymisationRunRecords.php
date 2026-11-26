<?php

/**
 * The durable records of a finished anonymisation run, in the order they depend on each other.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Service\Pseudonymisation\PseudonymMapRecorder;
use OCA\Filinq\Service\Redaction\RedactionVerdictRecorder;
use OCA\Filinq\Service\Review\ReviewRenditionRepository;
use OCA\Filinq\Service\Sanitization\AnonymisationSanitizationRecorder;

/**
 * Records the verdict, then the anonymisation link, then the key.
 *
 * The order is the dependency: the verdict travels on the link, and the key
 * names the link, so neither can be written before the thing it points at.
 */
class AnonymisationRunRecords {

	/**
	 * Constructor.
	 *
	 * @param RedactionVerdictRecorder        $verdicts    Verifies the bytes that were actually written.
	 * @param AnonymizationPersistenceService $persistence Writes the anonymisation link.
	 * @param PseudonymMapRecorder            $keys        Keeps the key of a reversible run, and removes
	 *                                                     the key of an earlier run when this one is
	 *                                                     irreversible.
	 * @param RedactionAccessibilityService|null $accessibility What the run asks OpenRegister to keep of
	 *                                                     the tag structure, and what it kept.
	 * @param AnonymisationSanitizationRecorder|null $sanitization Keeps what OpenRegister's office
	 *                                                     sanitiser removed during the run.
	 * @param ReviewRenditionRepository|null $renditions The source's review rendition, copied onto the link.
	 * @param BridgeResultRecorder|null $bridge The bridge document whose dossier copy this run anonymised.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly RedactionVerdictRecorder $verdicts,
		private readonly AnonymizationPersistenceService $persistence,
		private readonly PseudonymMapRecorder $keys,
		private readonly ?RedactionAccessibilityService $accessibility = null,
		private readonly ?AnonymisationSanitizationRecorder $sanitization = null,
		private readonly ?ReviewRenditionRepository $renditions = null,
		private readonly ?BridgeResultRecorder $bridge = null,
	) {

	}//end __construct()

	/**
	 * Write the records of one successful run.
	 *
	 * @param array<string, mixed> $resultInfo The result assembled so far.
	 * @param array<string, mixed> $context    Run context: anonymisedNode, redactedValues, outputMode,
	 *                                         fileId, sourceNode, placeholderMap, reversible, scope, userId.
	 *
	 * @return array<string, mixed> The result, with the verdict, the link id and the key outcome.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.2
	 * @spec openspec/changes/archive/2026-09-29-accessible-redaction-output/tasks.md#task-2.2
	 * @spec openspec/changes/zgw-document-bridge/tasks.md#task-2.6
	 */
	public function record(array $resultInfo, array $context): array {
		// On the bytes actually written, like the verdict: the summary page is already on.
		$resultInfo = $this->withStructurePreservation(resultInfo: $resultInfo, context: $context);

		// 🔴 ON THE BYTES THAT WERE ACTUALLY WRITTEN. The grondslagen summary
		// appends a page after the redaction, so the caller runs this after it:
		// verifying any earlier would record a verdict about a file that no
		// longer exists.
		$resultInfo = $this->verdicts->record(
			resultInfo: $resultInfo,
			anonymisedNode: $context['anonymisedNode'],
			redactedValues: ($context['redactedValues'] ?? []),
			outputMode: (string) ($context['outputMode'] ?? '')
		);

		if (empty($resultInfo['anonymizedFileId']) === true) {
			return $resultInfo;
		}

		// Per entity TYPE, never a value: what a processing certificate may state.
		$resultInfo['entityTypeCounts'] = $this->entityTypeCounts(redactedValues: (array) ($context['redactedValues'] ?? []));
		// The rendition the review ran on, so the link says what was reviewed (REQ-DDARW-012).
		$rendition = $this->renditionOf(fileId: (int) $context['fileId']);
		if ($rendition !== null) {
			$resultInfo['reviewRendition'] = $rendition;
		}

		$resultInfo = $this->persistence->recordAnonymizationLink(
			fileId: $context['fileId'],
			sourceNode: $context['sourceNode'],
			resultInfo: $resultInfo
		);

		// A bridge document's dossier copy: its external document is now processed (REQ-DDZGW-003).
		$this->bridge?->record(sourceFileId: (int) $context['fileId'], resultFileId: (int) $resultInfo['anonymizedFileId']);

		// What OpenRegister's sanitiser removed from an office document on the way.
		if ($this->sanitization !== null) {
			$resultInfo = $this->sanitization->record(resultInfo: $resultInfo, context: $context);
		}

		// After the link, because the key names it. A reversible run keeps
		// the key; an irreversible one removes the key an earlier run left.
		return $this->keys->record(
			resultInfo: $resultInfo,
			run: [
				'fileId' => $context['fileId'],
				'entities' => ($context['redactedValues'] ?? []),
				'placeholderMap' => ($context['placeholderMap'] ?? []),
				'reversible' => ($context['reversible'] ?? false),
				'scope' => ($context['scope'] ?? 'document'),
				'userId' => ($context['userId'] ?? ''),
			]
		);

	}//end record()

	/**
	 * The result with the structure-preservation assessment of the written output, when there is a report to assess.
	 *
	 * @param array<string, mixed> $resultInfo The result so far.
	 * @param array<string, mixed> $context    The run's context.
	 *
	 * @return array<string, mixed> The result.
	 */
	private function withStructurePreservation(array $resultInfo, array $context): array {
		if ($this->accessibility !== null && array_key_exists('structureReport', $context) === true) {
			$resultInfo['structurePreservation'] = $this->accessibility->assessOutput(
				report: $context['structureReport'],
				output: $context['anonymisedNode']
			);
		}

		return $resultInfo;

	}//end withStructurePreservation()

	/**
	 * Whether the run asks OpenRegister to keep the tag structure.
	 *
	 * Null (OpenRegister decides) when no accessibility service is wired.
	 *
	 * @return bool|null
	 *
	 * @spec openspec/changes/archive/2026-09-29-accessible-redaction-output/tasks.md#task-2.1
	 */
	public function preserveStructure(): ?bool {
		return $this->accessibility?->preserveRequested();

	}//end preserveStructure()

	/**
	 * How many redacted values of each entity type the run had.
	 *
	 * @param array<int, mixed> $redactedValues The mapped entities (text, entityType, key).
	 *
	 * @return array<string, int> Entity type => count; no value is kept.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-anonymization-runs-can-be-certified-with-a-sealed-processing-certificate-req-ddwmk-003
	 */
	private function entityTypeCounts(array $redactedValues): array {
		$counts = [];
		foreach ($redactedValues as $entity) {
			if (is_array($entity) === false) {
				continue;
			}

			$type = strtoupper((string) ($entity['entityType'] ?? 'UNKNOWN'));
			$counts[$type] = (($counts[$type] ?? 0) + 1);
		}

		ksort($counts);
		return $counts;

	}//end entityTypeCounts()

	/**
	 * The six rendition fields of a source, or null when none was prepared.
	 *
	 * @param int $fileId The source file id.
	 *
	 * @return array<string, mixed>|null The rendition.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.1
	 */
	private function renditionOf(int $fileId): ?array {
		$stored = $this->renditions?->findFor(sourceFileId: $fileId);
		if ($stored === null) {
			return null;
		}

		return array_intersect_key(
			$stored,
			array_flip(['fileId', 'sha256', 'kind', 'backend', 'textLayer', 'preparedAt'])
		);

	}//end renditionOf()
}//end class
