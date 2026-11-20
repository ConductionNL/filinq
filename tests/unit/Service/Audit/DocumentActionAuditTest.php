<?php

/**
 * Unit tests for DocumentActionAudit
 *
 * Every action on a document is written into the OpenRegister audit trail of
 * the document's record, and the trail reads back in time order with actor
 * and moment (specify-capability-rows REQ-SCR-007).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Audit
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Audit;

use InvalidArgumentException;
use OCA\Filinq\Service\Audit\DocumentActionAudit;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FinalDocumentRepository;
use OCA\OpenRegister\Db\AuditTrail;
use OCA\OpenRegister\Db\AuditTrail\ObjectAuditEvent;
use OCA\OpenRegister\Db\AuditTrailMapper;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\Audit\DocumentActionAudit
 */
#[CoversClass(DocumentActionAudit::class)]
class DocumentActionAuditTest extends TestCase {

	/**
	 * The entries written, as "<action> <object uuid>".
	 *
	 * @var list<string>
	 */
	private array $written = [];

	/**
	 * The context of the last entry written.
	 *
	 * @var array<string, mixed>
	 */
	private array $context = [];

	/**
	 * The actions a document's trail records.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function actions(): array {
		$cases = [];
		foreach (DocumentActionAudit::ACTIONS as $action) {
			$cases[$action] = [$action];
		}

		return $cases;

	}//end actions()

	/**
	 * Each action writes exactly one entry on the record of the file's current content.
	 *
	 * @param string $action The action.
	 *
	 * @return void
	 */
	#[DataProvider('actions')]
	public function testEachActionWritesOneEntry(string $action): void {
		$records = [['uuid' => 'rec-old', 'versionLabel' => '1700000000'], ['uuid' => 'rec-now', 'versionLabel' => '']];

		$written = $this->audit(records: $records)->record(fileId: 41, action: $action, context: ['via' => 'test']);

		$this->assertTrue($written);
		$this->assertSame(['filinq.document.' . $action . ' rec-now'], $this->written);
		$this->assertSame(['fileId' => 41, 'via' => 'test'], $this->context);

	}//end testEachActionWritesOneEntry()

	/**
	 * A file without a document record gets no entry: there is no trail to write to.
	 *
	 * @return void
	 */
	public function testAFileWithoutARecordGetsNoEntry(): void {
		$this->assertFalse($this->audit(records: [])->record(fileId: 41, action: 'edited'));
		$this->assertFalse($this->audit(records: null)->record(fileId: 41, action: 'edited'));
		$this->assertSame([], $this->written);

	}//end testAFileWithoutARecordGetsNoEntry()

	/**
	 * An action outside the list is refused before anything is read.
	 *
	 * @return void
	 */
	public function testAnUnknownActionIsRefused(): void {
		$this->expectException(InvalidArgumentException::class);

		$this->audit(records: [['uuid' => 'rec-now', 'versionLabel' => '']])->record(fileId: 41, action: 'printed');

	}//end testAnUnknownActionIsRefused()

	/**
	 * A failing write answers false and is logged, never thrown.
	 *
	 * @return void
	 */
	public function testAFailingWriteIsLoggedNotThrown(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('error');

		$audit = $this->audit(records: [['uuid' => 'rec-now', 'versionLabel' => '']], failWrite: true, logger: $logger);

		$this->assertFalse($audit->record(fileId: 41, action: 'shared'));

	}//end testAFailingWriteIsLoggedNotThrown()

	/**
	 * The trail of a registered, signed and downloaded document reads in time order with actor and moment.
	 *
	 * @return void
	 */
	public function testTheTrailReadsInTimeOrder(): void {
		$rows = [
			$this->row(action: 'filinq.document.downloaded', user: 'bea', created: '2026-11-03T10:00:00+00:00'),
			$this->row(action: 'create', user: 'anna', created: '2026-11-01T09:00:00+00:00'),
			$this->row(action: 'filinq.document.signed', user: 'carl', created: '2026-11-02T12:00:00+00:00'),
		];
		$mapper = $this->createMock(AuditTrailMapper::class);
		$asked = [];
		$mapper->expects($this->once())->method('findAll')->willReturnCallback(
			static function (...$arguments) use (&$asked, $rows): array {
				$asked = $arguments;

				return $rows;
			}
		);

		$trail = $this->audit(records: [['uuid' => 'rec-now', 'versionLabel' => '']], mapper: $mapper)->trail(fileId: 41);

		$this->assertSame(
			[
				['id' => 'row-anna', 'action' => 'create', 'user' => 'anna', 'userName' => 'Name of anna', 'created' => '2026-11-01T09:00:00+00:00'],
				['id' => 'row-carl', 'action' => 'filinq.document.signed', 'user' => 'carl', 'userName' => 'Name of carl', 'created' => '2026-11-02T12:00:00+00:00'],
				['id' => 'row-bea', 'action' => 'filinq.document.downloaded', 'user' => 'bea', 'userName' => 'Name of bea', 'created' => '2026-11-03T10:00:00+00:00'],
			],
			$trail
		);
		$this->assertSame([DocumentActionAudit::TRAIL_LIMIT, null, ['object_uuid' => 'rec-now'], ['created' => 'ASC'], null], $asked);

	}//end testTheTrailReadsInTimeOrder()

	/**
	 * Without a record the trail is empty and the audit trail is not read.
	 *
	 * @return void
	 */
	public function testWithoutARecordTheTrailIsEmpty(): void {
		$mapper = $this->createMock(AuditTrailMapper::class);
		$mapper->expects($this->never())->method('findAll');

		$this->assertSame([], $this->audit(records: [], mapper: $mapper)->trail(fileId: 41));

	}//end testWithoutARecordTheTrailIsEmpty()

	/**
	 * One serialised audit row.
	 *
	 * @param string $action  The action.
	 * @param string $user    The actor.
	 * @param string $created The moment.
	 *
	 * @return AuditTrail
	 */
	private function row(string $action, string $user, string $created): AuditTrail {
		return new class($action, $user, $created) extends AuditTrail {

			/**
			 * Constructor.
			 *
			 * @param string $rowAction  The action.
			 * @param string $rowUser    The actor.
			 * @param string $rowCreated The moment.
			 */
			public function __construct(
				private readonly string $rowAction,
				private readonly string $rowUser,
				private readonly string $rowCreated,
			) {
			}

			/**
			 * The row as OpenRegister serialises it.
			 *
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return ['uuid' => 'row-' . $this->rowUser, 'action' => $this->rowAction, 'user' => $this->rowUser, 'userName' => 'Name of ' . $this->rowUser, 'created' => $this->rowCreated, 'changed' => ['title' => 'x']];
			}
		};

	}//end row()

	/**
	 * The audit over these document records.
	 *
	 * @param array<int, array<string, mixed>>|null $records   The records of file 41, or null when unreadable.
	 * @param bool                                  $failWrite Whether the audit write throws.
	 * @param AuditTrailMapper|null                 $mapper    A mapper double to use instead.
	 * @param LoggerInterface|null                  $logger    A logger double to use instead.
	 *
	 * @return DocumentActionAudit
	 */
	private function audit(?array $records, bool $failWrite = false, ?AuditTrailMapper $mapper = null, ?LoggerInterface $logger = null): DocumentActionAudit {
		$documents = $this->createMock(FinalDocumentRepository::class);
		$documents->method('findForFile')->with(fileId: 41)->willReturn($records);

		$objects = $this->createMock(ObjectService::class);
		$objects->method('find')->willReturnCallback(
			static function (...$arguments): ObjectEntity {
				$entity = new ObjectEntity();
				$entity->setUuid((string) ($arguments['id'] ?? $arguments[0]));

				return $entity;
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		if ($mapper === null) {
			$mapper = $this->createMock(AuditTrailMapper::class);
			$mapper->method('recordObjectEvent')->willReturnCallback(
				function (ObjectAuditEvent $event) use ($failWrite): AuditTrail {
					if ($failWrite === true) {
						throw new RuntimeException('audit store gone');
					}

					$this->written[] = $event->action . ' ' . $event->object->getUuid();
					$this->context = $event->context;

					return new AuditTrail();
				}
			);
		}

		return new DocumentActionAudit($documents, $resolver, $mapper, $logger ?? $this->createMock(LoggerInterface::class));

	}//end audit()
}//end class
