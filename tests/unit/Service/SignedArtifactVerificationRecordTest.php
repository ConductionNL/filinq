<?php

/**
 * Unit tests for SignedArtifactProducer::recordVerification().
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
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Signing completion mints a verification record over the stored signed bytes.
 *
 * @covers \OCA\Filinq\Service\SignedArtifactProducer
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class SignedArtifactVerificationRecordTest extends TestCase {

	/**
	 * The root folder.
	 *
	 * @var IRootFolder&MockObject
	 */
	private IRootFolder&MockObject $rootFolder;

	/**
	 * Mints the record.
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
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->links = $this->createMock(SignatureVerificationLinkService::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The producer, with or without the link service.
	 *
	 * @param bool $withLinks Whether the link service is wired.
	 *
	 * @return SignedArtifactProducer
	 */
	private function producer(bool $withLinks = true): SignedArtifactProducer {
		return new SignedArtifactProducer(
			providerFactory: $this->createMock(SigningProviderFactory::class),
			userSession: $this->createMock(IUserSession::class),
			request: $this->createMock(IRequest::class),
			rootFolder: $this->rootFolder,
			finalDocuments: $this->createMock(FinalDocumentService::class),
			verificationLinks: ($withLinks === true ? $this->links : null),
			logger: $this->logger,
		);
	}//end producer()

	/**
	 * Make the initiator's folder hold one file with these bytes.
	 *
	 * @param string $bytes The file content.
	 *
	 * @return void
	 */
	private function initiatorHolds(string $bytes): void {
		$file = $this->createMock(File::class);
		$file->method('getContent')->willReturn($bytes);
		$file->method('getName')->willReturn('besluit.pdf');
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->with(11)->willReturn([$file]);
		$this->rootFolder->method('getUserFolder')->with('griffier')->willReturn($folder);
	}//end initiatorHolds()

	/**
	 * The record is minted over the stored signed bytes as the initiator reads them, and its token returned.
	 *
	 * @return void
	 */
	public function testMintsOverTheStoredBytes(): void {
		$this->initiatorHolds(bytes: 'signed bytes');
		$this->links->expects($this->once())->method('mint')
			->with('signed bytes', '11:signed:abcd', 'besluit.pdf')
			->willReturn(['token' => str_repeat('ab', 16)]);

		$token = $this->producer()->recordVerification(
			request: ['documentFileId' => 11, 'initiatorUserId' => 'griffier'],
			signedDocumentRef: '11:signed:abcd'
		);

		$this->assertSame(str_repeat('ab', 16), $token);

	}//end testMintsOverTheStoredBytes()

	/**
	 * A failed mint is logged and leaves no token; it does not undo the signing.
	 *
	 * @return void
	 */
	public function testAFailedMintIsLoggedNotThrown(): void {
		$this->initiatorHolds(bytes: 'signed bytes');
		$this->links->method('mint')->willThrowException(new \RuntimeException('store down'));
		$this->logger->expects($this->once())->method('warning')->with(
			$this->stringContains('could not be minted'),
			$this->callback(static fn (array $context): bool => $context['error'] === 'store down' && $context['signedDocumentRef'] === '11:signed:abcd')
		);

		$this->assertNull($this->producer()->recordVerification(
			request: ['documentFileId' => 11, 'initiatorUserId' => 'griffier'],
			signedDocumentRef: '11:signed:abcd'
		));

	}//end testAFailedMintIsLoggedNotThrown()

	/**
	 * A document the initiator cannot read mints nothing.
	 *
	 * @return void
	 */
	public function testAnUnreadableDocumentMintsNothing(): void {
		$this->links->expects($this->never())->method('mint');
		$this->logger->expects($this->once())->method('warning');

		$this->assertNull($this->producer()->recordVerification(request: ['documentFileId' => 0], signedDocumentRef: 'x'));

	}//end testAnUnreadableDocumentMintsNothing()

	/**
	 * Without the link service nothing is read and no token comes back.
	 *
	 * @return void
	 */
	public function testWithoutTheLinkServiceNothingHappens(): void {
		$this->rootFolder->expects($this->never())->method('getUserFolder');

		$this->assertNull($this->producer(withLinks: false)->recordVerification(
			request: ['documentFileId' => 11, 'initiatorUserId' => 'griffier'],
			signedDocumentRef: '11:signed:abcd'
		));

	}//end testWithoutTheLinkServiceNothingHappens()
}//end class
