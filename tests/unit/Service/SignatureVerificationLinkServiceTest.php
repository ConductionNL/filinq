<?php

/**
 * Unit tests for SignatureVerificationLinkService.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-4.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\SignatureVerificationLinkService;
use OCA\Filinq\Service\SigningVerificationService;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Minting and finding verification records by token.
 *
 * @covers \OCA\Filinq\Service\SignatureVerificationLinkService
 * @uses   \OCA\Filinq\Service\SigningVerificationService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class SignatureVerificationLinkServiceTest extends TestCase {

	/**
	 * OpenRegister's object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * The verifier.
	 *
	 * @var SigningVerificationService&MockObject
	 */
	private SigningVerificationService&MockObject $verifier;

	/**
	 * The service under test.
	 *
	 * @var SignatureVerificationLinkService
	 */
	private SignatureVerificationLinkService $service;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->objects = $this->createMock(ObjectService::class);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);
		$this->verifier = $this->createMock(SigningVerificationService::class);

		$this->service = new SignatureVerificationLinkService(objectResolver: $resolver, verifier: $this->verifier);

	}//end setUp()

	/**
	 * Minting stores the outcome under a 128-bit token, system-owned and past RBAC, without the bytes.
	 *
	 * @return void
	 */
	public function testMintStoresTheOutcomeNotTheDocument(): void {
		$bytes = "%PDF-1.7\nsecret contents of the besluit\n%%EOF\n";
		$summary = [['status' => 'verified', 'level' => 'SES', 'method' => 'native', 'signerAsserted' => 'Alice', 'signedAt' => '', 'integrityVerified' => true, 'identityBound' => true]];
		$this->verifier->expects($this->once())->method('summarise')->with($bytes)->willReturn($summary);

		$saved = null;
		$this->objects->expects($this->once())->method('saveObject')->willReturnCallback(
			static function (array $object, string $register, string $schema, ?string $uuid = null, bool $_rbac = true, bool $_multitenancy = true, bool $_unowned = false) use (&$saved): array {
				$saved = ['object' => $object, 'register' => $register, 'schema' => $schema, 'rbac' => $_rbac, 'unowned' => $_unowned];
				return $object + ['@self' => ['id' => 'uuid-1']];
			}
		);

		$record = $this->service->mint(signedBytes: $bytes, fileRef: '11:signed:abcd', fileName: 'besluit.pdf');

		$this->assertSame('filinq', $saved['register']);
		$this->assertSame('signatureVerification', $saved['schema']);
		$this->assertFalse($saved['rbac']);
		$this->assertTrue($saved['unowned']);
		$this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $saved['object']['token']);
		$this->assertSame(hash('sha256', $bytes), $saved['object']['contentHash']);
		$this->assertSame($summary, $saved['object']['signatures']);
		$this->assertSame('11:signed:abcd', $saved['object']['fileRef']);
		$this->assertSame('besluit.pdf', $saved['object']['fileName']);
		$this->assertFalse($saved['object']['revoked']);
		$this->assertArrayNotHasKey('waarmerkRef', $saved['object']);
		$this->assertStringNotContainsString('secret contents', (string)json_encode($saved['object']), 'no document bytes in the record');
		$this->assertSame($saved['object']['token'], $record['token']);
		$this->assertSame('uuid-1', $record['uuid']);

	}//end testMintStoresTheOutcomeNotTheDocument()

	/**
	 * Two tokens minted in sequence are distinct, well formed and not derivable from each other.
	 *
	 * @return void
	 */
	public function testTokensAreNonEnumerable(): void {
		$this->verifier->method('summarise')->willReturn([]);
		$tokens = [];
		$this->objects->method('saveObject')->willReturnCallback(
			static function (array $object) use (&$tokens): array {
				$tokens[] = $object['token'];
				return $object;
			}
		);

		for ($i = 0; $i < 20; $i++) {
			$this->service->mint(signedBytes: 'same bytes', fileRef: 'same-ref', fileName: 'same.pdf');
		}

		$this->assertCount(20, array_unique($tokens), 'the same input never gives the same token');
		foreach ($tokens as $token) {
			$this->assertTrue(SignatureVerificationLinkService::isWellFormed(token: $token));
		}

		// A counter or a timestamp shares its leading digits with the next
		// token; 64 random bits colliding is not a realistic outcome.
		for ($i = 1; $i < count($tokens); $i++) {
			$this->assertNotSame(substr($tokens[$i - 1], 0, 16), substr($tokens[$i], 0, 16), 'not a counter');
		}

	}//end testTokensAreNonEnumerable()

	/**
	 * A seal reference is stored when there is one.
	 *
	 * @return void
	 */
	public function testMintKeepsASealReference(): void {
		$this->verifier->method('summarise')->willReturn([]);
		$this->objects->method('saveObject')->willReturnArgument(0);

		$record = $this->service->mint(signedBytes: 'x', fileRef: 'r', fileName: 'f.pdf', waarmerkRef: 'wm-7');

		$this->assertSame('wm-7', $record['waarmerkRef']);

	}//end testMintKeepsASealReference()

	/**
	 * A failed write is raised, never swallowed.
	 *
	 * @return void
	 */
	public function testAFailedMintThrows(): void {
		$this->verifier->method('summarise')->willReturn([]);
		$this->objects->method('saveObject')->willThrowException(new \Exception('db down'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('db down');

		$this->service->mint(signedBytes: 'x', fileRef: 'r', fileName: 'f.pdf');

	}//end testAFailedMintThrows()

	/**
	 * A malformed or nil token finds nothing without reading the store.
	 *
	 * @return void
	 */
	public function testAMalformedTokenIsNeverLookedUp(): void {
		$this->objects->expects($this->never())->method('searchObjectsBySlug');

		foreach (['', 'abc', str_repeat('A', 32), str_repeat('g', 32), str_repeat('a', 33), SignatureVerificationLinkService::NIL_TOKEN, '%' . str_repeat('a', 31)] as $token) {
			$this->assertNull($this->service->findByToken(token: $token), $token);
		}

	}//end testAMalformedTokenIsNeverLookedUp()

	/**
	 * A token finds only the row whose token matches it exactly.
	 *
	 * @return void
	 */
	public function testFindByTokenMatchesExactly(): void {
		$token = str_repeat('ab', 16);
		$this->objects->expects($this->once())->method('searchObjectsBySlug')
			->with('filinq', 'signatureVerification', ['token' => $token], false)
			->willReturn([
				['token' => str_repeat('cd', 16), 'fileName' => 'other.pdf', '@self' => ['id' => 'u-0']],
				['token' => $token, 'fileName' => 'mine.pdf', '@self' => ['id' => 'u-1']],
			]);

		$record = $this->service->findByToken(token: $token);

		$this->assertSame('mine.pdf', $record['fileName']);
		$this->assertSame('u-1', $record['uuid']);
		$this->assertArrayNotHasKey('@self', $record);

	}//end testFindByTokenMatchesExactly()

	/**
	 * No matching row, or no array at all, is no record.
	 *
	 * @return void
	 */
	public function testNoMatchIsNull(): void {
		$this->objects->method('searchObjectsBySlug')->willReturnOnConsecutiveCalls(
			[['token' => str_repeat('cd', 16)]],
			null
		);

		$this->assertNull($this->service->findByToken(token: str_repeat('ab', 16)));
		$this->assertNull($this->service->findByToken(token: str_repeat('ab', 16)));

	}//end testNoMatchIsNull()

	/**
	 * An unreadable store is raised, not reported as "no such token".
	 *
	 * @return void
	 */
	public function testAnUnreadableStoreThrows(): void {
		$this->objects->method('searchObjectsBySlug')->willThrowException(new \Exception('index gone'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('index gone');

		$this->service->findByToken(token: str_repeat('ab', 16));

	}//end testAnUnreadableStoreThrows()
}//end class
