<?php

/**
 * The signed entry the producer writes into the document's audit trail.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\Audit\DocumentActionAudit;
use OCA\Filinq\Service\FinalDocumentService;
use OCA\Filinq\Service\SignedArtifactProducer;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use OCA\Filinq\Service\Signing\SigningProviderInterface;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Signing writes `signed` into the document's trail once the signed bytes
 * are stored, and not when they could not be.
 *
 * @covers \OCA\Filinq\Service\SignedArtifactProducer
 *
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.3
 */
class SignedArtifactProducerAuditTest extends TestCase {

	/**
	 * The document being signed.
	 *
	 * @var File&MockObject
	 */
	private File $file;

	/**
	 * The audit trail writer.
	 *
	 * @var DocumentActionAudit&MockObject
	 */
	private DocumentActionAudit $audit;

	/**
	 * Build the document, the provider and the trail writer.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->file = $this->createMock(File::class);
		$this->file->method('getContent')->willReturn('%PDF-1.7 original');
		$this->file->method('getName')->willReturn('besluit.pdf');

		$this->audit = $this->createMock(DocumentActionAudit::class);

	}//end setUp()

	/**
	 * The producer, wired to the trail writer.
	 *
	 * @return SignedArtifactProducer
	 */
	private function producer(): SignedArtifactProducer {
		$provider = $this->createMock(SigningProviderInterface::class);
		$provider->method('produceSignedArtifact')->willReturn('%PDF-1.7 signed');
		$factory = $this->createMock(SigningProviderFactory::class);
		$factory->method('getProvider')->with('native')->willReturn($provider);

		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->with(11)->willReturn([$this->file]);
		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->with('griffier')->willReturn($folder);

		return new SignedArtifactProducer(
			providerFactory: $factory,
			userSession: $this->createMock(IUserSession::class),
			request: $this->createMock(IRequest::class),
			rootFolder: $rootFolder,
			finalDocuments: $this->createMock(FinalDocumentService::class),
			documentAudit: $this->audit,
		);

	}//end producer()

	/**
	 * A stored signature writes one signed entry on the document, naming the provider.
	 *
	 * @return void
	 */
	public function testAStoredSignatureWritesOneSignedEntry(): void {
		$this->file->expects($this->once())->method('putContent')->with('%PDF-1.7 signed');
		$this->audit->expects($this->once())->method('record')
			->with(11, 'signed', ['provider' => 'native'])
			->willReturn(true);

		$reference = $this->producer()->produce(
			request: ['documentFileId' => 11, 'initiatorUserId' => 'griffier', 'provider' => 'native', 'signatureLevel' => 'SES']
		);

		$this->assertStringStartsWith('11:signed:', $reference);

	}//end testAStoredSignatureWritesOneSignedEntry()

	/**
	 * A signature that could not be stored writes no entry.
	 *
	 * @return void
	 */
	public function testASignatureThatWasNotStoredWritesNothing(): void {
		$this->file->method('putContent')->willThrowException(new RuntimeException('disk full'));
		$this->audit->expects($this->never())->method('record');

		$this->expectException(RuntimeException::class);

		$this->producer()->produce(
			request: ['documentFileId' => 11, 'initiatorUserId' => 'griffier', 'provider' => 'native', 'signatureLevel' => 'SES']
		);

	}//end testASignatureThatWasNotStoredWritesNothing()
}//end class
