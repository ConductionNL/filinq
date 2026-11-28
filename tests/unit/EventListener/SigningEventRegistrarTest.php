<?php

/**
 * Unit tests for SigningEventRegistrar.
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

use OCA\Filinq\Event\DocumentSigningRequestedEvent;
use OCA\Filinq\EventListener\DocumentSigningRequestedListener;
use OCA\Filinq\EventListener\SigningEventRegistrar;
use OCA\Filinq\EventListener\SigningTaskListener;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use PHPUnit\Framework\TestCase;

/**
 * Which listeners the signing registrar wires.
 *
 * @covers \OCA\Filinq\EventListener\SigningEventRegistrar
 */
class SigningEventRegistrarTest extends TestCase {

	/**
	 * Each OpenRegister task event reaches the signing task listener, then the
	 * cross-app signing request reaches its own listener.
	 *
	 * @return void
	 */
	public function testRegisterWiresEveryTaskEventThenTheSigningRequest(): void {
		$registered = [];
		$context = $this->createMock(IRegistrationContext::class);
		$context->expects(self::exactly(4))->method('registerEventListener')->willReturnCallback(
			static function (string $event, string $listener) use (&$registered): void {
				$registered[] = [$event, $listener];
			}
		);

		(new SigningEventRegistrar())->register($context);

		self::assertSame(
			[
				['OCA\OpenRegister\Event\TaskTransitionedEvent', SigningTaskListener::class],
				['OCA\OpenRegister\Event\TaskTerminalEvent', SigningTaskListener::class],
				['OCA\OpenRegister\Event\TaskSequenceCompletedEvent', SigningTaskListener::class],
				[DocumentSigningRequestedEvent::class, DocumentSigningRequestedListener::class],
			],
			$registered
		);
	}//end testRegisterWiresEveryTaskEventThenTheSigningRequest()
}//end class
