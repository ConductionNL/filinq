<?php

/**
 * Unit tests for SigningFolderService
 *
 * The folder a signer opens across every record, and the pass that signs a
 * selection from it (signing-folder-across-cases REQ-SFC-01..04).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\SigningActorResolver;
use OCA\Filinq\Service\SigningFolderService;
use OCA\Filinq\Service\SigningMandateService;
use OCA\Filinq\Service\SigningService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for the signing folder.
 *
 * Every double is built with onlyMethods(), so a method the real class does
 * not have cannot be invented here and pass.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningFolderServiceTest extends TestCase {

	/**
	 * @var SigningService|MockObject
	 */
	private SigningService|MockObject $signingService;

	/**
	 * @var SigningActorResolver|MockObject
	 */
	private SigningActorResolver|MockObject $actorResolver;

	/**
	 * @var SigningMandateService|MockObject
	 */
	private SigningMandateService|MockObject $mandateService;

	/**
	 * @var SigningFolderService
	 */
	private SigningFolderService $service;

	/**
	 * Set up the folder over doubles of the per-request path.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->signingService = $this->getMockBuilder(SigningService::class)
			->disableOriginalConstructor()
			->onlyMethods(['listRequests', 'getRequest', 'sign'])
			->getMock();

		$this->actorResolver = $this->getMockBuilder(SigningActorResolver::class)
			->disableOriginalConstructor()
			->onlyMethods(['findSignerForUser'])
			->getMock();

		$this->mandateService = $this->getMockBuilder(SigningMandateService::class)
			->disableOriginalConstructor()
			->onlyMethods(['maySign', 'assertMaySign', 'typeReference'])
			->getMock();

		// The default instance has no mandate declarations at all, which is
		// the state every instance is in until a consuming app declares one.
		$this->mandateService->method('maySign')->willReturn(true);
		$this->mandateService->method('typeReference')->willReturnCallback(
			static function (array $request): string {
				$app = (string)($request['sourceApp'] ?? '');
				$schema = (string)($request['subjectSchema'] ?? '');
				if ($app === '' || $schema === '') {
					return '';
				}

				return $app . '/' . $schema;
			}
		);

		$this->service = new SigningFolderService(
			signingService: $this->signingService,
			actorResolver: $this->actorResolver,
			mandateService: $this->mandateService
		);

	}//end setUp()

	/**
	 * Build a signing request row as OpenRegister reads one back.
	 *
	 * @param string $id The request id.
	 * @param array<string, mixed> $overrides Fields to override.
	 *
	 * @return array<string, mixed>
	 */
	private function request(string $id, array $overrides = []): array {
		return array_merge(
			[
				'id' => $id,
				'documentName' => 'Besluit ' . $id,
				'documentFileId' => '100' . $id,
				'initiatorUserId' => 'griffier',
				'signatureLevel' => 'AdES',
				'provider' => 'native',
				'status' => 'PENDING',
				'deadline' => '',
				'signerIds' => ['signer-' . $id],
				'sourceApp' => 'dossiq',
				'subjectRegister' => 'zaken',
				'subjectSchema' => 'besluit',
				'subjectId' => 'zaak-' . $id,
				'subjectLabel' => 'Zaak ' . $id,
				'@self' => ['created' => '2026-09-01T09:00:00+00:00'],
			],
			$overrides
		);
	}

	/**
	 * Every pending signature for one signer lands in one folder.
	 *
	 * @return void
	 */
	public function testFortyDecisionsOnNineCasesArriveInOneFolder(): void {
		$requests = [];
		for ($i = 1; $i <= 40; $i++) {
			$requests[] = $this->request((string)$i, ['subjectId' => 'zaak-' . (($i % 9) + 1)]);
		}

		$this->signingService->method('listRequests')->willReturn($requests);
		$this->actorResolver->method('findSignerForUser')->willReturnCallback(
			static fn (array $signerIds, string $userId): ?string => $signerIds[0]
		);

		$folder = $this->service->folder(userId: 'wethouder', limit: 200);

		$this->assertSame(40, $folder['total']);
		$this->assertCount(40, $folder['entries']);

	}//end testFortyDecisionsOnNineCasesArriveInOneFolder()

	/**
	 * Deadline first, age second.
	 *
	 * @return void
	 */
	public function testTheFolderIsOrderedByDeadlineThenAge(): void {
		$this->signingService->method('listRequests')->willReturn(
			[
				$this->request('no-deadline-new', ['@self' => ['created' => '2026-09-10T09:00:00+00:00']]),
				$this->request('late', ['deadline' => '2026-10-01T00:00:00+00:00']),
				$this->request('no-deadline-old', ['@self' => ['created' => '2026-08-01T09:00:00+00:00']]),
				$this->request('soon', ['deadline' => '2026-09-20T00:00:00+00:00']),
			]
		);
		$this->actorResolver->method('findSignerForUser')->willReturnCallback(
			static fn (array $signerIds, string $userId): ?string => $signerIds[0]
		);

		$folder = $this->service->folder(userId: 'wethouder');

		$this->assertSame(
			['soon', 'late', 'no-deadline-old', 'no-deadline-new'],
			array_column($folder['entries'], 'requestId')
		);

	}//end testTheFolderIsOrderedByDeadlineThenAge()

	/**
	 * The folder holds nobody else's work.
	 *
	 * @return void
	 */
	public function testTheFolderHoldsNobodyElsesWork(): void {
		$mine = $this->request('mine', ['signerIds' => ['signer-mine']]);
		$theirs = $this->request('theirs', ['signerIds' => ['signer-theirs']]);

		$this->signingService->method('listRequests')->willReturn([$mine, $theirs]);
		$this->actorResolver->method('findSignerForUser')->willReturnCallback(
			static function (array $signerIds, string $userId): ?string {
				// The resolver answers only for a PENDING signer record
				// belonging to this user; here only signer-mine is theirs.
				return (in_array('signer-mine', $signerIds, true) === true ? 'signer-mine' : null);
			}
		);

		$folder = $this->service->folder(userId: 'wethouder');

		$this->assertSame(['mine'], array_column($folder['entries'], 'requestId'));

	}//end testTheFolderHoldsNobodyElsesWork()

	/**
	 * A request that is no longer open has left the folder, with nothing rebuilt.
	 *
	 * @return void
	 */
	public function testACancelledOrCompletedRequestIsNotInTheFolder(): void {
		$this->signingService->method('listRequests')->willReturn(
			[
				$this->request('open'),
				$this->request('cancelled', ['status' => 'CANCELLED']),
				$this->request('completed', ['status' => 'COMPLETED']),
				$this->request('expired', ['status' => 'EXPIRED']),
			]
		);
		$this->actorResolver->method('findSignerForUser')->willReturnCallback(
			static fn (array $signerIds, string $userId): ?string => $signerIds[0]
		);

		$folder = $this->service->folder(userId: 'wethouder');

		$this->assertSame(['open'], array_column($folder['entries'], 'requestId'));
		$this->assertSame(1, $folder['total']);

	}//end testACancelledOrCompletedRequestIsNotInTheFolder()

	/**
	 * The entry says what is being signed, for which record, by whose request.
	 *
	 * @return void
	 */
	public function testTheEntryCarriesTheContextNeededToSignIt(): void {
		$this->signingService->method('listRequests')->willReturn(
			[$this->request('1', ['deadline' => '2026-09-20T00:00:00+00:00'])]
		);
		$this->actorResolver->method('findSignerForUser')->willReturn('signer-1');

		$entry = $this->service->folder(userId: 'wethouder')['entries'][0];

		$this->assertSame('Besluit 1', $entry['documentName']);
		$this->assertSame('griffier', $entry['requestedBy']);
		$this->assertSame('2026-09-01T09:00:00+00:00', $entry['requestedAt']);
		$this->assertSame('2026-09-20T00:00:00+00:00', $entry['deadline']);
		$this->assertSame('signer-1', $entry['signerId']);
		$this->assertSame(
			['app' => 'dossiq', 'register' => 'zaken', 'schema' => 'besluit', 'id' => 'zaak-1'],
			[
				'app' => $entry['record']['app'],
				'register' => $entry['record']['register'],
				'schema' => $entry['record']['schema'],
				'id' => $entry['record']['id'],
			]
		);

	}//end testTheEntryCarriesTheContextNeededToSignIt()

	/**
	 * The entry references the record; it copies none of the consuming app's fields.
	 *
	 * @return void
	 */
	public function testNoCaseFieldsAreCopiedIntoTheEntry(): void {
		$this->signingService->method('listRequests')->willReturn(
			[
				$this->request(
					'1',
					[
						'applicantBsn' => '999999990',
						'caseSubject' => 'Vergunning voor een dakkapel',
						'internalNotes' => 'Bespreken met de wethouder',
					]
				),
			]
		);
		$this->actorResolver->method('findSignerForUser')->willReturn('signer-1');

		$entry = $this->service->folder(userId: 'wethouder')['entries'][0];
		$serialised = json_encode($entry);

		$this->assertStringNotContainsString('999999990', $serialised);
		$this->assertStringNotContainsString('dakkapel', $serialised);
		$this->assertStringNotContainsString('Bespreken met de wethouder', $serialised);
		$this->assertSame('zaak-1', $entry['record']['id']);

	}//end testNoCaseFieldsAreCopiedIntoTheEntry()

	/**
	 * A document outside the mandate is absent from the folder.
	 *
	 * @return void
	 */
	public function testADocumentOutsideTheMandateIsNotInTheFolder(): void {
		$mandateService = $this->getMockBuilder(SigningMandateService::class)
			->disableOriginalConstructor()
			->onlyMethods(['maySign', 'assertMaySign', 'typeReference'])
			->getMock();
		$mandateService->method('typeReference')->willReturn('dossiq/besluit');
		$mandateService->method('maySign')->willReturnCallback(
			static function (array $request, string $userId): bool {
				return ((string)($request['subjectSchema'] ?? '') !== 'besluit');
			}
		);

		$service = new SigningFolderService(
			signingService: $this->signingService,
			actorResolver: $this->actorResolver,
			mandateService: $mandateService
		);

		$this->signingService->method('listRequests')->willReturn(
			[
				$this->request('besluit'),
				$this->request('brief', ['subjectSchema' => 'brief']),
			]
		);
		$this->actorResolver->method('findSignerForUser')->willReturnCallback(
			static fn (array $signerIds, string $userId): ?string => $signerIds[0]
		);

		$folder = $service->folder(userId: 'beleidsmedewerker');

		$this->assertSame(['brief'], array_column($folder['entries'], 'requestId'));

	}//end testADocumentOutsideTheMandateIsNotInTheFolder()

	/**
	 * The folder pages, and the page is still ordered by deadline.
	 *
	 * @return void
	 */
	public function testTheFolderPages(): void {
		$requests = [];
		for ($i = 1; $i <= 12; $i++) {
			$requests[] = $this->request(
				str_pad((string)$i, 2, '0', STR_PAD_LEFT),
				['deadline' => sprintf('2026-09-%02dT00:00:00+00:00', $i)]
			);
		}

		$this->signingService->method('listRequests')->willReturn($requests);
		$this->actorResolver->method('findSignerForUser')->willReturnCallback(
			static fn (array $signerIds, string $userId): ?string => $signerIds[0]
		);

		$page = $this->service->folder(userId: 'wethouder', limit: 5, offset: 5);

		$this->assertSame(12, $page['total']);
		$this->assertCount(5, $page['entries']);
		$this->assertSame('06', $page['entries'][0]['requestId']);

	}//end testTheFolderPages()

	/**
	 * A folder with no authenticated signer is not a folder.
	 *
	 * @return void
	 */
	public function testAnUnauthenticatedFolderIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No authenticated user');

		$this->service->folder(userId: '');

	}//end testAnUnauthenticatedFolderIsRefused()

	/**
	 * One pass, one signature per document, through the per-request path.
	 *
	 * @return void
	 */
	public function testThePassSignsEachDocumentThroughThePerRequestPath(): void {
		$this->signingService->method('getRequest')->willReturnCallback(
			fn (string $requestId, string $callerUserId = ''): array => $this->request($requestId)
		);
		$this->actorResolver->method('findSignerForUser')->willReturnCallback(
			static fn (array $signerIds, string $userId): ?string => $signerIds[0]
		);

		$signed = [];
		$this->signingService->method('sign')->willReturnCallback(
			static function (
				string $requestId,
				string $signerId,
				?array $verifiedActor = null,
				?array $signatureData = null,
			) use (&$signed): array {
				$signed[] = $requestId;
				return ['id' => $signerId, 'status' => 'SIGNED', 'signedAt' => '2026-09-18T10:00:00+00:00'];
			}
		);

		$result = $this->service->signSelection(requestIds: ['a', 'b', 'c'], userId: 'wethouder');

		$this->assertSame(['a', 'b', 'c'], $signed);
		$this->assertSame(3, $result['signed']);
		$this->assertSame(0, $result['refused']);
		$this->assertSame('2026-09-18T10:00:00+00:00', $result['results'][0]['signedAt']);

	}//end testThePassSignsEachDocumentThroughThePerRequestPath()

	/**
	 * Thirty-eight of forty: a refusal is reported against its own document.
	 *
	 * @return void
	 */
	public function testARefusalIsReportedPerDocumentAndTheRestAreStillSigned(): void {
		$this->signingService->method('getRequest')->willReturnCallback(
			fn (string $requestId, string $callerUserId = ''): array => $this->request($requestId)
		);
		$this->actorResolver->method('findSignerForUser')->willReturnCallback(
			static fn (array $signerIds, string $userId): ?string => $signerIds[0]
		);
		$this->signingService->method('sign')->willReturnCallback(
			static function (
				string $requestId,
				string $signerId,
				?array $verifiedActor = null,
				?array $signatureData = null,
			): array {
				if ($requestId === 'b') {
					throw new RuntimeException('The provider could not produce a verifiable artifact');
				}

				return ['id' => $signerId, 'status' => 'SIGNED', 'signedAt' => '2026-09-18T10:00:00+00:00'];
			}
		);

		$result = $this->service->signSelection(requestIds: ['a', 'b', 'c'], userId: 'wethouder');

		$this->assertSame(2, $result['signed']);
		$this->assertSame(1, $result['refused']);
		$this->assertFalse($result['results'][1]['signed']);
		$this->assertSame(
			'The provider could not produce a verifiable artifact',
			$result['results'][1]['reason']
		);
		$this->assertTrue($result['results'][2]['signed']);

	}//end testARefusalIsReportedPerDocumentAndTheRestAreStillSigned()

	/**
	 * A resumed pass signs what is left and reports what is already done.
	 *
	 * @return void
	 */
	public function testAResumedPassSignsOnlyWhatIsStillPending(): void {
		$this->signingService->method('getRequest')->willReturnCallback(
			fn (string $requestId, string $callerUserId = ''): array => $this->request($requestId)
		);
		// The first document was signed by the interrupted pass, so it no
		// longer carries a pending signer record for this user.
		$this->actorResolver->method('findSignerForUser')->willReturnCallback(
			static function (array $signerIds, string $userId): ?string {
				return (in_array('signer-a', $signerIds, true) === true ? null : $signerIds[0]);
			}
		);
		$this->signingService->method('sign')->willReturn(
			['id' => 'signer', 'status' => 'SIGNED', 'signedAt' => '2026-09-18T10:00:00+00:00']
		);

		$result = $this->service->signSelection(requestIds: ['a', 'b'], userId: 'wethouder');

		$this->assertSame(1, $result['signed']);
		$this->assertFalse($result['results'][0]['signed']);
		$this->assertSame(
			'No signature is pending from you on this document',
			$result['results'][0]['reason']
		);

	}//end testAResumedPassSignsOnlyWhatIsStillPending()

	/**
	 * The pass refuses a document outside the mandate, naming the rule, and signs on.
	 *
	 * @return void
	 */
	public function testThePassRefusesOutsideTheMandateNamingTheRule(): void {
		$mandateService = $this->getMockBuilder(SigningMandateService::class)
			->disableOriginalConstructor()
			->onlyMethods(['maySign', 'assertMaySign', 'typeReference'])
			->getMock();
		$mandateService->method('typeReference')->willReturn('dossiq/besluit');
		$mandateService->method('assertMaySign')->willReturnCallback(
			static function (array $request, string $userId): void {
				if ((string)($request['subjectSchema'] ?? '') === 'besluit') {
					throw new RuntimeException(
						'Signing refused by the mandate declared for dossiq/besluit: '
						. 'Only the portefeuillehouder signs a besluit (held by: portefeuillehouders)'
					);
				}
			}
		);

		$service = new SigningFolderService(
			signingService: $this->signingService,
			actorResolver: $this->actorResolver,
			mandateService: $mandateService
		);

		$this->signingService->method('getRequest')->willReturnCallback(
			fn (string $requestId, string $callerUserId = ''): array => $this->request(
				$requestId,
				['subjectSchema' => ($requestId === 'besluit' ? 'besluit' : 'brief')]
			)
		);
		$this->actorResolver->method('findSignerForUser')->willReturnCallback(
			static fn (array $signerIds, string $userId): ?string => $signerIds[0]
		);
		$this->signingService->method('sign')->willReturn(
			['id' => 'signer', 'status' => 'SIGNED', 'signedAt' => '2026-09-18T10:00:00+00:00']
		);

		$result = $service->signSelection(requestIds: ['besluit', 'brief'], userId: 'beleidsmedewerker');

		$this->assertSame(1, $result['signed']);
		$this->assertStringContainsString(
			'Only the portefeuillehouder signs a besluit',
			$result['results'][0]['reason']
		);
		$this->assertTrue($result['results'][1]['signed']);

	}//end testThePassRefusesOutsideTheMandateNamingTheRule()

	/**
	 * A request the caller may not read is reported without confirming it exists.
	 *
	 * @return void
	 */
	public function testARequestTheCallerMayNotReadIsRefusedWithoutConfirmingIt(): void {
		$this->signingService->method('getRequest')->willReturn(null);

		$result = $this->service->signSelection(requestIds: ['someone-elses'], userId: 'wethouder');

		$this->assertSame(0, $result['signed']);
		$this->assertSame(
			'This signing request is not available to you',
			$result['results'][0]['reason']
		);

	}//end testARequestTheCallerMayNotReadIsRefusedWithoutConfirmingIt()
}//end class
