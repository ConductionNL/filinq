<?php

/**
 * Unit tests for LibreSignCompletion
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/libresign-signing-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Event\SigningConcludedEventFactory;
use OCA\Filinq\Service\FinalDocumentService;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SignedArtifactProducer;
use OCA\Filinq\Service\Signing\LibreSignClient;
use OCA\Filinq\Service\Signing\LibreSignCompletion;
use OCA\Filinq\Service\Signing\LibreSignProvider;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use OCA\Filinq\Service\SigningAuditService;
use OCA\Filinq\Service\SigningConclusionEmitter;
use OCA\OpenRegister\Service\ObjectService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * A LibreSign request concludes when LibreSign says so.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class LibreSignCompletionTest extends TestCase {

	/**
	 * Every object saved, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $saved = [];

	/**
	 * Every audit action written.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $audited = [];

	/**
	 * What the document holds after the run.
	 *
	 * @var string
	 */
	private string $document = '%PDF-original';

	/**
	 * The completion service over a LibreSign that reports the given status.
	 *
	 * @param int   $libreSignStatus The status LibreSign reports
	 * @param array $requests        The open requests in the register
	 *
	 * @return LibreSignCompletion
	 */
	private function completion(int $libreSignStatus, array $requests): LibreSignCompletion {
		$client = $this->createMock(LibreSignClient::class);
		$client->method('validate')->willReturn(['status' => $libreSignStatus, 'nodeId' => 7, 'signers' => []]);
		$client->method('downloadSigned')->willReturn('%PDF-1.7 signed by LibreSign');
		$provider = new LibreSignProvider(config: $this->createMock(IAppConfig::class), client: $client);

		$providers = $this->createMock(SigningProviderFactory::class);
		$providers->method('getProvider')->with('libresign')->willReturn($provider);

		$objects = $this->getMockBuilder(ObjectService::class)
			->disableOriginalConstructor()
			->onlyMethods(['saveObject', 'findAll'])
			->getMock();
		$objects->method('findAll')->willReturnCallback(
			static fn (array $config): array => array_values(
				array_filter($requests, static fn (array $r): bool => $r['status'] === $config['filters']['status'])
			)
		);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object): array {
				$this->saved[] = $object;
				return $object;
			}
		);

		$settings = $this->createMock(SettingsService::class);
		$settings->method('getObjectService')->willReturn($objects);
		$settings->method('resolveSigningRequestBinding')->willReturn(['register' => 'filinq', 'schema' => 'signingRequest']);

		$file = $this->createMock(File::class);
		$file->method('getContent')->willReturnCallback(fn (): string => $this->document);
		$file->method('putContent')->willReturnCallback(
			function (string $bytes): void {
				$this->document = $bytes;
			}
		);
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturn([$file]);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->with('alice')->willReturn($folder);

		$producer = new SignedArtifactProducer(
			providerFactory: $providers,
			userSession: $this->createMock(IUserSession::class),
			request: $this->createMock(IRequest::class),
			rootFolder: $root,
			finalDocuments: $this->createMock(FinalDocumentService::class)
		);

		$audit = $this->createMock(SigningAuditService::class);
		$audit->method('logEvent')->willReturnCallback(
			function (
				string $signingRequestId,
				string $action,
				string $actorUserId,
				string $actorDisplayName,
				string $ipAddress,
				string $signatureLevel = '',
				string $provider = '',
				array $metadata = []
			): array {
				unset($actorUserId, $actorDisplayName, $ipAddress, $signatureLevel);
				$this->audited[] = ['id' => $signingRequestId, 'action' => $action, 'provider' => $provider, 'metadata' => $metadata];
				return [];
			}
		);

		return new LibreSignCompletion(
			settings: $settings,
			providers: $providers,
			producer: $producer,
			audit: $audit,
			emitter: new SigningConclusionEmitter(
				eventDispatcher: $this->createMock(IEventDispatcher::class),
				logger: new NullLogger(),
				eventFactory: new SigningConcludedEventFactory()
			),
			logger: new NullLogger()
		);

	}//end completion()

	/**
	 * An open LibreSign request.
	 *
	 * @param string $status   The request status
	 * @param string $provider The provider
	 *
	 * @return array<string, mixed>
	 */
	private function request(string $status = 'PENDING', string $provider = 'libresign'): array {
		return [
			'id' => 'req-' . $provider,
			'status' => $status,
			'provider' => $provider,
			'externalId' => 'ls-1',
			'documentFileId' => 42,
			'initiatorUserId' => 'alice',
			'signatureLevel' => 'AdES',
		];

	}//end request()

	/**
	 * Completed LibreSign signature is stored and audited.
	 *
	 * @return void
	 */
	public function testACompletedLibreSignSignatureIsStoredAndAudited(): void {
		$concluded = $this->completion(3, [$this->request(), $this->request(provider: 'native')])->syncAll();

		$this->assertSame(1, $concluded);
		$this->assertSame('%PDF-1.7 signed by LibreSign', $this->document);
		$this->assertSame(['IN_PROGRESS', 'COMPLETED'], array_column($this->saved, 'status'));
		$this->assertStringStartsWith('42:signed:', $this->saved[1]['signedDocumentRef']);
		$this->assertSame('req-libresign', $this->saved[1]['id']);
		$this->assertSame('COMPLETED', $this->audited[0]['action']);
		$this->assertSame('ls-1', $this->audited[0]['metadata']['externalId']);

	}//end testACompletedLibreSignSignatureIsStoredAndAudited()

	/**
	 * A request LibreSign has not finished is left alone.
	 *
	 * @return void
	 */
	public function testAnUnfinishedRequestIsLeftAlone(): void {
		$this->assertSame(0, $this->completion(2, [$this->request(status: 'IN_PROGRESS')])->syncAll());

		$this->assertSame([], $this->saved);
		$this->assertSame([], $this->audited);
		$this->assertSame('%PDF-original', $this->document);

	}//end testAnUnfinishedRequestIsLeftAlone()

	/**
	 * A request withdrawn in LibreSign is cancelled here too.
	 *
	 * @return void
	 */
	public function testARequestCancelledInLibreSignIsCancelled(): void {
		$this->assertSame(1, $this->completion(6, [$this->request()])->syncAll());

		$this->assertSame(['CANCELLED'], array_column($this->saved, 'status'));
		$this->assertSame('CANCELLED', $this->audited[0]['action']);
		$this->assertSame('%PDF-original', $this->document);

	}//end testARequestCancelledInLibreSignIsCancelled()
}//end class
