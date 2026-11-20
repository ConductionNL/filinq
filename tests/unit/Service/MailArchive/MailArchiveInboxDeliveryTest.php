<?php

/**
 * Unit tests for MailArchiveInboxDelivery
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

use OCA\Filinq\Event\IntakeDocumentReceivedEvent;
use OCA\Filinq\Kernel\Contracts\IntakeRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\IntakeServiceInterface;
use OCA\Filinq\Service\MailArchive\MailArchiveInboxDelivery;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;

/**
 * An inbox-bound message arrives as mail under its source reference, once.
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-2
 */
class MailArchiveInboxDeliveryTest extends TestCase {

	/**
	 * A message seen before by its source reference counts as received.
	 *
	 * @return void
	 */
	public function testAKnownSourceReferenceIsReceived(): void {
		$intake = $this->createMock(IntakeRepositoryInterface::class);
		$intake->method('findBySourceRef')->willReturnMap([['m1@demostad.nl', ['uuid' => 'intake-1']], ['m2@demostad.nl', null]]);
		$delivery = new MailArchiveInboxDelivery(intake: $intake, intakeService: $this->createMock(IntakeServiceInterface::class));

		$this->assertTrue($delivery->received(sourceRef: 'm1@demostad.nl'));
		$this->assertFalse($delivery->received(sourceRef: 'm2@demostad.nl'));

	}//end testAKnownSourceReferenceIsReceived()

	/**
	 * Delivering receives the written file as mail, with subject, sender, sent time and source reference.
	 *
	 * @return void
	 */
	public function testDeliveringReceivesTheFileAsMail(): void {
		$received = [];
		$service = $this->createMock(IntakeServiceInterface::class);
		$service->expects($this->exactly(2))->method('receiveMessage')->willReturnCallback(
			function (IntakeDocumentReceivedEvent $message, array $attachments = []) use (&$received): array {
				$received[] = [$message->getChannel(), $message->getFileId(), $message->getFileName(), $message->getSubject(), $message->getSender(), $message->getSourceRef(), $message->getReceivedAt(), $attachments];

				return [];
			}
		);
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(5001);
		$file->method('getName')->willReturn('a.eml');
		$delivery = new MailArchiveInboxDelivery(intake: $this->createMock(IntakeRepositoryInterface::class), intakeService: $service);

		$delivery->deliver(message: $file, fields: ['subject' => 'Brug', 'fromAddress' => 'jansen@demostad.nl', 'sentAt' => '2024-01-01T09:00:00+00:00'], sourceRef: 'm1@demostad.nl');
		$delivery->deliver(message: $file, fields: ['sentAt' => ['not a date']], sourceRef: 'sha256:abc');

		$this->assertSame('mail', $received[0][0]);
		$this->assertSame([5001, 'a.eml', 'Brug', 'jansen@demostad.nl', 'm1@demostad.nl', '2024-01-01T09:00:00+00:00', []], array_slice($received[0], 1));
		$this->assertSame(['', '', 'sha256:abc'], array_slice($received[1], 3, 3));
		$this->assertNotSame(['not a date'], $received[1][6], 'a sent time that is not a string is not passed on');

	}//end testDeliveringReceivesTheFileAsMail()
}//end class
