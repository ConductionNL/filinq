<?php

/**
 * The audit trail of a subject erasure.
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

use OCA\Filinq\Exception\SubjectErasureRefusedException;
use OCA\OpenRegister\Db\AuditTrailMapper;
use OCA\OpenRegister\Db\ObjectEntity;
use OCP\AppFramework\Utility\ITimeFactory;
use Throwable;

/**
 * Every step writes one entry on the request object, and a step whose entry
 * cannot be written is not done. The entries name files, counts, obligations
 * and outcomes; never the person's name or identifiers, which live on the
 * request alone.
 */
class SubjectErasureAudit {

	public const ACTION_DENIED = 'filinq.subject_erasure.denied';

	public const ACTION_REQUESTED = 'filinq.subject_erasure.requested';

	public const ACTION_PREVIEWED = 'filinq.subject_erasure.previewed';

	public const ACTION_EXCLUDED = 'filinq.subject_erasure.excluded';

	public const ACTION_RUN_STARTED = 'filinq.subject_erasure.run_started';

	public const ACTION_DOCUMENT_ERASED = 'filinq.subject_erasure.document_erased';

	public const ACTION_DOCUMENT_REFUSED = 'filinq.subject_erasure.document_refused';

	public const ACTION_DOCUMENT_FAILED = 'filinq.subject_erasure.document_failed';

	public const ACTION_MAPPING_DESTROYED = 'filinq.subject_erasure.mapping_destroyed';

	public const ACTION_CERTIFIED = 'filinq.subject_erasure.certified';

	/**
	 * Constructor.
	 *
	 * @param AuditTrailMapper   $auditTrail OpenRegister's audit trail.
	 * @param ITimeFactory       $time       The clock.
	 * @param SubjectErasureStore $store     Finds the stored request the entry sits on.
	 */
	public function __construct(
		private readonly AuditTrailMapper $auditTrail,
		private readonly ITimeFactory $time,
		private readonly SubjectErasureStore $store,
	) {

	}//end __construct()

	/**
	 * Record one step against the request.
	 *
	 * @param string               $requestUuid The request uuid ('' for a refusal before one exists).
	 * @param string               $action      One of the ACTION_* constants.
	 * @param string               $userId      Who acted.
	 * @param array<string, mixed> $details     What happened; never an identifier of the subject.
	 *
	 * @return void
	 *
	 * @throws SubjectErasureRefusedException When the audit trail did not take the entry.
	 *
	 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-4.2
	 */
	public function record(string $requestUuid, string $action, string $userId, array $details = []): void {
		$subject = $this->store->storedRequest(uuid: $requestUuid);
		if ($subject === null) {
			$subject = new ObjectEntity();
			$subject->setUuid($requestUuid);
		}

		try {
			$this->auditTrail->createAuditTrailEntry(
				object: $subject,
				action: $action,
				context: array_merge(
					[
						'subjectErasureRequest' => $requestUuid,
						'actor' => $userId,
						'at' => $this->time->getDateTime()->format(format: DATE_ATOM),
					],
					$details
				),
				actorId: $userId
			);
		} catch (Throwable $e) {
			throw new SubjectErasureRefusedException(
				reason: SubjectErasureRefusedException::REASON_AUDIT_UNAVAILABLE,
				message: 'The audit trail refused the erasure entry, so the step was not done: ' . $e->getMessage()
			);
		}

	}//end record()
}//end class
