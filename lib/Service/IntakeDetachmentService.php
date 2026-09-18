<?php

/**
 * Intake Detachment Service
 *
 * Takes a document off the record it was filed on and puts it back on the
 * worklist, carrying the reason and the person who detached it. A document
 * removed from a case used to land nowhere: it was off the case and in nobody's
 * list, and the only trace was the file itself.
 *
 * A document that never had an intake record gets one HERE, in `detached`.
 * Uploads straight onto a record are the common case, and refusing to detach
 * them because they never passed through the inbox would make the worklist
 * useless exactly where it is needed.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\Filinq\Exception\IntakeRefusedException;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Returns a filed document to the worklist, with the reason it came back.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */
class IntakeDetachmentService {

	/**
	 * Constructor.
	 *
	 * @param IntakeRepository $repository The intake document store.
	 * @param IntakeAuthorizationGate $gate The write-rights gate.
	 * @param IUserSession $userSession The current session.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IntakeRepository $repository,
		private readonly IntakeAuthorizationGate $gate,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Take one document off the record it is filed on.
	 *
	 * @param int $fileId The Nextcloud file id of the document.
	 * @param string $reason Why it does not belong on that record.
	 * @param string $documentName The document's name, for a record that has to be created here.
	 *
	 * @return array<string, mixed> The intake document, now on the worklist.
	 *
	 * @throws IntakeRefusedException When there is no reason, or the user may not write the intake register.
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function detach(int $fileId, string $reason, string $documentName = ''): array {
		$reason = trim($reason);
		if ($reason === '') {
			throw new IntakeRefusedException(
				message: 'Detaching a document says why it does not belong on that record.',
				status: 400
			);
		}

		if ($this->gate->mayWrite(
			register: IntakeRepository::REGISTER,
			schema: IntakeRepository::SCHEMA
		) === false
		) {
			throw new IntakeRefusedException(
				message: 'You may not write the intake register.',
				status: 403
			);
		}

		$existing = $this->repository->findByFile(fileId: $fileId);
		$uuid = null;
		if ($existing === null) {
			$this->logger->info(
				message: '[IntakeDetachmentService] the document never passed through the inbox, giving it a record now',
				context: ['file' => __FILE__, 'line' => __LINE__, 'fileId' => $fileId]
			);

			$document = [
				'channel' => 'scan',
				'subject' => $documentName,
				'sender' => '',
				'receivedAt' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
				'file' => $fileId,
				'fileName' => $documentName,
				'sourceRef' => 'detached-' . $fileId,
			];
		} else {
			$document = $existing;
			$uuid = (string)($existing['uuid'] ?? '');
			if ($uuid === '') {
				$uuid = null;
			}
		}

		$document['status'] = IntakeRepository::STATUS_DETACHED;
		$document['detachReason'] = $reason;
		$document['detachedBy'] = $this->currentUserId();
		$document['detachedAt'] = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);

		return $this->repository->save(document: $document, uuid: $uuid);

	}//end detach()

	/**
	 * The user id of the person at the keyboard.
	 *
	 * @return string The user id, or an empty string when there is no session.
	 *
	 * @spec exclude Session accessor with no behaviour of its own.
	 */
	private function currentUserId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return '';
		}

		return $user->getUID();

	}//end currentUserId()
}//end class
