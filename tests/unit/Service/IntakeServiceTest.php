<?php

/**
 * Unit tests for IntakeService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Event\IntakeDocumentReceivedEvent;
use OCA\Filinq\Exception\IntakeRefusedException;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeAuthorizationGate;
use OCA\Filinq\Service\IntakeDefaultRuleService;
use OCA\Filinq\Service\IntakeRoutingService;
use OCA\Filinq\Service\PartySuggestionService;
use OCA\Filinq\Service\IntakeFilePlacement;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\IntakeService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Asserts what the intake inbox does with a delivered document, and what it
 * refuses: a second delivery of the same message, an assignment by somebody who
 * may not write the record, a rejection with no reason, and any verb at all on
 * a document that has already left the inbox.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class IntakeServiceTest extends TestCase {

	/**
	 * Objects the fake OpenRegister was asked to store, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $written = [];

	/**
	 * The uuids those writes were addressed to, in the same order.
	 *
	 * @var array<int, string|null>
	 */
	private array $writtenTo = [];

	/**
	 * Build the service over a fake register holding the given rows.
	 *
	 * @param array<int, array<string, mixed>> $rows The stored intake documents.
	 * @param bool $mayWriteIntake Whether the clerk may write the intake schema.
	 * @param bool $mayWriteTarget Whether the clerk may write the target schema.
	 * @param string|null $placedAt The path the file placement reports, or null.
	 *
	 * @return IntakeService The service under test.
	 */
	private function service(
		array $rows,
		bool $mayWriteIntake = true,
		bool $mayWriteTarget = true,
		?string $placedAt = null,
	): IntakeService {
		$objectService = $this->createMock(ObjectService::class);
		// 🔴 `searchObjectsBySlug`, not `searchObjects`: the repository passes
		// the slugs `filinq` and `intakeDocument`, and `searchObjects` reads
		// those as numeric ids and answers zero rows with no error. A double
		// on the wrong method is a double that cannot fail.
		$objectService->method('searchObjectsBySlug')->willReturnCallback(
			static function (string $registerSlug, string $schemaSlug, array $filters) use ($rows): array {
				$matches = [];
				foreach ($rows as $row) {
					$hit = true;
					foreach ($filters as $key => $value) {
						if ((string)($row[$key] ?? '') !== (string)$value) {
							$hit = false;
						}
					}

					if ($hit === true) {
						$matches[] = $row;
					}
				}

				return $matches;
			}
		);
		$objectService->method('find')->willReturnCallback(
			static function (...$arguments) use ($rows): ?array {
				$id = (string)($arguments[0] ?? '');
				foreach ($rows as $row) {
					if ((string)($row['uuid'] ?? '') === $id) {
						return $row;
					}
				}

				return null;
			}
		);
		$objectService->method('saveObject')->willReturnCallback(
			function (...$arguments): array {
				$object = ($arguments[0] ?? []);
				$this->written[] = $object;
				$this->writtenTo[] = ($arguments[3] ?? null);

				return ($object + ['uuid' => ($arguments[3] ?? 'intake-new')]);
			}
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		$repository = new IntakeRepository($resolver, $this->createMock(LoggerInterface::class));

		$gate = $this->createMock(IntakeAuthorizationGate::class);
		$gate->method('mayWrite')->willReturnCallback(
			static function (string $register, string $schema) use ($mayWriteIntake, $mayWriteTarget): bool {
				if ($schema === IntakeRepository::SCHEMA) {
					return $mayWriteIntake;
				}

				return $mayWriteTarget;
			}
		);

		$placement = $this->createMock(IntakeFilePlacement::class);
		$placement->method('place')->willReturn($placedAt);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('anna');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$defaults = $this->createMock(IntakeDefaultRuleService::class);
		$defaults->method('stamp')->willReturnCallback(
			static function (array $document): array {
				$document['stampedDefaults'] = [];
				$document['defaultRule'] = '';

				return $document;
			}
		);

		$routing = $this->createMock(IntakeRoutingService::class);
		$routing->method('apply')->willReturnCallback(
			static function (array $document): array {
				$document['routing'] = '';
				$document['acceptance'] = ['required' => false];

				return $document;
			}
		);

		$parties = $this->createMock(PartySuggestionService::class);
		$parties->method('suggestFor')->willReturn([]);

		return new IntakeService(
			$repository,
			$gate,
			$placement,
			$defaults,
			$routing,
			$parties,
			$session,
			$this->createMock(LoggerInterface::class)
		);

	}//end service()

	/**
	 * One waiting intake document.
	 *
	 * @return array<string, mixed> The row.
	 */
	private function waitingRow(): array {
		return [
			'uuid' => 'intake-1',
			'channel' => 'scan',
			'subject' => 'Bezwaarschrift',
			'sender' => 'Balie postkamer',
			'receivedAt' => '2026-09-18T08:15:00+00:00',
			'file' => 4711,
			'fileName' => 'scan-0001.pdf',
			'sourceRef' => 'scanbatch-0001',
			'status' => 'received',
		];

	}//end waitingRow()

	/**
	 * A scanned file becomes one intake document waiting for a clerk.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testAScannedFileBecomesAWaitingIntakeDocument(): void {
		$service = $this->service(rows: []);

		$stored = $service->receive(
			event: new IntakeDocumentReceivedEvent(
				channel: 'scan',
				fileId: 4711,
				fileName: 'scan-0001.pdf',
				subject: 'Bezwaarschrift',
				sender: 'Balie postkamer',
				sourceRef: 'scanbatch-0001'
			)
		);

		$this->assertSame('received', $stored['status']);
		$this->assertSame('scan', $stored['channel']);
		$this->assertSame(4711, $stored['file']);
		$this->assertCount(1, $this->written);

	}//end testAScannedFileBecomesAWaitingIntakeDocument()

	/**
	 * A second delivery under the same reference does not make a second row.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testASecondDeliveryOfTheSameMessageDoesNotMakeASecondDocument(): void {
		$service = $this->service(rows: [$this->waitingRow()]);

		$stored = $service->receive(
			event: new IntakeDocumentReceivedEvent(
				channel: 'scan',
				fileId: 4711,
				sourceRef: 'scanbatch-0001'
			)
		);

		$this->assertSame('intake-1', $stored['uuid']);
		$this->assertSame([], $this->written);

	}//end testASecondDeliveryOfTheSameMessageDoesNotMakeASecondDocument()

	/**
	 * A channel this app does not accept is refused, not stored.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testAnUnknownChannelIsRefused(): void {
		$service = $this->service(rows: []);

		$this->expectException(IntakeRefusedException::class);
		$service->receive(event: new IntakeDocumentReceivedEvent(channel: 'carrier-pigeon'));

	}//end testAnUnknownChannelIsRefused()

	/**
	 * Assigning records the record, the clerk and the moment, and closes the row.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testAssigningRecordsTheRecordTheClerkAndTheMoment(): void {
		$service = $this->service(
			rows: [$this->waitingRow()],
			placedAt: '/anna/files/Zaken/Z-2026-1/scan-0001.pdf'
		);

		$assigned = $service->assign(
			uuid: 'intake-1',
			target: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-7']
		);

		$this->assertSame('assigned', $assigned['status']);
		$this->assertSame(
			['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-7'],
			$assigned['assignedTo']
		);
		$this->assertSame('anna', $assigned['assignedBy']);
		$this->assertNotSame('', (string)$assigned['assignedAt']);
		$this->assertSame('/anna/files/Zaken/Z-2026-1/scan-0001.pdf', $assigned['filePath']);
		$this->assertSame(['intake-1'], $this->writtenTo);

	}//end testAssigningRecordsTheRecordTheClerkAndTheMoment()

	/**
	 * A clerk who may only read the record cannot assign a document to it.
	 *
	 * The refusal is a 403 and NOTHING is written: a refusal that still stored
	 * the row would leave the document out of the inbox and out of the record
	 * at the same time.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testAssignToARecordTheClerkMayNotWriteIsRefused(): void {
		$service = $this->service(rows: [$this->waitingRow()], mayWriteTarget: false);

		try {
			$service->assign(
				uuid: 'intake-1',
				target: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-7']
			);
			$this->fail('Assigning to a record the clerk may not write must be refused.');
		} catch (IntakeRefusedException $refusal) {
			$this->assertSame(403, $refusal->getStatus());
		}

		$this->assertSame([], $this->written);

	}//end testAssignToARecordTheClerkMayNotWriteIsRefused()

	/**
	 * A clerk who may not write the intake register cannot assign either.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testAssignByAClerkWhoMayNotWriteTheIntakeRegisterIsRefused(): void {
		$service = $this->service(rows: [$this->waitingRow()], mayWriteIntake: false);

		$this->expectException(IntakeRefusedException::class);
		$service->assign(
			uuid: 'intake-1',
			target: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-7']
		);

	}//end testAssignByAClerkWhoMayNotWriteTheIntakeRegisterIsRefused()

	/**
	 * An assignment that does not name a whole record is refused as a bad request.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testAnAssignmentWithoutARecordIsRefused(): void {
		$service = $this->service(rows: [$this->waitingRow()]);

		try {
			$service->assign(uuid: 'intake-1', target: ['register' => 'zaken', 'schema' => 'zaak']);
			$this->fail('An assignment must name the record it goes to.');
		} catch (IntakeRefusedException $refusal) {
			$this->assertSame(400, $refusal->getStatus());
		}

	}//end testAnAssignmentWithoutARecordIsRefused()

	/**
	 * A document that has already left the inbox cannot be assigned again.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testADocumentThatHasLeftTheInboxCannotBeAssignedAgain(): void {
		$row = ($this->waitingRow() + []);
		$row['status'] = 'assigned';
		$service = $this->service(rows: [$row]);

		try {
			$service->assign(
				uuid: 'intake-1',
				target: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-9']
			);
			$this->fail('A document that has left the inbox must not be assigned again.');
		} catch (IntakeRefusedException $refusal) {
			$this->assertSame(409, $refusal->getStatus());
		}

		$this->assertSame([], $this->written);

	}//end testADocumentThatHasLeftTheInboxCannotBeAssignedAgain()

	/**
	 * A rejection records the reason, the clerk and the moment.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testARejectionRecordsTheReasonTheClerkAndTheMoment(): void {
		$service = $this->service(rows: [$this->waitingRow()]);

		$rejected = $service->reject(uuid: 'intake-1', reason: 'Hoort bij een andere gemeente');

		$this->assertSame('rejected', $rejected['status']);
		$this->assertSame('Hoort bij een andere gemeente', $rejected['rejectReason']);
		$this->assertSame('anna', $rejected['rejectedBy']);
		$this->assertNotSame('', (string)$rejected['rejectedAt']);

	}//end testARejectionRecordsTheReasonTheClerkAndTheMoment()

	/**
	 * A rejection without a reason is refused.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testARejectionWithoutAReasonIsRefused(): void {
		$service = $this->service(rows: [$this->waitingRow()]);

		try {
			$service->reject(uuid: 'intake-1', reason: '   ');
			$this->fail('A rejection must say why.');
		} catch (IntakeRefusedException $refusal) {
			$this->assertSame(400, $refusal->getStatus());
		}

		$this->assertSame([], $this->written);

	}//end testARejectionWithoutAReasonIsRefused()

	/**
	 * The inbox lists what is waiting and nothing else.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testTheInboxListsOnlyWhatIsWaiting(): void {
		$assigned = ($this->waitingRow() + []);
		$assigned['uuid'] = 'intake-2';
		$assigned['status'] = 'assigned';

		$service = $this->service(rows: [$this->waitingRow(), $assigned]);

		$waiting = $service->listWaiting();

		$this->assertCount(1, $waiting);
		$this->assertSame('intake-1', $waiting[0]['uuid']);

	}//end testTheInboxListsOnlyWhatIsWaiting()

	/**
	 * An aanvraag with three bijlagen becomes four records that stay together.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testAMessageWithThreeAttachmentsBecomesFourLinkedRecords(): void {
		$service = $this->service(rows: []);

		$documents = $service->receiveMessage(
			message: new IntakeDocumentReceivedEvent(
				channel: 'mail',
				fileId: 100,
				subject: 'Aanvraag',
				sender: 'jan@voorbeeld.nl',
				sourceRef: 'mail-1'
			),
			attachments: [
				new IntakeDocumentReceivedEvent(channel: 'mail', fileId: 101, sourceRef: 'mail-1-a1'),
				new IntakeDocumentReceivedEvent(channel: 'mail', fileId: 102, sourceRef: 'mail-1-a2'),
				new IntakeDocumentReceivedEvent(channel: 'mail', fileId: 103, sourceRef: 'mail-1-a3'),
			]
		);

		$this->assertCount(4, $documents);
		$this->assertArrayNotHasKey('arrivedWith', $documents[0], 'The message itself arrived with nothing.');
		foreach (array_slice($documents, 1) as $attachment) {
			$this->assertSame(
				'intake-new',
				$attachment['arrivedWith'],
				'Every attachment names the message it arrived with.'
			);
		}

	}//end testAMessageWithThreeAttachmentsBecomesFourLinkedRecords()

	/**
	 * An attachment assigned on its own is noted on the message too.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testAnAttachmentAssignedElsewhereIsNotedOnItsMessage(): void {
		$message = $this->waitingRow();
		$message['uuid'] = 'intake-message';
		$attachment = $this->waitingRow();
		$attachment['uuid'] = 'intake-attachment';
		$attachment['arrivedWith'] = 'intake-message';

		$service = $this->service(rows: [$message, $attachment]);

		$service->assign(
			uuid: 'intake-attachment',
			target: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-9']
		);

		$this->assertSame(['intake-attachment', 'intake-message'], $this->writtenTo);
		$note = $this->written[1]['attachmentNotes'][0];
		$this->assertSame('intake-attachment', $note['attachment']);
		$this->assertSame('zaak-9', $note['assignedTo']['id']);

	}//end testAnAttachmentAssignedElsewhereIsNotedOnItsMessage()

	/**
	 * Assigning a message can take everything that arrived with it.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testAssigningAMessageCanTakeItsAttachmentsAlong(): void {
		$message = $this->waitingRow();
		$message['uuid'] = 'intake-message';
		$attachment = $this->waitingRow();
		$attachment['uuid'] = 'intake-attachment';
		$attachment['arrivedWith'] = 'intake-message';

		$service = $this->service(rows: [$message, $attachment]);

		$service->assign(
			uuid: 'intake-message',
			target: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-9'],
			withAttachments: true
		);

		$this->assertContains('intake-attachment', $this->writtenTo);

	}//end testAssigningAMessageCanTakeItsAttachmentsAlong()

	/**
	 * A detached document is waiting again, and can be assigned.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testADetachedDocumentCanBeAssignedAgain(): void {
		$row = $this->waitingRow();
		$row['status'] = 'detached';
		$row['detachReason'] = 'verkeerde zaak';
		$service = $this->service(rows: [$row]);

		$assigned = $service->assign(
			uuid: 'intake-1',
			target: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-8']
		);

		$this->assertSame('assigned', $assigned['status']);

	}//end testADetachedDocumentCanBeAssignedAgain()

	/**
	 * The worklist holds the detached documents, not the waiting ones.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testTheWorklistHoldsTheDetachedDocuments(): void {
		$detached = $this->waitingRow();
		$detached['uuid'] = 'intake-2';
		$detached['status'] = 'detached';

		$service = $this->service(rows: [$this->waitingRow(), $detached]);

		$worklist = $service->listDetached();

		$this->assertCount(1, $worklist);
		$this->assertSame('intake-2', $worklist[0]['uuid']);

	}//end testTheWorklistHoldsTheDetachedDocuments()

	/**
	 * A document that is not in the inbox at all answers 404.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testAnUnknownDocumentAnswersNotFound(): void {
		$service = $this->service(rows: []);

		try {
			$service->reject(uuid: 'intake-nope', reason: 'weg ermee');
			$this->fail('An unknown intake document must be refused.');
		} catch (IntakeRefusedException $refusal) {
			$this->assertSame(404, $refusal->getStatus());
		}

	}//end testAnUnknownDocumentAnswersNotFound()
}//end class
