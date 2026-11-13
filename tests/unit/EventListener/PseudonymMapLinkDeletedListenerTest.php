<?php

/**
 * Unit tests for PseudonymMapLinkDeletedListener.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\PseudonymMapLinkDeletedListener;
use OCA\Filinq\Kernel\Contracts\PseudonymMapServiceInterface;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Deleting an anonymisation link deletes its pseudonym map key.
 *
 * @covers \OCA\Filinq\EventListener\PseudonymMapLinkDeletedListener
 */
class PseudonymMapLinkDeletedListenerTest extends TestCase {

	/**
	 * The map service double.
	 *
	 * @var PseudonymMapServiceInterface&MockObject
	 */
	private PseudonymMapServiceInterface&MockObject $maps;

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
		$this->maps = $this->createMock(PseudonymMapServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);
	}//end setUp()

	/**
	 * Build a deleted object.
	 *
	 * @param string|null          $uuid The uuid.
	 * @param array<string, mixed> $data The payload.
	 *
	 * @return ObjectEntity
	 */
	private function object(?string $uuid, array $data): ObjectEntity {
		$object = $this->createMock(ObjectEntity::class);
		$object->method('getUuid')->willReturn($uuid);
		$object->method('getObject')->willReturn($data);
		return $object;
	}//end object()

	/**
	 * Handle one event.
	 *
	 * @param Event $event The event.
	 *
	 * @return void
	 */
	private function handle(Event $event): void {
		(new PseudonymMapLinkDeletedListener(maps: $this->maps, logger: $this->logger))->handle(event: $event);
	}//end handle()

	/**
	 * A deleted anonymisation link deletes the map for that link.
	 *
	 * @return void
	 */
	public function testDeletesTheMapOfADeletedLink(): void {
		$this->maps->expects($this->once())->method('deleteForLink')->with('link-1');
		$this->logger->expects($this->never())->method('error');

		$this->handle(event: new ObjectDeletedEvent(object: $this->object(uuid: 'link-1', data: ['sourceFileId' => 1, 'anonymizedFileId' => 2])));
	}//end testDeletesTheMapOfADeletedLink()

	/**
	 * Other events, missing objects, objects without a uuid and non-link objects are ignored.
	 *
	 * @return void
	 */
	public function testIgnoresWhatIsNotADeletedLink(): void {
		$this->maps->expects($this->never())->method('deleteForLink');

		$this->handle(event: new Event());
		$this->handle(event: new ObjectDeletedEvent(object: null));
		$this->handle(event: new ObjectDeletedEvent(object: $this->object(uuid: null, data: ['sourceFileId' => 1, 'anonymizedFileId' => 2])));
		$this->handle(event: new ObjectDeletedEvent(object: $this->object(uuid: 'x', data: ['sourceFileId' => 1])));
		$this->handle(event: new ObjectDeletedEvent(object: $this->object(uuid: 'y', data: ['sourceFileId' => null, 'anonymizedFileId' => 2])));
	}//end testIgnoresWhatIsNotADeletedLink()

	/**
	 * A failed delete is logged as an error naming the link, and does not escape.
	 *
	 * @return void
	 */
	public function testFailedDeleteIsLogged(): void {
		$this->maps->method('deleteForLink')->willThrowException(new RuntimeException('locked'));
		$this->logger->expects($this->once())->method('error')->with(
			'[PseudonymMapLinkDeletedListener] the key of a deleted anonymisation link was NOT deleted',
			['linkId' => 'link-2', 'error' => 'locked']
		);

		$this->handle(event: new ObjectDeletedEvent(object: $this->object(uuid: 'link-2', data: ['sourceFileId' => 1, 'anonymizedFileId' => 2])));
	}//end testFailedDeleteIsLogged()
}//end class
