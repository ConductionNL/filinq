<?php

/**
 * Golden tests for the signing envelope service
 *
 * Pins who may read, sign and cancel an envelope, and exactly what create
 * hands to the signing service and stores, so the service can be split into
 * named steps without changing what it allows or refuses.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\SigningEnvelope
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SigningEnvelope;

use OCA\Filinq\Service\SigningEnvelope\SigningEnvelopeRepository;
use OCA\Filinq\Service\SigningEnvelope\SigningEnvelopeService;
use OCA\Filinq\Service\SigningService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Allowed and refused outcomes of SigningEnvelopeService.
 *
 * @covers \OCA\Filinq\Service\SigningEnvelope\SigningEnvelopeService
 * @uses   \OCA\Filinq\Service\SigningEnvelope\SigningEnvelopeRollUp
 * @uses \OCA\Filinq\Service\SigningEnvelope\EnvelopeCaller
 * @uses \OCA\Filinq\Service\SigningEnvelope\SigningEnvelopeDraft
 */
final class SigningEnvelopeServiceGoldenTest extends TestCase {

	private const ENVELOPE = [
		'uuid' => 'env-1',
		'initiatorUserId' => 'ini',
		'signerUserIds' => ['sig1'],
		'requestRefs' => ['r1', 'r2', 'r3'],
		'status' => 'in_progress',
	];

	/**
	 * The signing service double.
	 *
	 * @var SigningService&MockObject
	 */
	private SigningService&MockObject $signing;

	/**
	 * The envelope storage double.
	 *
	 * @var SigningEnvelopeRepository&MockObject
	 */
	private SigningEnvelopeRepository&MockObject $repository;

	/**
	 * Fresh doubles over one stored envelope whose members are PENDING, COMPLETED and IN_PROGRESS.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->signing = $this->createMock(SigningService::class);
		$this->repository = $this->createMock(SigningEnvelopeRepository::class);
		$stored = ['env-1' => self::ENVELOPE];
		$this->repository->method('find')->willReturnCallback(static fn (string $uuid): ?array => ($stored[$uuid] ?? null));
		$statuses = ['r1' => 'PENDING', 'r2' => 'COMPLETED', 'r3' => 'IN_PROGRESS'];
		$this->signing->method('getRequest')->willReturnCallback(
			static fn (string $requestId): array => [
				'status' => ($statuses[$requestId] ?? 'PENDING'),
				'documentName' => 'd-' . $requestId,
				'documentFileId' => 7,
			]
		);

	}//end setUp()

	/**
	 * The initiator, a named signer and an admin may read it; anybody else, or nobody, may not.
	 *
	 * @return void
	 */
	public function testWhoMayReadAnEnvelope(): void {
		$service = $this->service();
		foreach ([['ini', false], ['sig1', false], ['other', true]] as [$userId, $isAdmin]) {
			$this->assertSame('env-1', $service->get('env-1', $userId, $isAdmin)['uuid'] ?? null, $userId);
		}

		foreach ([['other', false], ['', true], ['', false]] as [$userId, $isAdmin]) {
			$this->assertNull($service->get('env-1', $userId, $isAdmin), $userId);
		}

		$this->assertNull($service->get('env-2', 'ini', true));

		$read = $service->get('env-1', 'sig1', false);
		$expected = [
			['id' => 'r1', 'documentName' => 'd-r1', 'documentFileId' => '7', 'status' => 'PENDING'],
			['id' => 'r2', 'documentName' => 'd-r2', 'documentFileId' => '7', 'status' => 'COMPLETED'],
			['id' => 'r3', 'documentName' => 'd-r3', 'documentFileId' => '7', 'status' => 'IN_PROGRESS'],
		];
		$this->assertSame($expected, $read['members']);

	}//end testWhoMayReadAnEnvelope()

	/**
	 * Only a signer the envelope names may sign all; the open members go to bulkSign.
	 *
	 * @return void
	 */
	public function testOnlyANamedSignerMaySignAll(): void {
		$this->signing->expects($this->once())->method('bulkSign')->with(['r1', 'r3'])->willReturn(['r1' => 'ok']);
		$service = $this->service();

		foreach (['ini', 'other', ''] as $userId) {
			$this->assertNull($service->signAll('env-1', $userId), $userId);
		}

		$this->assertNull($service->signAll('env-2', 'sig1'));
		$signed = $service->signAll('env-1', 'sig1');
		$this->assertSame(['r1' => 'ok'], $signed['results']);
		$this->assertSame('env-1', $signed['envelope']['uuid']);

	}//end testOnlyANamedSignerMaySignAll()

	/**
	 * A named signer and an outsider may not cancel; the initiator and an admin may, and only open members are cancelled.
	 *
	 * @return void
	 */
	public function testOnlyTheInitiatorOrAnAdminMayCancel(): void {
		$this->signing->expects($this->never())->method('cancelRequest');
		$this->repository->expects($this->never())->method('save');
		$service = $this->service();
		$this->assertNull($service->cancel('env-1', 'sig1', false));
		$this->assertNull($service->cancel('env-1', 'other', false));
		$this->assertNull($service->cancel('env-1', '', true));

		foreach ([['ini', false], ['other', true]] as [$userId, $isAdmin]) {
			$this->setUp();
			$cancelled = [];
			$this->signing->method('cancelRequest')->willReturnCallback(static function (string $requestId) use (&$cancelled): ?array {
				$cancelled[] = $requestId;
				if ($requestId === 'r3') {
					throw new RuntimeException('signed meanwhile');
				}

				return null;
			});
			$saved = [];
			$this->repository->method('save')->willReturnCallback(static function (array $envelope, string $uuid) use (&$saved): array {
				$saved[] = [$uuid, $envelope['status']];
				return $envelope;
			});

			$result = $this->service()->cancel('env-1', $userId, $isAdmin);

			$this->assertSame(['r1', 'r3'], $cancelled, $userId);
			$this->assertSame([['env-1', 'cancelled']], $saved, $userId);
			$this->assertSame(1, $result['cancelledRequests']);
			$this->assertSame('cancelled', $result['status']);
		}

	}//end testOnlyTheInitiatorOrAnAdminMayCancel()

	/**
	 * The documents list must hold two to the maximum documents, each with its own file id.
	 *
	 * @return void
	 */
	public function testTheDocumentsAreValidated(): void {
		$this->signing->expects($this->never())->method('createRequest');
		$this->repository->expects($this->never())->method('save');
		$tooMany = array_fill(0, 26, ['documentFileId' => 'x']);
		$cases = [
			'An envelope holds 2 to 25 documents' => [null, 'x', [['documentFileId' => 1]], $tooMany],
			'Document 2 needs a file id of its own' => [
				[['documentFileId' => 1], ['documentFileId' => ' ']],
				[['documentFileId' => 1], ['documentFileId' => ' 1 ']],
				['a' => ['documentFileId' => 1], 'b' => 'not a document'],
			],
		];
		foreach ($cases as $message => $values) {
			foreach ($values as $documents) {
				try {
					$this->service()->create(['documents' => $documents], 'ini');
					$this->fail('accepted ' . json_encode($documents));
				} catch (RuntimeException $e) {
					$this->assertSame([400, $message], [$e->getCode(), $e->getMessage()]);
				}
			}
		}

	}//end testTheDocumentsAreValidated()

	/**
	 * Create hands each document plus the shared fields to createRequest and stores the envelope last.
	 *
	 * @return void
	 */
	public function testCreateBuildsTheMembersAndTheEnvelope(): void {
		$created = [];
		$this->signing->method('createRequest')->willReturnCallback(static function (array $data) use (&$created): array {
			$created[] = $data;
			return ['uuid' => 'm' . count($created)];
		});
		$stored = [];
		$this->repository->method('save')->willReturnCallback(static function (array $envelope, string $uuid) use (&$stored): array {
			$stored[] = [$envelope, $uuid];
			return $envelope + ['uuid' => $uuid];
		});

		$input = [
			'title' => '  ',
			'documents' => [['documentFileId' => ' 11 ', 'documentName' => ' '], ['documentFileId' => 12, 'documentName' => ' Brief ']],
			'signers' => [['userId' => ' bob ', 'role' => 'a'], ['email' => 'e@x'], ['userId' => 'bob'], ['userId' => 'carol']],
			'signatureLevel' => 'AES',
			'signingMode' => '',
			'deadline' => '2026-12-01',
			'unknown' => 'dropped',
		];
		$this->service()->create($input, 'ini');

		$uuid = $stored[0][1];
		$this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $uuid);
		$signers = [
			['envelopeRef' => $uuid, 'userId' => ' bob ', 'role' => 'a'],
			['envelopeRef' => $uuid, 'email' => 'e@x'],
			['envelopeRef' => $uuid, 'userId' => 'bob'],
			['envelopeRef' => $uuid, 'userId' => 'carol'],
		];
		$common = ['signers' => $signers, 'envelopeRef' => $uuid, 'signatureLevel' => 'AES', 'deadline' => '2026-12-01'];
		$this->assertSame(
			[['documentFileId' => '11', 'documentName' => '11'] + $common, ['documentFileId' => '12', 'documentName' => 'Brief'] + $common],
			$created
		);

		$envelope = $stored[0][0];
		$this->assertMatchesRegularExpression('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d\+00:00$/', $envelope['createdAt']);
		unset($envelope['createdAt']);
		$this->assertSame(
			[
				'title' => '11',
				'requestRefs' => ['m1', 'm2'],
				'initiatorUserId' => 'ini',
				'signerUserIds' => ['bob', 'carol'],
				'documentCount' => 2,
				'status' => 'pending',
			],
			$envelope
		);

	}//end testCreateBuildsTheMembersAndTheEnvelope()

	/**
	 * A long title is cut to 255 characters; a refused member cancels the ones made and names the document.
	 *
	 * @return void
	 */
	public function testTitleLengthAndARefusedMember(): void {
		$stored = [];
		$this->repository->method('save')->willReturnCallback(static function (array $envelope, string $uuid) use (&$stored): array {
			$stored[] = $envelope;
			return $envelope + ['uuid' => $uuid];
		});
		$this->signing->method('createRequest')->willReturn(['id' => 'm']);
		$documents = [['documentFileId' => 1], ['documentFileId' => 2]];
		$this->service()->create(['title' => str_repeat('é', 300), 'documents' => $documents], 'ini');
		$this->assertSame(str_repeat('é', 255), $stored[0]['title']);

		$this->setUp();
		$this->repository->expects($this->never())->method('save');
		$this->signing->method('createRequest')->willReturnCallback(static function (array $data): array {
			if ($data['documentFileId'] === '3') {
				throw new RuntimeException('no file', 404);
			}

			return ['id' => 'm' . $data['documentFileId']];
		});
		$cancelled = [];
		$this->signing->method('cancelRequest')->willReturnCallback(static function (string $requestId) use (&$cancelled): ?array {
			$cancelled[] = $requestId;
			throw new RuntimeException('cannot');
		});
		try {
			$documents = [['documentFileId' => 1], ['documentFileId' => 2], ['documentFileId' => 3, 'documentName' => 'C']];
			$this->service()->create(['documents' => $documents], 'ini');
			$this->fail('accepted');
		} catch (RuntimeException $e) {
			$this->assertSame([400, 'Document 3 (C): no file'], [$e->getCode(), $e->getMessage()]);
		}

		$this->assertSame(['m1', 'm2'], $cancelled);

	}//end testTitleLengthAndARefusedMember()

	/**
	 * The service over the doubles.
	 *
	 * @return SigningEnvelopeService
	 */
	private function service(): SigningEnvelopeService {
		return new SigningEnvelopeService($this->signing, $this->repository);

	}//end service()
}//end class
