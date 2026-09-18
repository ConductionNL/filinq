<?php

/**
 * Unit tests for IntakeDetachmentService
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
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\IntakeRefusedException;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeAuthorizationGate;
use OCA\Filinq\Service\IntakeDetachmentService;
use OCA\Filinq\Service\IntakeRepository;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Asserts that a document taken off a record lands on the worklist rather than
 * nowhere, carrying the reason and the person, and that a document that never
 * passed through the inbox gets a record here rather than being refused.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class IntakeDetachmentServiceTest extends TestCase {

	/**
	 * Documents the fake OpenRegister was asked to store.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $written = [];

	/**
	 * The uuids those writes were addressed to.
	 *
	 * @var array<int, string|null>
	 */
	private array $writtenTo = [];

	/**
	 * Build the service over a fake register holding the given rows.
	 *
	 * @param array<int, array<string, mixed>> $rows The stored intake documents.
	 * @param bool $mayWrite Whether the user may write the intake register.
	 *
	 * @return IntakeDetachmentService The service under test.
	 */
	private function service(array $rows, bool $mayWrite = true): IntakeDetachmentService {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjects')->willReturnCallback(
			static function (array $query) use ($rows): array {
				$matches = [];
				foreach ($rows as $row) {
					$hit = true;
					foreach ($query as $key => $value) {
						if ($key === '@self') {
							continue;
						}

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
		$objectService->method('saveObject')->willReturnCallback(
			function (...$arguments): array {
				$this->written[] = ($arguments[0] ?? []);
				$this->writtenTo[] = ($arguments[3] ?? null);

				return (($arguments[0] ?? []) + ['uuid' => ($arguments[3] ?? 'intake-new')]);
			}
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		$gate = $this->createMock(IntakeAuthorizationGate::class);
		$gate->method('mayWrite')->willReturn($mayWrite);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('anna');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return new IntakeDetachmentService(
			new IntakeRepository($resolver, $this->createMock(LoggerInterface::class)),
			$gate,
			$session,
			$this->createMock(LoggerInterface::class)
		);

	}//end service()

	/**
	 * A document off the wrong case comes back with the reason and the handler.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testADetachedDocumentReturnsToTheWorklistWithItsReason(): void {
		$service = $this->service(
			rows: [
				[
					'uuid' => 'intake-1',
					'channel' => 'mail',
					'file' => 4711,
					'status' => 'assigned',
					'assignedTo' => ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-7'],
				],
			]
		);

		$detached = $service->detach(fileId: 4711, reason: 'verkeerde zaak');

		$this->assertSame('detached', $detached['status']);
		$this->assertSame('verkeerde zaak', $detached['detachReason']);
		$this->assertSame('anna', $detached['detachedBy']);
		$this->assertSame(['intake-1'], $this->writtenTo, 'The existing record must be updated, not duplicated.');

	}//end testADetachedDocumentReturnsToTheWorklistWithItsReason()

	/**
	 * A document uploaded straight onto a record gets an intake record now.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testADocumentThatNeverHadAnIntakeRecordGetsOne(): void {
		$service = $this->service(rows: []);

		$detached = $service->detach(fileId: 9001, reason: 'hoort hier niet', documentName: 'brief.pdf');

		$this->assertSame('detached', $detached['status']);
		$this->assertSame(9001, $detached['file']);
		$this->assertSame('brief.pdf', $detached['fileName']);
		$this->assertSame([null], $this->writtenTo, 'A record that did not exist is created, not updated.');

	}//end testADocumentThatNeverHadAnIntakeRecordGetsOne()

	/**
	 * Detaching without a reason is refused, and nothing is written.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testDetachingWithoutAReasonIsRefused(): void {
		$service = $this->service(rows: []);

		try {
			$service->detach(fileId: 4711, reason: '  ');
			$this->fail('Detaching must say why.');
		} catch (IntakeRefusedException $refusal) {
			$this->assertSame(400, $refusal->getStatus());
		}

		$this->assertSame([], $this->written);

	}//end testDetachingWithoutAReasonIsRefused()

	/**
	 * Somebody who may not write the intake register may not detach either.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testSomebodyWhoMayNotWriteTheIntakeRegisterMayNotDetach(): void {
		$service = $this->service(rows: [], mayWrite: false);

		try {
			$service->detach(fileId: 4711, reason: 'verkeerde zaak');
			$this->fail('Detaching must be refused without write rights.');
		} catch (IntakeRefusedException $refusal) {
			$this->assertSame(403, $refusal->getStatus());
		}

		$this->assertSame([], $this->written);

	}//end testSomebodyWhoMayNotWriteTheIntakeRegisterMayNotDetach()
}//end class
