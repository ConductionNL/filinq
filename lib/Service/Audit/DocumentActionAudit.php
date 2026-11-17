<?php

/**
 * The audit trail of a document's actions, kept in OpenRegister
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Audit
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

namespace OCA\Filinq\Service\Audit;

use InvalidArgumentException;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Kernel\Contracts\FinalDocumentRepositoryInterface;
use OCA\OpenRegister\Db\AuditTrail;
use OCA\OpenRegister\Db\AuditTrail\ObjectAuditEvent;
use OCA\OpenRegister\Db\AuditTrailMapper;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Writes one entry per action on a document into the OpenRegister audit
 * trail of the document's record, and reads that trail back.
 *
 * A document is a file with a document record (`documentVersion`); the entry
 * lands on the record that describes the file's current content. OpenRegister
 * stamps the actor and the moment. Filinq keeps no second store: a file
 * without a document record has no trail to write to, and gets no entry.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Audit
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
 */
class DocumentActionAudit {

	/**
	 * The prefix of every document action in the audit trail.
	 */
	public const PREFIX = 'filinq.document.';

	/**
	 * The actions a document's trail records.
	 */
	public const ACTIONS = ['opened', 'downloaded', 'edited', 'versioned', 'shared', 'anonymised', 'signed', 'deleted'];

	/**
	 * At most this many entries are read back for one document.
	 */
	public const TRAIL_LIMIT = 500;

	/**
	 * Constructor.
	 *
	 * @param FinalDocumentRepositoryInterface       $documents      The document records.
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param AuditTrailMapper              $auditTrail     OpenRegister's audit trail.
	 * @param LoggerInterface               $logger         Logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly FinalDocumentRepositoryInterface $documents,
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly AuditTrailMapper $auditTrail,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Write one action on a file into its document record's trail.
	 *
	 * Never throws for a failed write: the action itself already happened, so
	 * the failure is logged and the answer is false.
	 *
	 * @param int                  $fileId  The Nextcloud file id.
	 * @param string               $action  One of {@see self::ACTIONS}.
	 * @param array<string, mixed> $context What else the entry records; never document content.
	 *
	 * @return bool True when an entry was written, false when the file has no record or the write failed.
	 *
	 * @throws InvalidArgumentException For an action not in {@see self::ACTIONS}.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
	 */
	public function record(int $fileId, string $action, array $context = []): bool {
		if (in_array($action, self::ACTIONS, true) === false) {
			throw new InvalidArgumentException(message: 'Unknown document action: ' . $action);
		}

		$uuid = $this->recordUuid(fileId: $fileId);
		if ($uuid === '') {
			return false;
		}

		try {
			$object = $this->objectResolver->resolve()->find(
				id: $uuid,
				options: new ObjectFindOptions(register: FinalDocumentRepositoryInterface::REGISTER, schema: FinalDocumentRepositoryInterface::SCHEMA),
				_rbac: false
			);
			if (($object instanceof ObjectEntity) === false) {
				$object = new ObjectEntity();
				$object->setUuid($uuid);
			}

			$this->auditTrail->recordObjectEvent(
				event: new ObjectAuditEvent(object: $object, action: self::PREFIX . $action, context: ['fileId' => $fileId] + $context)
			);
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[DocumentActionAudit] the action happened but its audit entry could not be written',
				context: ['fileId' => $fileId, 'action' => $action, 'error' => $e->getMessage()]
			);
			return false;
		}//end try

		return true;

	}//end record()

	/**
	 * The trail of a file's document record, oldest first: the record's own
	 * entries (registered, changed) and the document actions written here.
	 *
	 * The caller checks first that the reader may read the file.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return list<array{action: string, user: string, userName: string, created: string}> The entries; empty without a record.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
	 */
	public function trail(int $fileId): array {
		$uuid = $this->recordUuid(fileId: $fileId);
		if ($uuid === '') {
			return [];
		}

		try {
			$rows = $this->auditTrail->findAll(limit: self::TRAIL_LIMIT, filters: ['object_uuid' => $uuid], sort: ['created' => 'ASC']);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[DocumentActionAudit] the trail of a document could not be read',
				context: ['fileId' => $fileId, 'error' => $e->getMessage()]
			);
			return [];
		}

		$entries = [];
		foreach ($rows as $row) {
			if ($row instanceof AuditTrail) {
				$entries[] = $this->entry(data: $row->jsonSerialize());
			}
		}

		usort($entries, static fn (array $left, array $right): int => strcmp($left['created'], $right['created']));

		return $entries;

	}//end trail()

	/**
	 * One trail entry as the activity tab shows it: action, actor and moment.
	 *
	 * @param array<string, mixed> $data The serialised audit row.
	 *
	 * @return array{action: string, user: string, userName: string, created: string}
	 */
	private function entry(array $data): array {
		$created = $data['created'] ?? '';
		if ($created instanceof \DateTimeInterface) {
			$created = $created->format(\DateTimeInterface::ATOM);
		}

		return [
			'action' => (string) ($data['action'] ?? ''),
			'user' => (string) ($data['user'] ?? ''),
			'userName' => (string) ($data['userName'] ?? ''),
			'created' => (string) $created,
		];

	}//end entry()

	/**
	 * The uuid of the record describing the file's current content, or ''.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return string The uuid, or '' when there is none or the register could not be read.
	 */
	private function recordUuid(int $fileId): string {
		if ($fileId <= 0) {
			return '';
		}

		$records = $this->documents->findForFile(fileId: $fileId);
		if ($records === null || $records === []) {
			return '';
		}

		$chosen = $records[0];
		foreach ($records as $record) {
			if ((string) ($record['versionLabel'] ?? '') === '') {
				$chosen = $record;
				break;
			}
		}

		return trim((string) ($chosen['uuid'] ?? $chosen['id'] ?? ''));

	}//end recordUuid()
}//end class
