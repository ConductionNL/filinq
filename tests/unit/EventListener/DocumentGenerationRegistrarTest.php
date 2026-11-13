<?php

/**
 * Unit tests for DocumentGenerationRegistrar.
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

use OCA\Filinq\Event\DocumentGenerationRequestedEvent;
use OCA\Filinq\EventListener\DocumentGenerationRegistrar;
use OCA\Filinq\EventListener\DocumentGenerationRequestedListener;
use OCA\Filinq\Flow\FilinqFlowNodeListener;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use PHPUnit\Framework\TestCase;

/**
 * Which listeners the document-generation registrar wires.
 *
 * @covers \OCA\Filinq\EventListener\DocumentGenerationRegistrar
 */
class DocumentGenerationRegistrarTest extends TestCase {

	/**
	 * The flow-node listener is keyed by OpenRegister's event name, then the generation command listener.
	 *
	 * @return void
	 */
	public function testRegisterWiresTheFlowNodeAndGenerationListenersInOrder(): void {
		$registered = [];
		$context = $this->createMock(IRegistrationContext::class);
		$context->expects(self::exactly(2))->method('registerEventListener')->willReturnCallback(
			static function (string $event, string $listener) use (&$registered): void {
				$registered[] = [$event, $listener];
			}
		);

		(new DocumentGenerationRegistrar())->register($context);

		self::assertSame(
			[
				['OCA\OpenRegister\Service\Flow\RegisterFlowNodesEvent', FilinqFlowNodeListener::class],
				[DocumentGenerationRequestedEvent::class, DocumentGenerationRequestedListener::class],
			],
			$registered
		);
	}//end testRegisterWiresTheFlowNodeAndGenerationListenersInOrder()

	/**
	 * The flow-nodes event name is a literal that never needs OpenRegister on the autoloader.
	 *
	 * @return void
	 */
	public function testTheFlowNodesEventNameIsOpenRegistersEvent(): void {
		self::assertSame('OCA\OpenRegister\Service\Flow\RegisterFlowNodesEvent', DocumentGenerationRegistrar::FLOW_NODES_EVENT);
	}//end testTheFlowNodesEventNameIsOpenRegistersEvent()
}//end class
