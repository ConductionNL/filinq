<?php

/**
 * Waiting Work Service
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * What waits for one person, from three places: a signature or acceptance in
 * their signing folder, an intake document routed to a group they are in and
 * not yet accepted, and a generated document they own that is due for review.
 * Each item says why it waits.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#2-documents
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Kernel\Contracts\WaitingWorkServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentReviewServiceInterface;
use OCA\Filinq\Kernel\Contracts\IntakeRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\SigningFolderServiceInterface;
use OCP\IGroupManager;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Lists the work that waits for one person (REQ-SCR-006).
 *
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#2-documents
 */
class WaitingWorkService implements WaitingWorkServiceInterface {

	/**
	 * A signature or an acceptance waits in the signing folder.
	 *
	 * @var string
	 */
	public const KIND_SIGNATURE = 'signature';

	/**
	 * An intake document routed to the person's group waits for acceptance.
	 *
	 * @var string
	 */
	public const KIND_INTAKE = 'intake';

	/**
	 * A document the person owns is due for review.
	 *
	 * @var string
	 */
	public const KIND_REVIEW = 'review';

	/**
	 * The most signing folder entries one list reads.
	 *
	 * @var int
	 */
	private const FOLDER_LIMIT = 200;

	/**
	 * Constructor.
	 *
	 * @param SigningFolderServiceInterface  $signingFolder The person's signing folder.
	 * @param IntakeRepositoryInterface      $intake        The intake documents.
	 * @param DocumentReviewServiceInterface $reviews       The documents due for review.
	 * @param IGroupManager                  $groups        Group membership, for routed intake documents.
	 * @param LoggerInterface                $logger        Logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SigningFolderServiceInterface $signingFolder,
		private readonly IntakeRepositoryInterface $intake,
		private readonly DocumentReviewServiceInterface $reviews,
		private readonly IGroupManager $groups,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Everything that waits for this person, signatures first, then intake, then reviews.
	 *
	 * A source that cannot be read is left out and logged, so the other two
	 * still show.
	 *
	 * @param string $userId The person.
	 *
	 * @return list<array{kind: string, id: string, name: string, reason: string, since: string}> The waiting items.
	 *
	 * @throws RuntimeException When there is no person.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#2-documents
	 */
	public function listFor(string $userId): array {
		if ($userId === '') {
			throw new RuntimeException('No authenticated user');
		}

		return array_merge(
			$this->signatures(userId: $userId),
			$this->intakeAcceptances(userId: $userId),
			$this->reviewsDue(userId: $userId)
		);

	}//end listFor()

	/**
	 * The signing folder entries: a signature, or an acceptance for an accept-only recipient.
	 *
	 * @param string $userId The person.
	 *
	 * @return list<array{kind: string, id: string, name: string, reason: string, since: string}>
	 */
	private function signatures(string $userId): array {
		try {
			$entries = $this->signingFolder->folder(userId: $userId, limit: self::FOLDER_LIMIT)['entries'];
		} catch (Throwable $e) {
			$this->unreadable(source: 'signing folder', error: $e);
			return [];
		}

		$items = [];
		foreach ($entries as $entry) {
			$reason = 'Waiting for your signature';
			if (($entry['role'] ?? '') === 'accept') {
				$reason = 'Waiting for your acceptance';
			}

			$items[] = $this->item(
				kind: self::KIND_SIGNATURE,
				id: (string)($entry['requestId'] ?? ''),
				name: (string)($entry['documentName'] ?? ''),
				reason: $reason,
				since: (string)($entry['requestedAt'] ?? '')
			);
		}

		return $items;

	}//end signatures()

	/**
	 * Intake documents routed to a group the person is in that still wait for acceptance.
	 *
	 * @param string $userId The person.
	 *
	 * @return list<array{kind: string, id: string, name: string, reason: string, since: string}>
	 */
	private function intakeAcceptances(string $userId): array {
		try {
			$documents = $this->intake->findByStatus(status: IntakeRepositoryInterface::STATUS_ASSIGNED);
		} catch (Throwable $e) {
			$this->unreadable(source: 'intake documents', error: $e);
			return [];
		}

		$items = [];
		foreach ($documents as $document) {
			$acceptance = (array)($document['acceptance'] ?? []);
			$group      = (string)($document['routing'] ?? '');
			if (($acceptance['required'] ?? false) !== true
				|| (string)($acceptance['acceptedBy'] ?? '') !== ''
				|| $group === ''
				|| $this->groups->isInGroup($userId, $group) === false
			) {
				continue;
			}

			$items[] = $this->item(
				kind: self::KIND_INTAKE,
				id: (string)($document['uuid'] ?? ($document['id'] ?? '')),
				name: (string)($document['fileName'] ?? ($document['subject'] ?? '')),
				reason: 'Routed to your group, waiting for acceptance',
				since: (string)($document['assignedAt'] ?? '')
			);
		}//end foreach

		return $items;

	}//end intakeAcceptances()

	/**
	 * Generated documents the person owns that are due for review.
	 *
	 * @param string $userId The person.
	 *
	 * @return list<array{kind: string, id: string, name: string, reason: string, since: string}>
	 */
	private function reviewsDue(string $userId): array {
		try {
			$documents = $this->reviews->listDue();
		} catch (Throwable $e) {
			$this->unreadable(source: 'documents due for review', error: $e);
			return [];
		}

		$items = [];
		foreach ($documents as $document) {
			if ((string)($document['owner'] ?? '') !== $userId) {
				continue;
			}

			$items[] = $this->item(
				kind: self::KIND_REVIEW,
				id: (string)($document['uuid'] ?? ($document['id'] ?? ($document['@self']['id'] ?? ''))),
				name: (string)($document['templateName'] ?? ($document['filePath'] ?? '')),
				reason: 'Due for review',
				since: (string)($document['reviewDate'] ?? '')
			);
		}

		return $items;

	}//end reviewsDue()

	/**
	 * One waiting item.
	 *
	 * @param string $kind   signature, intake or review.
	 * @param string $id     The id of the request, intake document or generated document.
	 * @param string $name   What it is called.
	 * @param string $reason Why it waits, in English; the screen translates it.
	 * @param string $since  When it started waiting or fell due, ISO 8601, or ''.
	 *
	 * @return array{kind: string, id: string, name: string, reason: string, since: string}
	 */
	private function item(string $kind, string $id, string $name, string $reason, string $since): array {
		return [
			'kind' => $kind,
			'id' => $id,
			'name' => $name,
			'reason' => $reason,
			'since' => $since,
		];

	}//end item()

	/**
	 * Log a source that could not be read.
	 *
	 * @param string    $source What could not be read.
	 * @param Throwable $error  Why.
	 *
	 * @return void
	 */
	private function unreadable(string $source, Throwable $error): void {
		$this->logger->warning(
			message: '[WaitingWorkService] could not read the ' . $source,
			context: ['error' => $error->getMessage()]
		);

	}//end unreadable()
}//end class
