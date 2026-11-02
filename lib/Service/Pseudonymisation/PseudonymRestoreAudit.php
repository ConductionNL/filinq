<?php

/**
 * Pseudonym Restore Audit
 *
 * Writes every restore attempt into OpenRegister's audit trail: refused,
 * granted, finished or failed. The write is on the request path and throws when
 * it fails, because a re-identification nobody can trace afterwards is the thing
 * this whole feature has to rule out.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Pseudonymisation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-3.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Pseudonymisation;

use OCA\Filinq\Service\Redaction\AnonymizationLinkReader;
use OCA\OpenRegister\Db\AuditTrailMapper;
use OCA\OpenRegister\Db\ObjectEntity;
use OCP\AppFramework\Utility\ITimeFactory;
use RuntimeException;
use Throwable;

/**
 * The audit trail of restores.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Pseudonymisation
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PseudonymRestoreAudit {

	public const ACTION_DENIED = 'filinq.pseudonymisation.restore_denied';

	public const ACTION_GRANTED = 'filinq.pseudonymisation.restore_granted';

	public const ACTION_RESTORED = 'filinq.pseudonymisation.restored';

	public const ACTION_FAILED = 'filinq.pseudonymisation.restore_failed';

	/**
	 * Constructor.
	 *
	 * @param AuditTrailMapper $auditTrail OpenRegister's audit trail.
	 * @param ITimeFactory $time The clock.
	 * @param AnonymizationLinkReader $links Finds the stored link the entry belongs on.
	 */
	public function __construct(
		private readonly AuditTrailMapper $auditTrail,
		private readonly ITimeFactory $time,
		private readonly AnonymizationLinkReader $links,
	) {

	}//end __construct()

	/**
	 * Record one step of a restore attempt against the anonymisation link.
	 *
	 * @param string $linkId The anonymisation link uuid the attempt was about.
	 * @param string $action One of the ACTION_* constants.
	 * @param string $userId Who attempted it.
	 * @param array<string, mixed> $details What happened: the reason, the copy, the mode.
	 *                                      Never an original value.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the audit trail did not take the entry.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-3.2
	 */
	public function record(string $linkId, string $action, string $userId, array $details = []): void {
		// The entry sits on the stored link (id, uuid, register, schema), so the
		// link's own audit view lists it. A guessed id has no stored object; the
		// attempt is still recorded, under the id that was tried.
		$subject = $this->links->storedObject(linkId: $linkId);
		if ($subject === null) {
			$subject = new ObjectEntity();
			$subject->setUuid($linkId);
		}

		try {
			$this->auditTrail->createAuditTrailEntry(
				object: $subject,
				action: $action,
				context: array_merge(
					[
						'anonymizationLink' => $linkId,
						'actor' => $userId,
						'at' => $this->time->getDateTime()->format(format: DATE_ATOM),
					],
					$details
				),
				actorId: $userId
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The audit trail refused the restore entry: ' . $e->getMessage(), code: 0, previous: $e);
		}

	}//end record()
}//end class
