<?php

/**
 * Final Document Unfreeze Service
 *
 * The exception, and the reason it exists. Without one, somebody edits the
 * file on the filesystem and there is no record at all. With a silent one, the
 * guarantee is worthless. So an administrator may unfreeze, it needs a named
 * right and a reason, it writes an audit entry, and it leaves a mark on the
 * document that cannot be cleared.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use OCP\IGroupManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Unfreezes a final document version, and records that it happened.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */
class FinalDocumentUnfreezeService {

	/**
	 * The named right that unfreezing needs.
	 *
	 * The `docudesk-` prefix is the family every group in this app already
	 * belongs to (`docudesk-policy-admins`, `docudesk-signing-admins`). Group
	 * names are configuration an administrator has already typed on live
	 * instances, so the family stays on the old prefix rather than splitting in
	 * two when the app id moves.
	 *
	 * @var string
	 */
	public const UNFREEZE_GROUP = 'docudesk-final-document-admins';

	/**
	 * Constructor.
	 *
	 * @param FinalDocumentRepository $repository Store of the finalisation records.
	 * @param FinalDocumentService $finalDocuments The finalisation service.
	 * @param IUserSession $userSession The current user session.
	 * @param IGroupManager $groupManager Group membership, for the named right.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly FinalDocumentRepository $repository,
		private readonly FinalDocumentService $finalDocuments,
		private readonly IUserSession $userSession,
		private readonly IGroupManager $groupManager,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Unfreeze a final version, with a reason, leaving a permanent mark.
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param string $reason Why the version is being unfrozen.
	 *
	 * @return array<string, mixed> The unfrozen record.
	 *
	 * @throws RuntimeException When the caller lacks the right, gives no reason, or the version is not final.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function unfreeze(int $fileId, string $reason): array {
		$actor = $this->requireRight();

		if (trim($reason) === '') {
			throw new RuntimeException(
				message: 'Unfreezing a final document needs a reason. It is written into the record and '
					. 'stays there, which is the whole point of allowing it at all.'
			);
		}

		$record = $this->repository->findCurrent(fileId: $fileId);
		if ($record === null || $this->finalDocuments->isFinal(record: $record) === false) {
			throw new RuntimeException(
				message: 'This document is not final, so there is nothing to unfreeze.'
			);
		}

		$uuid = (string)($record['uuid'] ?? '');
		if ($uuid === '') {
			throw new RuntimeException(
				message: 'This document has no finalisation record to unfreeze.'
			);
		}

		$moment = (new DateTimeImmutable())->format(DATE_ATOM);

		$updated = $record;
		unset($updated['uuid']);
		$updated['status'] = FinalDocumentRepository::STATUS_DRAFT;
		$updated['unfrozen'] = true;
		$updated['unfrozenBy'] = $actor['id'];
		$updated['unfrozenAt'] = $moment;
		$updated['unfrozenReason'] = $reason;

		$stored = $this->repository->save(record: $updated, uuid: $uuid);

		$this->writeAudit(record: $stored, actor: $actor, reason: $reason, moment: $moment);

		return $stored;

	}//end unfreeze()

	/**
	 * Refuse an attempt to clear the mark.
	 *
	 * The mark is what makes the exception honest. A product whose scar can be
	 * rubbed out has the silent exception this one was written to avoid, so
	 * clearing it is refused rather than quietly ignored.
	 *
	 * @param array<string, mixed> $incoming The record a caller wants to write.
	 * @param array<string, mixed> $stored The record as it stands.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the incoming record would clear the mark.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function refuseClearingTheMark(array $incoming, array $stored): void {
		if ((bool)($stored['unfrozen'] ?? false) === false) {
			return;
		}

		$keeps = (bool)($incoming['unfrozen'] ?? false);
		if ($keeps === true) {
			return;
		}

		throw new RuntimeException(
			message: 'This document was unfrozen once, and that cannot be taken back. The mark stays so '
				. 'an archivist can see it.'
		);

	}//end refuseClearingTheMark()

	/**
	 * Whether the acting user holds the named right.
	 *
	 * @return bool True when the acting user may unfreeze.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function mayUnfreeze(): bool {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return false;
		}

		if ($this->groupManager->isAdmin($user->getUID()) === true) {
			return true;
		}

		return $this->groupManager->isInGroup($user->getUID(), self::UNFREEZE_GROUP);

	}//end mayUnfreeze()

	/**
	 * Require the named right, naming it in the refusal.
	 *
	 * @return array{id: string, name: string} The acting administrator.
	 *
	 * @throws RuntimeException When the caller does not hold the right.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	private function requireRight(): array {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new RuntimeException(
				message: 'Unfreezing a final document needs an authenticated administrator.'
			);
		}

		if ($this->mayUnfreeze() === false) {
			throw new RuntimeException(
				message: sprintf(
					'Unfreezing a final document needs the "%s" right. Ask an administrator to add you '
					. 'to that group, or to unfreeze it for you.',
					self::UNFREEZE_GROUP
				)
			);
		}

		return ['id' => $user->getUID(), 'name' => $user->getDisplayName()];

	}//end requireRight()

	/**
	 * Record the unfreeze where an archivist will find it.
	 *
	 * The audit entry itself is OpenRegister's, written by the save above:
	 * `documentVersion` is an OpenRegister object, and the immutable audit trail
	 * records every change to one with the acting user and the moment, while
	 * `unfrozenBy`, `unfrozenAt` and `unfrozenReason` carry the three facts on
	 * the record itself. Filinq does not keep a second audit trail beside it
	 * (ADR-022), and the write is fail-closed because `save()` throws: an
	 * unfreeze that could not be recorded did not happen.
	 *
	 * What is added here is the operational signal. An unfreeze is rare and
	 * consequential, so it belongs in the server log at warning level too,
	 * where an administrator watching the instance sees it without querying the
	 * register.
	 *
	 * @param array<string, mixed> $record The unfrozen record, as stored.
	 * @param array{id: string, name: string} $actor The acting administrator.
	 * @param string $reason Why the version was unfrozen.
	 * @param string $moment When it happened (ISO 8601).
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	private function writeAudit(array $record, array $actor, string $reason, string $moment): void {
		$this->logger->warning(
			message: '[FinalDocumentUnfreezeService] a final document was unfrozen',
			context: [
				'file' => __FILE__,
				'line' => __LINE__,
				'fileId' => ($record['fileId'] ?? null),
				'versionUuid' => ($record['uuid'] ?? ''),
				'unfrozenBy' => $actor['id'],
				'unfrozenAt' => $moment,
				'reason' => $reason,
			]
		);

	}//end writeAudit()
}//end class
