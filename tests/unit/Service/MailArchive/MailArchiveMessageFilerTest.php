<?php

/**
 * Unit tests for MailArchiveMessageFiler
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\MailArchive
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\MailArchive;

use OCA\Filinq\Kernel\Contracts\EmailDocumentRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\IntakeRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\IntakeServiceInterface;
use OCA\Filinq\Service\EmailIngestion\EmailFiling;
use OCA\Filinq\Service\EmailIngestion\EmailMessageReader;
use OCA\Filinq\Service\EmailIngestion\EmailThreadHeaders;
use OCA\Filinq\Service\MailArchive\MailArchiveInboxDelivery;
use OCA\Filinq\Service\MailArchive\MailArchiveMessageFiler;
use OCA\Filinq\Service\MailArchive\MailArchiveWorkspace;
use OCA\Filinq\Service\Support\EmailHandling;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * What the filer does when storing a message breaks.
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-2
 */
class MailArchiveMessageFilerTest extends TestCase {

	/**
	 * A move into the dossier that breaks is a filing-failed failure, and the written file is removed.
	 *
	 * @return void
	 */
	public function testABrokenMoveIsAFailureAndLeavesNoFile(): void {
		$staged = $this->createMock(File::class);
		$staged->expects($this->once())->method('delete');
		$workspace = $this->createMock(MailArchiveWorkspace::class);
		$workspace->method('stage')->willReturn($staged);
		$reader = $this->createMock(EmailMessageReader::class);
		$reader->method('read')->willReturn(['subject' => 'Brug']);
		$filing = $this->createMock(EmailFiling::class);
		$filing->method('file')->willThrowException(new RuntimeException('storage gone'));
		$emails = $this->createMock(EmailDocumentRepositoryInterface::class);
		$emails->expects($this->never())->method('save');
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning')->with($this->anything(), ['name' => '1.eml', 'exception' => 'storage gone']);

		$filer = new MailArchiveMessageFiler(
			headers: new EmailThreadHeaders(),
			handling: new EmailHandling(reader: $reader, filing: $filing),
			emailDocuments: $emails,
			inbox: new MailArchiveInboxDelivery(intake: $this->createMock(IntakeRepositoryInterface::class), intakeService: $this->createMock(IntakeServiceInterface::class)),
			workspace: $workspace,
			clock: $this->createMock(ITimeFactory::class),
			logger: $logger
		);

		$result = $filer->file(
			archive: $this->createMock(File::class),
			message: ['folder' => 'Brug', 'name' => '1.eml', 'raw' => "Message-ID: <brug@demostad.nl>\r\nSubject: Brug\r\n\r\nx"],
			dossier: 'dossier-1'
		);

		$this->assertSame(
			['outcome' => 'failed', 'failure' => ['messageId' => 'brug@demostad.nl', 'archiveFolder' => 'Brug', 'name' => '1.eml', 'reason' => 'filing-failed']],
			$result
		);

	}//end testABrokenMoveIsAFailureAndLeavesNoFile()
}//end class
