<?php

/**
 * The listener that offers the four operations to Nextcloud Flow, one
 * broken operation leaving the others registered (REQ-DDFLO-001).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Flow
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Flow;

use OCA\Filinq\Flow\FlowOperationJob;
use OCA\Filinq\Flow\Operation\AnonymizeDocumentOperation;
use OCA\Filinq\Flow\Operation\ConvertToPdfaOperation;
use OCA\Filinq\Flow\Operation\RunOcrOperation;
use OCA\Filinq\Flow\Operation\RunValidationOperation;
use OCA\Filinq\Flow\RegisterFlowOperationsListener;
use OCP\EventDispatcher\Event;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests RegisterFlowOperationsListener.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Flow\RegisterFlowOperationsListener
 */
class RegisterFlowOperationsListenerTest extends TestCase {

	/**
	 * All four operations are registered, in a fixed order.
	 *
	 * @return void
	 */
	public function testAllFourOperationsAreRegistered(): void {
		$operations = [];
		foreach (FlowOperationJob::OPERATIONS as $class) {
			$operations[$class] = $this->createMock($class);
		}

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(static fn (string $class): object => $operations[$class]);
		$registered = [];
		$event = $this->createMock(RegisterOperationsEvent::class);
		$event->expects($this->exactly(4))->method('registerOperation')->willReturnCallback(
			static function (object $operation) use (&$registered): void {
				$registered[] = $operation;
			}
		);

		(new RegisterFlowOperationsListener($container, $this->createMock(LoggerInterface::class)))->handle($event);

		$this->assertSame(
			[AnonymizeDocumentOperation::class, RunOcrOperation::class, ConvertToPdfaOperation::class, RunValidationOperation::class],
			FlowOperationJob::OPERATIONS
		);
		$this->assertSame(array_values($operations), $registered);

	}//end testAllFourOperationsAreRegistered()

	/**
	 * An operation that cannot be built is logged and left out; the rest still register.
	 *
	 * @return void
	 */
	public function testABrokenOperationIsSkippedNotFatal(): void {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			fn (string $class): object => $class === RunOcrOperation::class ? throw new RuntimeException('no OCR service') : $this->createMock($class)
		);
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning')->with(
			'Filinq Flow: an operation could not be registered',
			['operation' => RunOcrOperation::class, 'exception' => 'no OCR service']
		);
		$event = $this->createMock(RegisterOperationsEvent::class);
		$event->expects($this->exactly(3))->method('registerOperation');

		(new RegisterFlowOperationsListener($container, $logger))->handle($event);

	}//end testABrokenOperationIsSkippedNotFatal()

	/**
	 * Any other event is ignored.
	 *
	 * @return void
	 */
	public function testOtherEventsAreIgnored(): void {
		$container = $this->createMock(ContainerInterface::class);
		$container->expects($this->never())->method('get');

		(new RegisterFlowOperationsListener($container, $this->createMock(LoggerInterface::class)))->handle(new Event());

	}//end testOtherEventsAreIgnored()
}//end class
