<?php

/**
 * Unit tests for NativeSigningProvider session lifecycle.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\Signing\NativeSigningProvider;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Session status, cancellation and persistence edges of the native provider.
 *
 * @covers \OCA\Filinq\Service\Signing\NativeSigningProvider
 * @uses   \OCA\Filinq\Service\Signing\SessionRowMatcher
 * @uses   \OCA\Filinq\Service\Signing\AssertionCanonicalizer
 * @uses   \OCA\Filinq\Service\Signing\FieldPlacementRenderer
 */
class NativeSigningProviderSessionTest extends TestCase {

	/**
	 * The object service double.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * The logger double.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * Set up the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->objects = $this->getMockBuilder(ObjectService::class)
			->disableOriginalConstructor()
			->onlyMethods(['findAll', 'saveObject'])
			->getMock();
		$this->logger = $this->createMock(LoggerInterface::class);
	}//end setUp()

	/**
	 * Build the provider.
	 *
	 * @param bool $withRegister Whether OpenRegister is available.
	 *
	 * @return NativeSigningProvider
	 */
	private function provider(bool $withRegister = true): NativeSigningProvider {
		$settings = $this->createMock(SettingsService::class);
		$objects = null;
		if ($withRegister === true) {
			$objects = $this->objects;
		}

		$settings->method('getObjectService')->willReturn($objects);
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(static fn (string $app, string $key, string $default = ''): string => $default);

		return new NativeSigningProvider(logger: $this->logger, settingsService: $settings, config: $config);
	}//end provider()

	/**
	 * Let findAll answer with these rows.
	 *
	 * @param array<int, array<string, mixed>> $rows The rows.
	 *
	 * @return void
	 */
	private function rows(array $rows): void {
		$this->objects->method('findAll')->willReturn($rows);
	}//end rows()

	/**
	 * The identifier is "native" and only SES is supported.
	 *
	 * @return void
	 */
	public function testIdentityAndLevels(): void {
		$provider = $this->provider();

		$this->assertSame('native', $provider->getIdentifier());
		$this->assertTrue($provider->supportsLevel(level: 'SES'));
		$this->assertFalse($provider->supportsLevel(level: 'AES'));
	}//end testIdentityAndLevels()

	/**
	 * Initiating at another level is refused before anything is stored.
	 *
	 * @return void
	 */
	public function testInitiateRefusesUnsupportedLevel(): void {
		$this->objects->expects($this->never())->method('saveObject');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Native provider only supports SES signature level, got: QES');

		$this->provider()->initiateSigning(documentPath: '/a.pdf', documentName: 'a', signers: [], level: 'QES');
	}//end testInitiateRefusesUnsupportedLevel()

	/**
	 * Without OpenRegister a session cannot be created.
	 *
	 * @return void
	 */
	public function testInitiateWithoutRegisterIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('OpenRegister is not available; cannot persist signing session');

		$this->provider(withRegister: false)->initiateSigning(documentPath: '/a.pdf', documentName: 'a', signers: [], level: 'SES');
	}//end testInitiateWithoutRegisterIsRefused()

	/**
	 * Status reads the stored session and fills defaults; the query filters on register, schema and id.
	 *
	 * @return void
	 */
	public function testCheckStatusFillsDefaults(): void {
		$this->objects->expects($this->once())->method('findAll')
			->with(['filters' => ['register' => 'filinq', 'schema' => 'signingSession', 'externalId' => 'native-1']], false)
			->willReturn([['externalId' => 'native-1', 'signers' => [['name' => 'A']]]]);

		$this->assertSame(
			['status' => 'pending', 'signers' => [['name' => 'A']], 'signatures' => [], 'completedAt' => null],
			$this->provider()->checkStatus(externalId: 'native-1')
		);
	}//end testCheckStatusFillsDefaults()

	/**
	 * A pending session cannot be downloaded.
	 *
	 * @return void
	 */
	public function testDownloadRefusesAPendingSession(): void {
		$this->rows(rows: [['externalId' => 'native-2', 'status' => 'pending', 'signedDocumentPath' => '/s.pdf', 'markerEmbedded' => true]]);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Signing session is not completed');

		$this->provider()->downloadSignedDocument(externalId: 'native-2');
	}//end testDownloadRefusesAPendingSession()

	/**
	 * Cancelling a pending session stores it as cancelled under its existing uuid.
	 *
	 * @return void
	 */
	public function testCancelStoresTheCancelledSessionUnderItsUuid(): void {
		$this->rows(rows: [['externalId' => 'native-3', 'status' => 'pending', 'uuid' => 'or-uuid']]);
		$this->objects->expects($this->once())->method('saveObject')->with(
			['externalId' => 'native-3', 'status' => 'cancelled', 'uuid' => 'or-uuid', 'id' => 'or-uuid'],
			'filinq',
			'signingSession',
			null,
			false,
			true,
			true
		);

		$this->provider()->cancelSigning(externalId: 'native-3');
	}//end testCancelStoresTheCancelledSessionUnderItsUuid()

	/**
	 * A row identified only by id keeps that id on update.
	 *
	 * @return void
	 */
	public function testCancelKeepsAnIdOnlyRow(): void {
		$this->rows(rows: [['externalId' => 'native-4', 'id' => 77]]);
		$this->objects->expects($this->once())->method('saveObject')->with(
			$this->callback(static fn (array $s): bool => $s['id'] === '77' && $s['status'] === 'cancelled')
		);

		$this->provider()->cancelSigning(externalId: 'native-4');
	}//end testCancelKeepsAnIdOnlyRow()

	/**
	 * Cancelling twice is a no-op; a completed session cannot be cancelled.
	 *
	 * @return void
	 */
	public function testCancelIsIdempotentAndRefusesCompleted(): void {
		$this->objects->method('findAll')->willReturnCallback(
			static fn (array $config): array => [
				['externalId' => 'gone', 'status' => 'cancelled'],
				['externalId' => 'done', 'status' => 'completed'],
			]
		);
		$this->objects->expects($this->never())->method('saveObject');
		$provider = $this->provider();

		$provider->cancelSigning(externalId: 'gone');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('This signing request is already completed and cannot be withdrawn.');

		$provider->cancelSigning(externalId: 'done');
	}//end testCancelIsIdempotentAndRefusesCompleted()

	/**
	 * A failing lookup is logged and reads as "not found".
	 *
	 * @return void
	 */
	public function testLookupFailureReadsAsNotFound(): void {
		$this->objects->method('findAll')->willThrowException(new RuntimeException('db down'));
		$this->logger->expects($this->once())->method('error')
			->with('Failed to load signing session native-5: db down', $this->arrayHasKey('exception'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Native signing session not found: native-5');

		$this->provider()->checkStatus(externalId: 'native-5');
	}//end testLookupFailureReadsAsNotFound()
}//end class
