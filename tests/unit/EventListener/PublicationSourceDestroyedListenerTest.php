<?php

/**
 * Unit tests for PublicationSourceDestroyedListener.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/specs/woo-publicatie-pipeline/spec.md#requirement-a-publication-goes-offline-when-its-source-document-is-destroyed-req-scr-009
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\PublicationSourceDestroyedListener;
use OCA\Filinq\Kernel\Contracts\PublicationPipelineServiceInterface;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Only a destruction takes a publication offline.
 *
 * @covers \OCA\Filinq\EventListener\PublicationSourceDestroyedListener
 */
class PublicationSourceDestroyedListenerTest extends TestCase {

	/**
	 * The pipeline double.
	 *
	 * @var PublicationPipelineServiceInterface&MockObject
	 */
	private PublicationPipelineServiceInterface&MockObject $pipeline;

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
		$this->pipeline = $this->createMock(PublicationPipelineServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The listener over a container whose destruction frame says whether it runs.
	 *
	 * @param bool|null $running Whether a destruction runs, null for no frame.
	 *
	 * @return PublicationSourceDestroyedListener
	 */
	private function listener(?bool $running): PublicationSourceDestroyedListener {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('has')->with(PublicationSourceDestroyedListener::DESTRUCTION_FRAME)->willReturn($running !== null);
		$container->method('get')->willReturn(
			new class ($running === true) {
				/**
				 * Constructor.
				 *
				 * @param bool $running Whether it runs.
				 */
				public function __construct(private readonly bool $running) {
				}

				/**
				 * Whether a destruction runs.
				 *
				 * @return bool
				 */
				public function isRunning(): bool {
					return $this->running;
				}
			}
		);

		return new PublicationSourceDestroyedListener($this->pipeline, $container, $this->logger);

	}//end listener()

	/**
	 * A deleted document registration.
	 *
	 * @return ObjectDeletedEvent
	 */
	private function deleted(): ObjectDeletedEvent {
		$object = new ObjectEntity();
		$object->setUuid('obj-1');
		$object->setObject(['document' => 42, 'title' => 'Besluit']);

		return new ObjectDeletedEvent($object);

	}//end deleted()

	/**
	 * A destroyed object withdraws by its uuid and the file it describes.
	 *
	 * @return void
	 */
	public function testADestroyedObjectWithdrawsItsPublications(): void {
		$this->pipeline->expects($this->once())->method('withdrawDestroyedSource')
			->with(['obj-1', '42'], '(object obj-1)')
			->willReturn([]);

		$this->listener(running: true)->handle($this->deleted());

	}//end testADestroyedObjectWithdrawsItsPublications()

	/**
	 * An ordinary delete, or one without OpenRegister's frame, leaves publications alone.
	 *
	 * @return void
	 */
	public function testAnOrdinaryDeleteLeavesThePublication(): void {
		$this->pipeline->expects($this->never())->method('withdrawDestroyedSource');

		$this->listener(running: false)->handle($this->deleted());
		$this->listener(running: null)->handle($this->deleted());
		$this->listener(running: true)->handle(new Event());

	}//end testAnOrdinaryDeleteLeavesThePublication()

	/**
	 * A failed withdrawal is logged, not thrown into OpenRegister's delete.
	 *
	 * @return void
	 */
	public function testAFailedWithdrawalIsLogged(): void {
		$this->pipeline->method('withdrawDestroyedSource')->willThrowException(new RuntimeException('platform down'));
		$this->logger->expects($this->once())->method('error')
			->with($this->stringContains('NOT withdrawn'), ['objectId' => 'obj-1', 'error' => 'platform down']);

		$this->listener(running: true)->handle($this->deleted());

	}//end testAFailedWithdrawalIsLogged()
}//end class
