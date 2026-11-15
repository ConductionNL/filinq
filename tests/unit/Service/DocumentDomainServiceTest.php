<?php

/**
 * Unit tests for DocumentDomainService
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
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\DomainLinkRefusedException;
use OCA\Filinq\Service\DocumentDomainService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FinalDocumentRepository;
use OCA\Filinq\Service\IntakeAuthorizationGate;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Asserts that a document has domains rather than copies, and that unlinking
 * the last one keeps the record with its creator.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DocumentDomainServiceTest extends TestCase {

	/**
	 * Records the fake OpenRegister was asked to store.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $written = [];

	/**
	 * The targets the write check was asked about, as register/schema/id.
	 *
	 * @var array<int, string>
	 */
	private array $checked = [];

	/**
	 * Build the service over a fake register holding the given records.
	 *
	 * @param array<int, array<string, mixed>> $rows The stored document records.
	 * @param bool $mayWrite Whether the caller may write the record a link points at.
	 *
	 * @return DocumentDomainService The service under test.
	 */
	private function service(array $rows, bool $mayWrite = true): DocumentDomainService {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willReturn($rows);
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

				return ($object + ['uuid' => ($arguments[3] ?? 'record-1')]);
			}
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('anna');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$gate = $this->createMock(originalClassName: IntakeAuthorizationGate::class);
		$gate->method('mayFileOnto')->willReturnCallback(
			callback: function (string $register, string $schema, string $id) use ($mayWrite): bool {
				$this->checked[] = $register . '/' . $schema . '/' . $id;

				return $mayWrite;
			}
		);

		return new DocumentDomainService(
			new FinalDocumentRepository($resolver, $this->createMock(LoggerInterface::class)),
			$session,
			$this->createMock(LoggerInterface::class),
			$gate
		);

	}//end service()

	/**
	 * One advies, three case types, one record.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testOneAdviesServesThreeDomainsAsOneRecord(): void {
		$service = $this->service(
			rows: [
				[
					'uuid' => 'record-1',
					'fileId' => 4711,
					'documentName' => 'Advies.odt',
					'status' => 'draft',
					'domains' => [
						['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1'],
						['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-2'],
					],
				],
			]
		);

		$record = $service->link(
			uuid: 'record-1',
			domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-3']
		);

		$this->assertCount(3, $record['domains']);
		$this->assertCount(1, $this->written, 'A third domain must not make a third record.');

	}//end testOneAdviesServesThreeDomainsAsOneRecord()

	/**
	 * Linking the same domain twice changes nothing.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testLinkingTheSameDomainTwiceChangesNothing(): void {
		$service = $this->service(
			rows: [
				[
					'uuid' => 'record-1',
					'domains' => [['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1']],
				],
			]
		);

		$service->link(uuid: 'record-1', domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1']);

		$this->assertSame([], $this->written);

	}//end testLinkingTheSameDomainTwiceChangesNothing()

	/**
	 * Unlinking the last domain keeps the record, owned by its creator.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testUnlinkingTheLastDomainKeepsTheRecord(): void {
		$service = $this->service(
			rows: [
				[
					'uuid' => 'record-1',
					'documentName' => 'Advies.odt',
					'createdByUser' => 'anna',
					'domains' => [['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1']],
				],
			]
		);

		$record = $service->unlink(
			uuid: 'record-1',
			domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1']
		);

		$this->assertSame([], $record['domains']);
		$this->assertSame('anna', $record['createdByUser']);
		$this->assertSame('Advies.odt', $record['documentName'], 'The record itself must survive the unlink.');

	}//end testUnlinkingTheLastDomainKeepsTheRecord()

	/**
	 * A record with no creator recorded gets one when its last link goes.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testARecordWithoutACreatorGetsOneWhenItIsUnlinked(): void {
		$service = $this->service(
			rows: [
				[
					'uuid' => 'record-1',
					'domains' => [['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1']],
				],
			]
		);

		$record = $service->unlink(
			uuid: 'record-1',
			domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1']
		);

		$this->assertSame('anna', $record['createdByUser']);

	}//end testARecordWithoutACreatorGetsOneWhenItIsUnlinked()

	/**
	 * A domain that does not name all three parts is refused.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testADomainThatNamesLessThanARecordIsRefused(): void {
		$service = $this->service(rows: []);

		$this->expectException(RuntimeException::class);
		$service->link(uuid: 'record-1', domain: ['register' => 'zaken', 'schema' => 'zaak']);

	}//end testADomainThatNamesLessThanARecordIsRefused()

	/**
	 * A person's own documents list holds the records they created.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testMyDocumentsHoldsTheRecordsIMade(): void {
		$service = $this->service(
			rows: [
				['uuid' => 'record-1', 'createdByUser' => 'anna'],
				['uuid' => 'record-2', 'createdByUser' => 'anna'],
			]
		);

		$mine = $service->listMine();

		$this->assertCount(2, $mine);
		$this->assertSame('record-1', $mine[0]['uuid']);

	}//end testMyDocumentsHoldsTheRecordsIMade()

	/**
	 * A link to a record the caller may write is checked against that record, then stored.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.5
	 */
	public function testALinkIsCheckedAgainstTheTargetRecord(): void {
		$service = $this->service(rows: [['uuid' => 'record-1', 'domains' => []]]);

		$record = $service->link(uuid: 'record-1', domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-2']);

		$this->assertSame(expected: ['zaken/zaak/zaak-2'], actual: $this->checked);
		$this->assertSame(expected: [['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-2']], actual: $record['domains']);
		$this->assertCount(expectedCount: 1, haystack: $this->written);

	}//end testALinkIsCheckedAgainstTheTargetRecord()

	/**
	 * A link to a record the caller may not write is refused and stores nothing.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.5
	 */
	public function testALinkToARecordICannotWriteIsRefused(): void {
		$service = $this->service(rows: [['uuid' => 'record-1', 'domains' => []]], mayWrite: false);

		try {
			$service->link(uuid: 'record-1', domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-9']);
			$this->fail(message: 'A link to a record the caller may not write must be refused.');
		} catch (DomainLinkRefusedException $e) {
			$this->assertSame(expected: 'You may not change the documents of that record.', actual: $e->getMessage());
		}

		$this->assertSame(expected: [], actual: $this->written);

	}//end testALinkToARecordICannotWriteIsRefused()

	/**
	 * Removing a link needs the same right on that record, and changes nothing without it.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.5
	 */
	public function testAnUnlinkFromARecordICannotWriteIsRefused(): void {
		$service = $this->service(
			rows: [['uuid' => 'record-1', 'domains' => [['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1']]]],
			mayWrite: false
		);

		$this->expectException(exception: DomainLinkRefusedException::class);
		try {
			$service->unlink(uuid: 'record-1', domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1']);
		} finally {
			$this->assertSame(expected: [], actual: $this->written);
		}

	}//end testAnUnlinkFromARecordICannotWriteIsRefused()
}//end class
