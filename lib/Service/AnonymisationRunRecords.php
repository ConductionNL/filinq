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
	 *
	 * @return void
	 */
	public function __construct(
		private readonly RedactionVerdictRecorder $verdicts,
		private readonly AnonymizationPersistenceService $persistence,
		private readonly PseudonymMapRecorder $keys,
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
	 */
	public function record(array $resultInfo, array $context): array {
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

		$resultInfo = $this->persistence->recordAnonymizationLink(
			fileId: $context['fileId'],
			sourceNode: $context['sourceNode'],
			resultInfo: $resultInfo
		);

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
}//end class
