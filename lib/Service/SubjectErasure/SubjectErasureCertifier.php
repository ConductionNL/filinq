<?php

/**
 * The certificate of a subject erasure.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SubjectErasure
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-4.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SubjectErasure;

use DateTimeInterface;
use OCA\Filinq\Exception\SubjectErasureRefusedException;
use OCA\Filinq\Service\Publication\PublicationStore;
use OCP\AppFramework\Utility\ITimeFactory;
use Throwable;

/**
 * Names the refusals as prominently as the erasures, and a document that
 * failed as a refusal the privacy officer must handle: a certificate that
 * lists only what was erased reads as a completed request.
 */
class SubjectErasureCertifier {

	/**
	 * Publication states whose published copy still carries the person.
	 *
	 * @var string[]
	 */
	public const PUBLISHED = ['handed_off', 'published', 'depublication_requested'];

	/**
	 * Constructor.
	 *
	 * @param SubjectErasureStore $store        The certificates.
	 * @param SubjectErasureRules $rules        Whether the result is complete.
	 * @param PublicationStore    $publications What was published.
	 * @param SubjectErasureAudit $audit        The audit trail.
	 * @param ITimeFactory        $time         The clock.
	 */
	public function __construct(
		private readonly SubjectErasureStore $store,
		private readonly SubjectErasureRules $rules,
		private readonly PublicationStore $publications,
		private readonly SubjectErasureAudit $audit,
		private readonly ITimeFactory $time,
	) {

	}//end __construct()

	/**
	 * Write the certificate of a finished run.
	 *
	 * @param array<string, mixed>             $request The request.
	 * @param array<int, array<string, mixed>> $results The per-document results.
	 * @param string                           $userId  The caller.
	 *
	 * @return array<string, mixed> The stored certificate.
	 *
	 * @throws SubjectErasureRefusedException When the audit trail refuses the entry.
	 *
	 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-4.2
	 */
	public function issue(array $request, array $results, string $userId): array {
		$erased = [];
		$refused = [];
		$destroyed = 0;
		foreach ($results as $result) {
			$destroyed += (int) ($result['mappingEntriesDestroyed'] ?? 0);
			if ($result['outcome'] === SubjectErasureDocumentStep::ERASED) {
				$erased[] = ['document' => $result['document'], 'occurrences' => (int) $result['occurrences']];
			}

			$refused = array_merge($refused, $this->refusedRows(result: $result));
		}

		$republish = $this->needsRepublishing(documents: array_column($erased, 'document'));
		$verdict = $this->rules->certificate(request: $request, erased: $erased, refused: $refused, republish: $republish);

		$certificate = $this->store->save(
			schema: SubjectErasureStore::CERTIFICATE,
			row: [
				'request' => (string) $request['uuid'],
				'issuedAt' => $this->time->getDateTime()->format(format: DateTimeInterface::ATOM),
				'actor' => mb_substr($userId, 0, 255),
				'ground' => (string) ($request['ground'] ?? ''),
				'erased' => $erased,
				'refused' => $refused,
				'excluded' => array_values((array) ($request['excluded'] ?? [])),
				'needsRepublishing' => $republish,
				'mappingEntriesDestroyed' => $destroyed,
				'documentRecordsDeleted' => 0,
				'complete' => $verdict['complete'],
			]
		);

		$this->audit->record(
			requestUuid: (string) $request['uuid'],
			action: SubjectErasureAudit::ACTION_CERTIFIED,
			userId: $userId,
			details: ['certificate' => $certificate['uuid'], 'erased' => count($erased), 'refused' => count($refused), 'complete' => $verdict['complete']]
		);

		return $certificate;

	}//end issue()

	/**
	 * The certificate rows for one refused or failed document.
	 *
	 * @param array<string, mixed> $result The result.
	 *
	 * @return array<int, array<string, string>> The rows.
	 */
	private function refusedRows(array $result): array {
		if ($result['outcome'] === SubjectErasureDocumentStep::FAILED) {
			return [[
				'document' => $result['document'],
				'obligation' => 'not_processable',
				'reason' => (string) $result['reason'],
				'decidedBy' => 'the privacy officer, by hand',
			],
			];
		}

		$rows = [];
		if ($result['outcome'] === SubjectErasureDocumentStep::REFUSED) {
			foreach ((array) $result['obligations'] as $obligation) {
				$rows[] = [
					'document' => $result['document'],
					'obligation' => (string) $obligation['obligation'],
					'reason' => (string) $obligation['reason'],
					'decidedBy' => (string) $obligation['decidedBy'],
				];
			}
		}

		return $rows;

	}//end refusedRows()

	/**
	 * The erased documents a published copy was made of. When the publication
	 * register cannot be read, every erased document is listed: an unverified
	 * "nothing to republish" is the answer that leaves the person online.
	 *
	 * @param array<int, string> $documents The erased document ids.
	 *
	 * @return array<int, string> The ids.
	 */
	private function needsRepublishing(array $documents): array {
		if ($documents === []) {
			return [];
		}

		try {
			$republish = [];
			foreach ($documents as $document) {
				foreach ($this->publications->listRecords(filters: ['documentFileRef' => $document]) as $record) {
					if ((string) ($record['documentFileRef'] ?? '') === $document && in_array((string) ($record['status'] ?? ''), self::PUBLISHED, true) === true) {
						$republish[] = $document;
						break;
					}
				}
			}
		} catch (Throwable) {
			return $documents;
		}

		return $republish;

	}//end needsRepublishing()
}//end class
