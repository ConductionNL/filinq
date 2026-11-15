<?php

/**
 * Document Copy Service Test
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
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

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\DomainLinkRefusedException;
use OCA\Filinq\Service\DocumentCopyService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FinalDocumentRepository;
use OCA\Filinq\Service\IntakeAuthorizationGate;
use OCA\Filinq\Service\IntakeFilePlacement;
use OCA\OpenRegister\Db\AuditTrail;
use OCA\OpenRegister\Db\AuditTrail\ObjectAuditEvent;
use OCA\OpenRegister\Db\AuditTrailMapper;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * A copy lands in the target's folder, linked and audited on both records;
 * a refused check writes nothing.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
#[CoversClass(DocumentCopyService::class)]
#[UsesClass(FinalDocumentRepository::class)]
#[UsesClass(DomainLinkRefusedException::class)]
class DocumentCopyServiceTest extends TestCase {

	/**
	 * Files written in the target folder, as name => content.
	 *
	 * @var array<string, string>
	 */
	private array $written = [];

	/**
	 * Document records stored.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $records = [];

	/**
	 * Audit entries, as "action uuid".
	 *
	 * @var array<int, string>
	 */
	private array $audited = [];

	/**
	 * Audit contexts, by action.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $contexts = [];

	/**
	 * The service over case A (readable, holding file 11) and case B.
	 *
	 * @param bool $mayWriteTarget Whether the caller may write case B.
	 * @param bool $mayReadSource  Whether the caller can see case A.
	 *
	 * @return DocumentCopyService The service.
	 */
	private function service(bool $mayWriteTarget = true, bool $mayReadSource = true): DocumentCopyService {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('find')->willReturnCallback(
			static function (...$arguments) use ($mayReadSource): ?ObjectEntity {
				$id = (string)$arguments[0];
				$rbac = ($arguments[3] ?? true);
				if ($id === 'case-a' && $mayReadSource === false && $rbac === true) {
					return null;
				}

				$entity = new ObjectEntity();
				$entity->setUuid($id);

				return $entity;
			}
		);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object): array {
				$this->records[] = $object;

				return $object + ['uuid' => 'record-copy'];
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		$gate = $this->createMock(IntakeAuthorizationGate::class);
		$gate->method('mayFileOnto')->willReturnCallback(
			static fn (string $register, string $schema, string $id): bool => ($id === 'case-b' && $mayWriteTarget)
		);
		$placement = $this->createMock(IntakeFilePlacement::class);
		$placement->method('folderFor')->willReturn('Zaken/B');

		$source = $this->createMock(File::class);
		$source->method('getName')->willReturn('Samengevoegd.pdf');
		$source->method('getContent')->willReturn('%PDF-merged');
		$source->method('isReadable')->willReturn(true);

		$copy = $this->createMock(File::class);
		$copy->method('getId')->willReturn(99);
		$copy->method('getName')->willReturn('Samengevoegd.pdf');

		$targetFolder = $this->createMock(Folder::class);
		$targetFolder->method('isCreatable')->willReturn(true);
		$targetFolder->method('getNonExistingName')->willReturnArgument(0);
		$targetFolder->method('newFile')->willReturnCallback(
			function (string $name, string $content) use ($copy): File {
				$this->written[$name] = $content;

				return $copy;
			}
		);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getFirstNodeById')->willReturnCallback(static fn (int $id): ?File => ($id === 11 ? $source : null));
		$userFolder->method('get')->willReturnCallback(static fn (string $path): Folder => $targetFolder);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($userFolder);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('anna');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$audit = $this->createMock(AuditTrailMapper::class);
		$audit->method('recordObjectEvent')->willReturnCallback(
			function (ObjectAuditEvent $event): AuditTrail {
				$this->audited[] = $event->action . ' ' . $event->object->getUuid();
				$this->contexts[$event->action] = $event->context;

				return new AuditTrail();
			}
		);

		return new DocumentCopyService(
			objectResolver: $resolver,
			recordGate: $gate,
			placement: $placement,
			documents: new FinalDocumentRepository($resolver, $this->createMock(LoggerInterface::class)),
			rootFolder: $root,
			userSession: $session,
			auditTrail: $audit,
			logger: $this->createMock(LoggerInterface::class)
		);

	}//end service()

	/**
	 * A merged PDF goes to a related case: same name and content, linked to B, audited on both.
	 *
	 * @return void
	 */
	public function testACopyLandsOnTheTargetRecord(): void {
		$result = $this->service()->copy(
			fileId: 11,
			source: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'case-a'],
			target: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'case-b']
		);

		$this->assertSame(expected: ['Samengevoegd.pdf' => '%PDF-merged'], actual: $this->written);
		$this->assertSame(expected: 99, actual: $result['fileId']);
		$this->assertCount(expectedCount: 1, haystack: $this->records);
		$this->assertSame(expected: 99, actual: $this->records[0]['fileId']);
		$this->assertSame(expected: [['register' => 'zaken', 'schema' => 'zaak', 'id' => 'case-b']], actual: $this->records[0]['domains']);
		$this->assertSame(expected: 'anna', actual: $this->records[0]['createdByUser']);
		$this->assertSame(
			expected: [DocumentCopyService::ACTION_COPIED_OUT . ' case-a', DocumentCopyService::ACTION_COPIED_IN . ' case-b'],
			actual: $this->audited
		);
		$this->assertSame(expected: 'case-b', actual: $this->contexts[DocumentCopyService::ACTION_COPIED_OUT]['to']['id']);
		$this->assertSame(expected: 'case-a', actual: $this->contexts[DocumentCopyService::ACTION_COPIED_IN]['from']['id']);

	}//end testACopyLandsOnTheTargetRecord()

	/**
	 * No write on the target, no copy: refused, and nothing written, linked or audited.
	 *
	 * @return void
	 */
	public function testNoWriteOnTheTargetNoCopy(): void {
		try {
			$this->service(mayWriteTarget: false)->copy(
				fileId: 11,
				source: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'case-a'],
				target: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'case-b']
			);
			$this->fail(message: 'The copy must be refused.');
		} catch (DomainLinkRefusedException $e) {
			$this->assertSame(expected: 'You may not change the documents of that record.', actual: $e->getMessage());
		}

		$this->assertSame(expected: [], actual: $this->written);
		$this->assertSame(expected: [], actual: $this->records);
		$this->assertSame(expected: [], actual: $this->audited);

	}//end testNoWriteOnTheTargetNoCopy()

	/**
	 * A source the caller cannot read, or a file they cannot see, is refused before anything is written.
	 *
	 * @return void
	 */
	public function testAnUnreadableSourceIsRefused(): void {
		$cases = [
			['service' => $this->service(mayReadSource: false), 'fileId' => 11],
			['service' => $this->service(), 'fileId' => 12],
		];
		foreach ($cases as $case) {
			try {
				$case['service']->copy(
					fileId: $case['fileId'],
					source: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'case-a'],
					target: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'case-b']
				);
				$this->fail(message: 'The copy must be refused.');
			} catch (DomainLinkRefusedException $e) {
				$this->assertSame(expected: 'You may not read that document.', actual: $e->getMessage());
			}
		}

		$this->assertSame(expected: [], actual: $this->written);
		$this->assertSame(expected: [], actual: $this->audited);

	}//end testAnUnreadableSourceIsRefused()
}//end class
