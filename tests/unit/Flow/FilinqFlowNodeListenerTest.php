<?php

/**
 * Unit tests for FilinqFlowNodeListener
 *
 * Dispatches the REAL RegisterFlowNodesEvent shape (from the shared
 * OpenRegister flow stub, the same file the analysers read) over a real
 * FlowNodeRegistry, and asserts the node lands in the catalogue under its id.
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
 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-filinq-registers-its-node-and-still-boots-without-openregister
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Flow;

use OCA\Filinq\Flow\FilinqFlowNodeListener;
use OCA\Filinq\Flow\GenerateDocumentNode;
use OCA\Filinq\Service\DocumentGenerationRequestService;
use OCA\OpenRegister\Service\Flow\FlowNodeRegistry;
use OCA\OpenRegister\Service\Flow\RegisterFlowNodesEvent;
use OCP\EventDispatcher\Event;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests for FilinqFlowNodeListener.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class FilinqFlowNodeListenerTest extends TestCase {

	/**
	 * The node lands in the catalogue under filinq.generate-document.
	 *
	 * @return void
	 */
	public function testRegistersTheGenerateDocumentNode(): void {
		$node = new GenerateDocumentNode(
			generator: $this->createMock(DocumentGenerationRequestService::class),
			urls: $this->createMock(IURLGenerator::class),
			l10n: $this->createMock(IL10N::class)
		);

		$container = $this->createMock(ContainerInterface::class);
		$container->expects($this->once())
			->method('get')
			->with(GenerateDocumentNode::class)
			->willReturn($node);

		$registry = new FlowNodeRegistry();
		$listener = new FilinqFlowNodeListener(container: $container, logger: $this->createMock(LoggerInterface::class));
		$listener->handle(new RegisterFlowNodesEvent(registry: $registry));

		$this->assertSame(['filinq.generate-document'], array_keys($registry->all()));
		$this->assertSame($node, $registry->all()['filinq.generate-document']);
	}//end testRegistersTheGenerateDocumentNode()

	/**
	 * A node that cannot be built is logged and skipped, not fatal.
	 *
	 * @return void
	 */
	public function testANodeThatCannotBeBuiltIsSkippedAndLogged(): void {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willThrowException(new RuntimeException('no twig'));

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())
			->method('warning')
			->with($this->stringContains('GenerateDocumentNode could not be registered: no twig'));

		$registry = new FlowNodeRegistry();
		(new FilinqFlowNodeListener(container: $container, logger: $logger))
			->handle(new RegisterFlowNodesEvent(registry: $registry));

		$this->assertSame([], $registry->all());
	}//end testANodeThatCannotBeBuiltIsSkippedAndLogged()

	/**
	 * Any other event is ignored without touching the container.
	 *
	 * @return void
	 */
	public function testOtherEventsAreIgnored(): void {
		$container = $this->createMock(ContainerInterface::class);
		$container->expects($this->never())->method('get');

		(new FilinqFlowNodeListener(container: $container, logger: $this->createMock(LoggerInterface::class)))
			->handle(new Event());

		$this->assertSame([GenerateDocumentNode::class], FilinqFlowNodeListener::nodeClasses());
	}//end testOtherEventsAreIgnored()
}//end class
