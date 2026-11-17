<?php

/**
 * The `filinq-templates` leaf is registered as a data provider with its
 * app-local provider (template-library-leaf REQ-TMPL-14).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/template-library-leaf/tasks.md#task-3.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\RegisterTemplatesLeafListener;
use OCA\Filinq\Integration\TemplatesLeafProvider;
use OCA\OpenRegister\Event\RegisterLeafProvidersEvent;
use OCA\OpenRegister\Service\Integration\LeafDescriptor;
use OCP\EventDispatcher\Event;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests RegisterTemplatesLeafListener.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\EventListener\RegisterTemplatesLeafListener
 */
class RegisterTemplatesLeafListenerTest extends TestCase {

	/**
	 * A translator that returns the source.
	 *
	 * @return IL10N The translator.
	 */
	private function l10n(): IL10N {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return $l10n;

	}//end l10n()

	/**
	 * One data-provider leaf under the provider's id, carried with the provider itself.
	 *
	 * @return void
	 */
	public function testTheLeafIsADataProviderWithItsProvider(): void {
		$provider = $this->createMock(TemplatesLeafProvider::class);
		$event = new RegisterLeafProvidersEvent();

		(new RegisterTemplatesLeafListener($provider, $this->l10n(), $this->createMock(LoggerInterface::class)))->handle($event);

		$leaves = $event->getLeaves();
		$this->assertCount(1, $leaves);
		$descriptor = $leaves[0]['descriptor'];
		$this->assertSame('filinq-templates', $descriptor->getId());
		$this->assertSame('Templates', $descriptor->getLabel());
		$this->assertSame([LeafDescriptor::KIND_DATA_PROVIDER], $descriptor->getKinds());
		$this->assertSame('filinq', $descriptor->getRequiredApp());
		$this->assertSame($provider, $leaves[0]['provider']);

	}//end testTheLeafIsADataProviderWithItsProvider()

	/**
	 * A registry that refuses the leaf is logged, never thrown into the dispatch.
	 *
	 * @return void
	 */
	public function testARefusedRegistrationIsLogged(): void {
		$event = $this->createMock(RegisterLeafProvidersEvent::class);
		$event->method('registerLeaf')->willThrowException(new RuntimeException('duplicate id'));
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning')->with('Filinq could not register the filinq-templates leaf: duplicate id');

		(new RegisterTemplatesLeafListener($this->createMock(TemplatesLeafProvider::class), $this->l10n(), $logger))->handle($event);

	}//end testARefusedRegistrationIsLogged()

	/**
	 * Any other event is ignored.
	 *
	 * @return void
	 */
	public function testOtherEventsAreIgnored(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->never())->method('warning');

		(new RegisterTemplatesLeafListener($this->createMock(TemplatesLeafProvider::class), $this->l10n(), $logger))->handle(new Event());

	}//end testOtherEventsAreIgnored()
}//end class
