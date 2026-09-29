<?php

/**
 * Publication readiness
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Publication
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Publication;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\Filinq\Service\ConsentCrudService;
use OCA\Filinq\Service\ConsentService;
use OCA\Filinq\Service\PolicyMatchService;
use OCA\Filinq\Service\Redaction\RedactionReviewMarkRepository;

/**
 * The three checks a document passes before it may be handed off.
 *
 * Entities reviewed: a redacted copy exists and a person marked its
 * detected entities as checked. Consent clear: every consent request for
 * the document allows publication. Prohibitions clear: no active
 * publication prohibition matches anyone the consent requests name. Each
 * reason names record ids, never the person, so the verdict can be stored
 * and shown.
 *
 * Anything that cannot be read counts as not ready: an unconfigured
 * consent register or a missing review mark blocks, it does not wave the
 * document through.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Publication
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/woo-publicatie-pipeline/spec.md
 */
class PublicationReadiness {

	/**
	 * Constructor
	 *
	 * @param ConsentService                 $consents      The consent records and their clearance
	 * @param ConsentCrudService             $consentConfig Where consent records are kept
	 * @param PolicyMatchService             $policies      The publication prohibitions
	 * @param RedactionReviewMarkRepository  $marks         Who checked the detected entities
	 * @param PublicationStore               $store         The redacted copy
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ConsentService $consents,
		private readonly ConsentCrudService $consentConfig,
		private readonly PolicyMatchService $policies,
		private readonly RedactionReviewMarkRepository $marks,
		private readonly PublicationStore $store,
	) {

	}//end __construct()

	/**
	 * Run the three checks and write the verdicts onto the record.
	 *
	 * @param array<string, mixed> $record The publication record
	 * @param DateTimeImmutable    $now    The moment
	 *
	 * @return array<string, mixed> The record with entitiesReviewed, consentClear,
	 *                              prohibitionsClear, readinessReasons, readinessEvaluatedAt
	 *                              and, when found, redactedFileRef.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.2
	 */
	public function evaluate(array $record, DateTimeImmutable $now): array {
		$fileId = (string) ($record['documentFileRef'] ?? '');
		$reasons = [];

		$redacted = (string) ($record['redactedFileRef'] ?? '');
		if ($redacted === '') {
			$redacted = (string) $this->store->findRedactedCopy(fileId: $fileId);
		}

		if ($redacted === '') {
			$reasons[] = 'Document ' . $fileId . ' has no redacted copy yet';
		}

		$reviewed = $this->marks->findFor(document: $fileId) !== null;
		if ($reviewed === false) {
			$reasons[] = 'Nobody has checked the detected entities of document ' . $fileId;
		}

		[$consentClear, $prohibitionsClear, $more] = $this->consentAndProhibitions(fileId: $fileId, now: $now);

		$record['redactedFileRef'] = $redacted;
		$record['entitiesReviewed'] = $redacted !== '' && $reviewed === true;
		$record['consentClear'] = $consentClear;
		$record['prohibitionsClear'] = $prohibitionsClear;
		$record['readinessReasons'] = array_merge($reasons, $more);
		$record['readinessEvaluatedAt'] = $now->format(DateTimeInterface::ATOM);

		return $record;

	}//end evaluate()

	/**
	 * Whether all three checks passed.
	 *
	 * @param array<string, mixed> $record An evaluated record
	 *
	 * @return bool True when the document may be handed off.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.2
	 */
	public function isReady(array $record): bool {
		return ($record['entitiesReviewed'] ?? false) === true
			&& ($record['consentClear'] ?? false) === true
			&& ($record['prohibitionsClear'] ?? false) === true;

	}//end isReady()

	/**
	 * The consent and prohibition verdicts.
	 *
	 * @param string            $fileId The document
	 * @param DateTimeImmutable $now    The moment
	 *
	 * @return array{0: bool, 1: bool, 2: list<string>} Consent clear, prohibitions clear, reasons.
	 */
	private function consentAndProhibitions(string $fileId, DateTimeImmutable $now): array {
		$config = $this->consentConfig->getConsentConfig();
		if ($config === null) {
			return [false, false, ['The consent register is not configured, so consent and prohibitions cannot be checked']];
		}

		$clearance = $this->consents->isDocumentConsentClear(
			documentId: $fileId,
			register: $config['register'],
			schema: $config['schema'],
			now: $now
		);
		$reasons = $clearance['reasons'];

		$prohibited = [];
		$records = $this->consents->getConsentsByDocument(documentId: $fileId, register: $config['register'], schema: $config['schema']);
		foreach ($records as $consent) {
			$text = (string) ($consent['entityText'] ?? '');
			if (($consent['scope'] ?? 'document') === 'entity' || $text === '') {
				continue;
			}

			$match = $this->policies->matchProhibition(entityText: $text, entityType: (string) ($consent['entityType'] ?? 'PERSON'));
			if ($match !== null) {
				$prohibited[$match['uuid']] = 'Publication prohibition ' . $match['uuid'] . ' applies to someone in the document';
			}
		}

		return [$clearance['clear'], $prohibited === [], array_merge($reasons, array_values($prohibited))];

	}//end consentAndProhibitions()
}//end class
