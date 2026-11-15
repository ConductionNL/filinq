<?php

/**
 * Document Copy Service
 *
 * Copies a document from one record to another: a new file with the same
 * name and content in the target record's folder, linked to the target the
 * way an uploaded document is, with an entry in both records' audit trails.
 * The source stays where it was.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-6.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Exception\DomainLinkRefusedException;
use OCA\OpenRegister\Db\AuditTrail\ObjectAuditEvent;
use OCA\OpenRegister\Db\AuditTrailMapper;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Copies one document to another record, after the read and write checks.
 *
 * Nothing is written until both checks passed: the caller must be able to
 * read the source record and the file, and to write the target record.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-6.1
 */
class DocumentCopyService {
	use ReadsCurrentUserId;

	/**
	 * The audit action on the record the document was copied from.
	 *
	 * @var string
	 */
	public const ACTION_COPIED_OUT = 'filinq.document.copied_to';

	/**
	 * The audit action on the record the copy went to.
	 *
	 * @var string
	 */
	public const ACTION_COPIED_IN = 'filinq.document.copied_from';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param IntakeAuthorizationGate       $recordGate     Whether the caller may write the target.
	 * @param IntakeFilePlacement           $placement      The folder a record owns.
	 * @param FinalDocumentRepository       $documents      The document records.
	 * @param IRootFolder                   $rootFolder     The file tree.
	 * @param IUserSession                  $userSession    The current session.
	 * @param AuditTrailMapper              $auditTrail     OpenRegister's audit trail.
	 * @param LoggerInterface               $logger         Logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly IntakeAuthorizationGate $recordGate,
		private readonly IntakeFilePlacement $placement,
		private readonly FinalDocumentRepository $documents,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly AuditTrailMapper $auditTrail,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Copy one file of a record to another record.
	 *
	 * @param int                  $fileId The file on the source record.
	 * @param array<string, mixed> $source The source record, as register, schema and id.
	 * @param array<string, mixed> $target The target record, as register, schema and id.
	 *
	 * @return array<string, mixed> The copy's file id and name, and its document record.
	 *
	 * @throws DomainLinkRefusedException When the caller may not read the source or write the target.
	 * @throws RuntimeException When the request is incomplete or the target has no folder to write to.
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-6.1
	 */
	public function copy(int $fileId, array $source, array $target): array {
		$source = $this->reference(record: $source);
		$target = $this->reference(record: $target);
		$uid = $this->currentUserId();
		if ($uid === '' || $fileId <= 0) {
			throw new DomainLinkRefusedException(message: 'You may not read that document.');
		}

		$file = $this->readableSource(uid: $uid, fileId: $fileId, source: $source);
		if ($this->recordGate->mayFileOnto(register: $target['register'], schema: $target['schema'], id: $target['id']) === false) {
			throw new DomainLinkRefusedException(message: 'You may not change the documents of that record.');
		}

		$folder = $this->targetFolder(uid: $uid, target: $target);
		$copy = $folder->newFile($folder->getNonExistingName($file->getName()), $file->getContent());

		$record = $this->documents->save(
			record: [
				'fileId' => (int)$copy->getId(),
				'documentName' => $copy->getName(),
				'status' => FinalDocumentRepository::STATUS_DRAFT,
				'domains' => [$target],
				'createdByUser' => $uid,
			]
		);

		$copyId = (int)$copy->getId();
		$this->audit(
			record: $source,
			action: self::ACTION_COPIED_OUT,
			context: ['fileId' => $fileId, 'copyFileId' => $copyId, 'name' => $copy->getName(), 'to' => $target, 'by' => $uid]
		);
		$this->audit(
			record: $target,
			action: self::ACTION_COPIED_IN,
			context: ['fileId' => $copyId, 'sourceFileId' => $fileId, 'name' => $copy->getName(), 'from' => $source, 'by' => $uid]
		);

		return ['fileId' => (int)$copy->getId(), 'name' => $copy->getName(), 'record' => $record];

	}//end copy()

	/**
	 * The source file, when the caller can read both the record and the file.
	 *
	 * @param string                $uid    The caller.
	 * @param int                   $fileId The file.
	 * @param array<string, string> $source The source record.
	 *
	 * @return File The file.
	 *
	 * @throws DomainLinkRefusedException When either cannot be read.
	 */
	private function readableSource(string $uid, int $fileId, array $source): File {
		try {
			$visible = $this->objectResolver->resolve()->find(
				id: $source['id'],
				options: new ObjectFindOptions(register: $source['register'], schema: $source['schema'])
			);
			$node = $this->rootFolder->getUserFolder($uid)->getFirstNodeById($fileId);
		} catch (Throwable) {
			$visible = null;
			$node = null;
		}

		if ($visible === null || ($node instanceof File) === false || $node->isReadable() === false) {
			throw new DomainLinkRefusedException(message: 'You may not read that document.');
		}

		return $node;

	}//end readableSource()

	/**
	 * The target record's folder, as the caller sees it, when they may write in it.
	 *
	 * @param string                $uid    The caller.
	 * @param array<string, string> $target The target record.
	 *
	 * @return Folder The folder.
	 *
	 * @throws RuntimeException When the record has no folder the caller can write.
	 */
	private function targetFolder(string $uid, array $target): Folder {
		$path = $this->placement->folderFor(register: $target['register'], schema: $target['schema'], id: $target['id']);
		try {
			$folder = null;
			if ($path !== null && $path !== '') {
				$folder = $this->rootFolder->getUserFolder($uid)->get(ltrim($path, '/'));
			}
		} catch (Throwable) {
			$folder = null;
		}

		if (($folder instanceof Folder) === false || $folder->isCreatable() === false) {
			throw new RuntimeException(message: 'That record has no folder you can add a document to.');
		}

		return $folder;

	}//end targetFolder()

	/**
	 * Write one entry in a record's audit trail; a failure is logged, never thrown.
	 *
	 * @param array<string, string> $record  The record.
	 * @param string                $action  The action.
	 * @param array<string, mixed>  $context What happened, naming the other record.
	 *
	 * @return void
	 */
	private function audit(array $record, string $action, array $context): void {
		try {
			$object = $this->objectResolver->resolve()->find(
				id: $record['id'],
				options: new ObjectFindOptions(register: $record['register'], schema: $record['schema']),
				_rbac: false
			);
			if (($object instanceof ObjectEntity) === false) {
				$object = new ObjectEntity();
				$object->setUuid($record['id']);
			}

			$this->auditTrail->recordObjectEvent(event: new ObjectAuditEvent(object: $object, action: $action, context: $context));
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[DocumentCopyService] the copy is done but its audit entry could not be written',
				context: ['record' => $record, 'action' => $action, 'error' => $e->getMessage()]
			);
		}

	}//end audit()

	/**
	 * A record as three trimmed parts, refused when one is missing.
	 *
	 * @param array<string, mixed> $record The record as given.
	 *
	 * @return array<string, string> Register, schema and id.
	 *
	 * @throws RuntimeException When a part is missing.
	 */
	private function reference(array $record): array {
		$reference = [
			'register' => trim((string)($record['register'] ?? '')),
			'schema' => trim((string)($record['schema'] ?? '')),
			'id' => trim((string)($record['id'] ?? '')),
		];
		if (in_array('', $reference, true) === true) {
			throw new RuntimeException(message: 'A copy names the register, the schema and the id of both records.');
		}

		return $reference;

	}//end reference()
}//end class
