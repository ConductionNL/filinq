<?php

/**
 * Unit tests for the verification QR code SignedArtifactProducer puts on a document before signing.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\FinalDocumentService;
use OCA\Filinq\Service\SignatureVerificationLinkService;
use OCA\Filinq\Service\SignedArtifactProducer;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use OCA\Filinq\Service\Signing\SigningProviderInterface;
use OCA\Filinq\Service\VerificationQrStamp;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A PDF gets the QR code to its verification page before it is signed, so
 * the code is part of the signed bytes; nothing else changes the signing.
 *
 * @covers \OCA\Filinq\Service\SignedArtifactProducer
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class SignedArtifactVerificationQrTest extends TestCase {

	/**
	 * The token used throughout.
	 *
	 * @var string
	 */
	private const TOKEN = '0123456789abcdef0123456789abcdef';

	/**
	 * The verification link the URL generator answers.
	 *
	 * @var string
	 */
	private const URL = 'https://cloud.example.nl/apps/filinq/verify/0123456789abcdef0123456789abcdef';

	/**
	 * The document being signed.
	 *
	 * @var File&MockObject
	 */
	private File&MockObject $file;

	/**
	 * The signing provider.
	 *
	 * @var SigningProviderInterface&MockObject
	 */
	private SigningProviderInterface&MockObject $provider;

	/**
	 * The provider factory.
	 *
	 * @var SigningProviderFactory&MockObject
	 */
	private SigningProviderFactory&MockObject $factory;

	/**
	 * The QR stamp.
	 *
	 * @var VerificationQrStamp&MockObject
	 */
	private VerificationQrStamp&MockObject $stamp;

	/**
	 * The record service.
	 *
	 * @var SignatureVerificationLinkService&MockObject
	 */
	private SignatureVerificationLinkService&MockObject $links;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * The names of the providers the factory was asked for.
	 *
	 * @var array<int, string>
	 */
	private array $asked = [];

	/**
	 * Set up: an initiator holding file 11 with PDF bytes.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->file = $this->createMock(File::class);
		$this->file->method('getContent')->willReturn('%PDF-1.7 original');
		$this->file->method('getName')->willReturn('besluit.pdf');

		$this->provider = $this->createMock(SigningProviderInterface::class);
		$this->factory = $this->createMock(SigningProviderFactory::class);
		$this->factory->method('getProvider')->willReturnCallback(
			function (string $identifier): SigningProviderInterface {
				$this->asked[] = $identifier;
				return $this->provider;
			}
		);

		$this->stamp = $this->createMock(VerificationQrStamp::class);
		$this->links = $this->createMock(SignatureVerificationLinkService::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The producer with the QR stamp wired.
	 *
	 * @return SignedArtifactProducer
	 */
	private function producer(): SignedArtifactProducer {
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->with(11)->willReturn([$this->file]);
		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->with('griffier')->willReturn($folder);

		$urls = $this->createMock(IURLGenerator::class);
		$urls->method('linkToRouteAbsolute')
			->with('filinq.publicVerification.page', ['token' => self::TOKEN])
			->willReturn(self::URL);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text): string => 'NL: ' . $text);

		return new SignedArtifactProducer(
			providerFactory: $this->factory,
			userSession: $this->createMock(IUserSession::class),
			request: $this->createMock(IRequest::class),
			rootFolder: $rootFolder,
			finalDocuments: $this->createMock(FinalDocumentService::class),
			verificationLinks: $this->links,
			logger: $this->logger,
			qrStamp: $this->stamp,
			urlGenerator: $urls,
			l10n: $l10n,
		);
	}//end producer()

	/**
	 * The signing request.
	 *
	 * @param string $provider The provider it names.
	 *
	 * @return array<string, mixed>
	 */
	private function request(string $provider = 'native'): array {
		return ['documentFileId' => 11, 'initiatorUserId' => 'griffier', 'provider' => $provider, 'signatureLevel' => 'SES'];
	}//end request()

	/**
	 * The provider signs the stamped bytes, and the signed bytes are stored.
	 *
	 * @return void
	 */
	public function testAPdfIsStampedBeforeItIsSigned(): void {
		$this->stamp->method('canStamp')->with('%PDF-1.7 original')->willReturn(true);
		$this->stamp->expects($this->once())->method('stamp')
			->with('%PDF-1.7 original', self::URL, 'NL: Verify this document')
			->willReturn('%PDF-1.7 stamped');
		$this->provider->expects($this->once())->method('produceSignedArtifact')
			->with('%PDF-1.7 stamped', $this->anything())
			->willReturn('%PDF-1.7 signed');
		$this->file->expects($this->once())->method('putContent')->with('%PDF-1.7 signed');

		$reference = $this->producer()->produce(request: $this->request(), verificationToken: self::TOKEN);

		$this->assertSame('11:signed:' . substr(hash('sha256', '%PDF-1.7 signed'), 0, 16), $reference);
		$this->assertSame(['native'], $this->asked);

	}//end testAPdfIsStampedBeforeItIsSigned()

	/**
	 * Without a token nothing is stamped.
	 *
	 * @return void
	 */
	public function testNoTokenNoStamp(): void {
		$this->stamp->expects($this->never())->method('stamp');
		$this->provider->expects($this->once())->method('produceSignedArtifact')
			->with('%PDF-1.7 original', $this->anything())
			->willReturn('%PDF-1.7 signed');

		$this->producer()->produce(request: $this->request());

	}//end testNoTokenNoStamp()

	/**
	 * LibreSign hands back the file it signed itself, so there is nothing to stamp.
	 *
	 * @return void
	 */
	public function testLibreSignIsNotStamped(): void {
		$this->stamp->expects($this->never())->method('canStamp');
		$this->stamp->expects($this->never())->method('stamp');
		$this->provider->expects($this->once())->method('produceSignedArtifact')
			->with('%PDF-1.7 original', $this->anything())
			->willReturn('%PDF-1.7 signed by libresign');

		$this->producer()->produce(request: $this->request(provider: 'libresign'), verificationToken: self::TOKEN);

	}//end testLibreSignIsNotStamped()

	/**
	 * A document that is not a stampable PDF is signed as it is.
	 *
	 * @return void
	 */
	public function testANonPdfIsSignedAsItIs(): void {
		$this->stamp->method('canStamp')->willReturn(false);
		$this->stamp->expects($this->never())->method('stamp');
		$this->provider->expects($this->once())->method('produceSignedArtifact')
			->with('%PDF-1.7 original', $this->anything())
			->willReturn('signed');

		$this->producer()->produce(request: $this->request(), verificationToken: self::TOKEN);

	}//end testANonPdfIsSignedAsItIs()

	/**
	 * A stamp that fails is logged and the original is signed: the QR code
	 * never fails a signature.
	 *
	 * @return void
	 */
	public function testAFailedStampSignsTheOriginal(): void {
		$this->stamp->method('canStamp')->willReturn(true);
		$this->stamp->method('stamp')->willThrowException(new RuntimeException('mPDF gave up'));
		$this->logger->expects($this->once())->method('warning')->with(
			'SignedArtifactProducer: the verification QR code could not be stamped; signing without it',
			['fileId' => 11, 'error' => 'mPDF gave up']
		);
		$this->provider->expects($this->once())->method('produceSignedArtifact')
			->with('%PDF-1.7 original', $this->anything())
			->willReturn('%PDF-1.7 signed');

		$this->producer()->produce(request: $this->request(), verificationToken: self::TOKEN);

	}//end testAFailedStampSignsTheOriginal()

	/**
	 * The token is taken from the record service, and the record is minted under it.
	 *
	 * @return void
	 */
	public function testTheRecordIsMintedUnderTheStampedToken(): void {
		$this->links->expects($this->once())->method('newToken')->willReturn(self::TOKEN);
		$this->links->expects($this->once())->method('mint')
			->with('%PDF-1.7 original', '11:signed:abcd', 'besluit.pdf', null, self::TOKEN)
			->willReturn(['token' => self::TOKEN]);

		$producer = $this->producer();
		$token = $producer->newVerificationToken();

		$this->assertSame(self::TOKEN, $token);
		$this->assertSame(
			self::TOKEN,
			$producer->recordVerification(request: $this->request(), signedDocumentRef: '11:signed:abcd', token: $token)
		);

	}//end testTheRecordIsMintedUnderTheStampedToken()

	/**
	 * Without the record service there is no token, so nothing is stamped.
	 *
	 * @return void
	 */
	public function testNoRecordServiceNoToken(): void {
		$producer = new SignedArtifactProducer(
			providerFactory: $this->factory,
			userSession: $this->createMock(IUserSession::class),
			request: $this->createMock(IRequest::class),
			rootFolder: $this->createMock(IRootFolder::class),
			finalDocuments: $this->createMock(FinalDocumentService::class),
		);

		$this->assertNull($producer->newVerificationToken());

	}//end testNoRecordServiceNoToken()
}//end class
