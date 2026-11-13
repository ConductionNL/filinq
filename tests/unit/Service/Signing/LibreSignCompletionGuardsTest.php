<?php

/**
 * LibreSignCompletion guard tests: nothing to read, LibreSign off, and one
 * request failing without stopping the rest.
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

use OCA\Filinq\Kernel\SigningAuditEvent;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SignedArtifactProducer;
use OCA\Filinq\Service\Signing\LibreSignCompletion;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use OCA\Filinq\Service\Signing\SigningProviderInterface;
use OCA\Filinq\Service\SigningAuditService;
use OCA\Filinq\Service\SigningConclusionEmitter;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\Signing\LibreSignCompletion
 * @uses   \OCA\Filinq\Kernel\SigningAuditActor
 * @uses   \OCA\Filinq\Kernel\SigningAuditEvent
 * @uses   \OCA\Filinq\Kernel\SigningAuditSubject
 */
class LibreSignCompletionGuardsTest extends TestCase {

	/**
	 * @var SettingsService&MockObject
	 */
	private SettingsService $settings;

	/**
	 * @var SigningProviderFactory&MockObject
	 */
	private SigningProviderFactory $providers;

	/**
	 * @var SigningAuditService&MockObject
	 */
	private SigningAuditService $audit;

	/**
	 * @var SigningConclusionEmitter&MockObject
	 */
	private SigningConclusionEmitter $emitter;

	/**
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * Build the collaborators.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->settings = $this->createMock(SettingsService::class);
		$this->providers = $this->createMock(SigningProviderFactory::class);
		$this->audit = $this->createMock(SigningAuditService::class);
		$this->emitter = $this->createMock(SigningConclusionEmitter::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The step under test.
	 *
	 * @return LibreSignCompletion
	 */
	private function completion(): LibreSignCompletion {
		return new LibreSignCompletion(
			settings: $this->settings,
			providers: $this->providers,
			producer: $this->createMock(SignedArtifactProducer::class),
			audit: $this->audit,
			emitter: $this->emitter,
			logger: $this->logger
		);

	}//end completion()

	/**
	 * An object service whose findAll answers per status.
	 *
	 * @param array<string, array<int, mixed>> $byStatus Results per status filter
	 * @param array<int, array<string, mixed>> $saved    Collects every saved object
	 *
	 * @return ObjectService&MockObject
	 */
	private function objects(array $byStatus, array &$saved): ObjectService {
		$objects = $this->getMockBuilder(ObjectService::class)
			->disableOriginalConstructor()
			->onlyMethods(['saveObject', 'findAll'])
			->getMock();
		$objects->method('findAll')->willReturnCallback(
			static fn (array $config): array => ($byStatus[$config['filters']['status']] ?? [])
		);
		$objects->method('saveObject')->willReturnCallback(
			static function (array $object) use (&$saved): array {
				$saved[] = $object;
				return $object;
			}
		);

		return $objects;

	}//end objects()

	/**
	 * Without an object service nothing is read and nothing concludes.
	 *
	 * @return void
	 */
	public function testWithoutAnObjectServiceNothingConcludes(): void {
		$this->settings->method('getObjectService')->willReturn(null);
		$this->settings->method('resolveSigningRequestBinding')->willReturn(['register' => 'filinq', 'schema' => 'signingRequest']);
		$this->providers->expects($this->never())->method('getProvider');

		$this->assertSame(0, $this->completion()->syncAll());

	}//end testWithoutAnObjectServiceNothingConcludes()

	/**
	 * Without a signing-request binding nothing is read either.
	 *
	 * @return void
	 */
	public function testWithoutABindingNothingConcludes(): void {
		$saved = [];
		$objects = $this->objects(byStatus: [], saved: $saved);
		$objects->expects($this->never())->method('findAll');
		$this->settings->method('getObjectService')->willReturn($objects);
		$this->settings->method('resolveSigningRequestBinding')->willReturn(null);
		$this->providers->expects($this->never())->method('getProvider');

		$this->assertSame(0, $this->completion()->syncAll());

	}//end testWithoutABindingNothingConcludes()

	/**
	 * When LibreSign is not enabled the factory refuses, and no request is read.
	 *
	 * @return void
	 */
	public function testWithLibreSignOffNothingIsRead(): void {
		$saved = [];
		$objects = $this->objects(byStatus: [], saved: $saved);
		$objects->expects($this->never())->method('findAll');
		$this->settings->method('getObjectService')->willReturn($objects);
		$this->settings->method('resolveSigningRequestBinding')->willReturn(['register' => 'filinq', 'schema' => 'signingRequest']);
		$this->providers->expects($this->once())->method('getProvider')->with('libresign')
			->willThrowException(new RuntimeException('provider not enabled'));

		$this->assertSame(0, $this->completion()->syncAll());

	}//end testWithLibreSignOffNothingIsRead()

	/**
	 * A request whose status check throws is logged and skipped; the next
	 * one, returned as an entity, is still concluded.
	 *
	 * @return void
	 */
	public function testOneFailingRequestDoesNotStopTheRest(): void {
		$entity = new class {
			/**
			 * The entity as OpenRegister serialises it.
			 *
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return ['id' => 'req-2', 'status' => 'IN_PROGRESS', 'provider' => 'libresign', 'externalId' => 'ls-2', 'signatureLevel' => 'AdES'];
			}
		};
		$saved = [];
		$objects = $this->objects(
			byStatus: [
				'PENDING' => [['id' => 'req-1', 'status' => 'PENDING', 'provider' => 'libresign', 'externalId' => 'ls-1']],
				'IN_PROGRESS' => [$entity],
			],
			saved: $saved
		);
		$this->settings->method('getObjectService')->willReturn($objects);
		$this->settings->method('resolveSigningRequestBinding')->willReturn(['register' => 'filinq', 'schema' => 'signingRequest']);

		$provider = $this->createMock(SigningProviderInterface::class);
		$provider->method('checkStatus')->willReturnCallback(
			static function (string $externalId): array {
				if ($externalId === 'ls-1') {
					throw new RuntimeException('LibreSign unreachable');
				}

				return ['status' => 'CANCELLED', 'signers' => []];
			}
		);
		$this->providers->method('getProvider')->willReturn($provider);

		$this->logger->expects($this->once())->method('error')->with(
			'LibreSign request req-1 could not be concluded: LibreSign unreachable',
			$this->callback(static fn (array $context): bool => $context['exception'] instanceof RuntimeException)
		);
		$this->audit->expects($this->once())->method('logEvent')->with(
			$this->callback(static fn (SigningAuditEvent $event): bool => $event->action === 'CANCELLED')
		);
		$this->emitter->expects($this->once())->method('emitIfDelegated')->with(
			$this->callback(static fn (array $request): bool => $request['id'] === 'req-2'),
			'cancelled'
		);

		$this->assertSame(1, $this->completion()->syncAll());
		$this->assertCount(1, $saved);
		$this->assertSame(['req-2', 'CANCELLED'], [$saved[0]['id'], $saved[0]['status']]);

	}//end testOneFailingRequestDoesNotStopTheRest()
}//end class
