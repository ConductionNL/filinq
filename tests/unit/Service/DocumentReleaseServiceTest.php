<?php

/**
 * Document Release Service Test
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-5.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\DomainLinkRefusedException;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentReleaseService;
use OCA\Filinq\Service\FinalDocumentRepository;
use OCA\Filinq\Service\IntakeAuthorizationGate;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A document is released only on a record the caller may write and that it is on,
 * and the portal sees a release's document until it is withdrawn.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
#[CoversClass(DocumentReleaseService::class)]
#[UsesClass(FinalDocumentRepository::class)]
#[UsesClass(DomainLinkRefusedException::class)]
class DocumentReleaseServiceTest extends TestCase {

	/**
	 * What OpenRegister was asked to store: object, schema, uuid and the two flags.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $saved = [];

	/**
	 * The records the write check was asked about.
	 *
	 * @var array<int, string>
	 */
	private array $checked = [];

	/**
	 * The service over a fake store.
	 *
	 * @param array<string, array<string, mixed>> $rows     Stored rows by uuid, documents and releases alike.
	 * @param bool                                $mayWrite Whether the caller may write the record.
	 *
	 * @return DocumentReleaseService The service.
	 */
	private function service(array $rows, bool $mayWrite = true): DocumentReleaseService {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('find')->willReturnCallback(
			static fn (...$arguments): ?array => ($rows[(string)($arguments[0] ?? '')] ?? null)
		);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object, string $register, string $schema, ?string $uuid, bool $_rbac, bool $_multitenancy, bool $_unowned): array {
				$this->saved[] = ['object' => $object, 'schema' => $schema, 'uuid' => $uuid, '_rbac' => $_rbac, '_unowned' => $_unowned];

				return ($object + ['uuid' => ($uuid ?? 'release-new')]);
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		$gate = $this->createMock(IntakeAuthorizationGate::class);
		$gate->method('mayFileOnto')->willReturnCallback(
			function (string $register, string $schema, string $id) use ($mayWrite): bool {
				$this->checked[] = $register . '/' . $schema . '/' . $id;

				return $mayWrite;
			}
		);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('anna');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return new DocumentReleaseService(
			objectResolver: $resolver,
			documents: new FinalDocumentRepository($resolver, $this->createMock(LoggerInterface::class)),
			recordGate: $gate,
			userSession: $session
		);

	}//end service()

	/**
	 * A decision on a case, linked to it.
	 *
	 * @return array<string, array<string, mixed>> The stored rows.
	 */
	private function decisionOnCase(): array {
		return [
			'doc-1' => [
				'uuid' => 'doc-1',
				'fileId' => 4711,
				'documentName' => 'Besluit.pdf',
				'status' => 'final',
				'domains' => [['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1']],
			],
		];

	}//end decisionOnCase()

	/**
	 * A release names the document, the record and the requester, and is stored unowned past the cascade.
	 *
	 * @return void
	 */
	public function testAReleaseIsStoredForTheRequester(): void {
		$release = $this->service(rows: $this->decisionOnCase())->release(
			uuid: 'doc-1',
			record: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1'],
			contactRef: ' contact-7 ',
			kind: 'decision'
		);

		$this->assertSame(expected: ['zaken/zaak/zaak-1'], actual: $this->checked);
		$this->assertCount(expectedCount: 1, haystack: $this->saved);
		$this->assertSame(expected: 'documentRelease', actual: $this->saved[0]['schema']);
		$this->assertFalse(condition: $this->saved[0]['_rbac']);
		$this->assertTrue(condition: $this->saved[0]['_unowned']);
		$this->assertSame(expected: 'contact-7', actual: $release['contactRef']);
		$this->assertSame(expected: 'decision', actual: $release['kind']);
		$this->assertSame(expected: 4711, actual: $release['fileId']);
		$this->assertSame(expected: 'Besluit.pdf', actual: $release['documentName']);
		$this->assertSame(expected: 'anna', actual: $release['releasedBy']);
		$this->assertSame(expected: 'zaak-1', actual: $release['recordId']);

	}//end testAReleaseIsStoredForTheRequester()

	/**
	 * A caller who may not write the record releases nothing.
	 *
	 * @return void
	 */
	public function testAReleaseOnARecordICannotWriteIsRefused(): void {
		$service = $this->service(rows: $this->decisionOnCase(), mayWrite: false);

		try {
			$service->release(uuid: 'doc-1', record: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1'], contactRef: 'contact-7');
			$this->fail(message: 'The release must be refused.');
		} catch (DomainLinkRefusedException $e) {
			$this->assertSame(expected: 'You may not change the documents of that record.', actual: $e->getMessage());
		}

		$this->assertSame(expected: [], actual: $this->saved);

	}//end testAReleaseOnARecordICannotWriteIsRefused()

	/**
	 * A document cannot be released on a record it is not on, even by someone who may write that record.
	 *
	 * @return void
	 */
	public function testADocumentIsReleasedOnlyOnItsOwnRecord(): void {
		$service = $this->service(rows: $this->decisionOnCase());

		$this->expectExceptionMessage(message: 'That document is not on that record.');
		try {
			$service->release(uuid: 'doc-1', record: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-2'], contactRef: 'contact-7');
		} finally {
			$this->assertSame(expected: [], actual: $this->saved);
		}

	}//end testADocumentIsReleasedOnlyOnItsOwnRecord()

	/**
	 * A release without a requester or with an unknown kind is refused before any check.
	 *
	 * @return void
	 */
	public function testAnIncompleteReleaseIsRefused(): void {
		$service = $this->service(rows: $this->decisionOnCase());

		foreach ([['contact' => '', 'kind' => 'document'], ['contact' => 'contact-7', 'kind' => 'memo']] as $case) {
			try {
				$service->release(uuid: 'doc-1', record: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1'], contactRef: $case['contact'], kind: $case['kind']);
				$this->fail(message: 'An incomplete release must be refused.');
			} catch (DomainLinkRefusedException $e) {
				$this->fail(message: 'An incomplete release is a bad request, not a refusal of rights.');
			} catch (RuntimeException $e) {
				$this->assertNotSame(expected: '', actual: $e->getMessage());
			}
		}

		$this->assertSame(expected: [], actual: $this->checked);
		$this->assertSame(expected: [], actual: $this->saved);

	}//end testAnIncompleteReleaseIsRefused()

	/**
	 * The portal sees the release's one document, with where its file lives.
	 *
	 * @return void
	 */
	public function testThePortalSeesAReleasedDocument(): void {
		$rows = [
			'rel-1' => [
				'uuid' => 'rel-1',
				'documentName' => 'Besluit.pdf',
				'kind' => 'decision',
				'fileId' => 4711,
				'recordRegister' => 'zaken',
				'recordSchema' => 'zaak',
				'recordId' => 'zaak-1',
				'contactRef' => 'contact-7',
				'releasedAt' => '2026-11-01T10:00:00+00:00',
			],
		];

		$this->assertSame(
			expected: [
				[
					'id' => 'rel-1',
					'title' => 'Besluit.pdf',
					'kind' => 'decision',
					'date' => '2026-11-01T10:00:00+00:00',
					'file' => ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1', 'fileId' => '4711'],
				],
			],
			actual: $this->service(rows: $rows)->portalEntries(releaseId: 'rel-1')
		);

	}//end testThePortalSeesAReleasedDocument()

	/**
	 * A withdrawn or unknown release lists nothing in the portal.
	 *
	 * @return void
	 */
	public function testAWithdrawnReleaseListsNothing(): void {
		$rows = [
			'rel-1' => [
				'uuid' => 'rel-1',
				'documentName' => 'Besluit.pdf',
				'fileId' => 4711,
				'withdrawnAt' => '2026-11-02T10:00:00+00:00',
			],
		];
		$service = $this->service(rows: $rows);

		$this->assertSame(expected: [], actual: $service->portalEntries(releaseId: 'rel-1'));
		$this->assertSame(expected: [], actual: $service->portalEntries(releaseId: 'rel-unknown'));

	}//end testAWithdrawnReleaseListsNothing()

	/**
	 * Withdrawing needs write on the release's record, and stamps who and when.
	 *
	 * @return void
	 */
	public function testAWithdrawalIsCheckedAndStamped(): void {
		$rows = [
			'rel-1' => [
				'uuid' => 'rel-1',
				'documentName' => 'Besluit.pdf',
				'fileId' => 4711,
				'recordRegister' => 'zaken',
				'recordSchema' => 'zaak',
				'recordId' => 'zaak-1',
			],
		];

		$withdrawn = $this->service(rows: $rows)->withdraw(releaseId: 'rel-1');

		$this->assertSame(expected: ['zaken/zaak/zaak-1'], actual: $this->checked);
		$this->assertSame(expected: 'anna', actual: $withdrawn['withdrawnBy']);
		$this->assertNotSame(expected: '', actual: $withdrawn['withdrawnAt']);
		$this->assertSame(expected: 'rel-1', actual: $this->saved[0]['uuid']);

		$this->saved = [];
		$this->expectException(exception: DomainLinkRefusedException::class);
		try {
			$this->service(rows: $rows, mayWrite: false)->withdraw(releaseId: 'rel-1');
		} finally {
			$this->assertSame(expected: [], actual: $this->saved);
		}

	}//end testAWithdrawalIsCheckedAndStamped()
}//end class
