<?php

/**
 * Wiring test for the document-generation contract, asserted from the caller.
 *
 * A listener with a full test suite and no registration is a feature that
 * never runs. So this does not ask DocumentGenerationRegistrar what it does;
 * it runs RegistrationBootstrap::register(), which is what
 * Application::register() calls, and reads what actually reached the
 * registration context.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\AppInfo
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

namespace OCA\Filinq\Tests\Unit\AppInfo;

use OCA\Filinq\AppInfo\RegistrationBootstrap;
use OCA\Filinq\Event\DocumentGenerationRequestedEvent;
use OCA\Filinq\Event\DocumentStampRequestedEvent;
use OCA\Filinq\EventListener\DocumentGenerationRequestedListener;
use OCA\Filinq\EventListener\DocumentStampRequestedListener;
use OCA\Filinq\Flow\FilinqFlowNodeListener;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use PHPUnit\Framework\TestCase;

/**
 * Asserts the generation listeners are registered by the real bootstrap.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class DocumentGenerationWiringTest extends TestCase {

	/**
	 * Run the app's register() and return every (event, listener) pair.
	 *
	 * @return array<int, array{0: string, 1: string}>
	 */
	private function registeredListeners(): array {
		// A recording context rather than a PHPUnit mock: the unit suite's
		// IRegistrationContext stub declares only part of the real interface,
		// and the bootstrap also calls members it leaves out (dashboard
		// widgets, middleware, ...). Those land in __call and are ignored;
		// the one member this test reads is declared and recorded.
		$context = new class implements IRegistrationContext {
			/**
			 * The (event, listener) pairs registered.
			 *
			 * @var array<int, array{0: string, 1: string}>
			 */
			public array $pairs = [];

			public function registerService(string $name, callable $factory, bool $shared = true): void {
			}

			public function registerAlias(string $alias, string $target): void {
			}

			public function registerServiceAlias(string $alias, string $target): void {
			}

			public function registerParameter(string $name, mixed $value): void {
			}

			public function registerEventListener(string $event, string $listener, int $priority = 0): void {
				unset($priority);
				$this->pairs[] = [$event, $listener];
			}

			/**
			 * Every other registration member, ignored.
			 *
			 * @param string            $name      The member.
			 * @param array<int, mixed> $arguments Its arguments.
			 *
			 * @return void
			 */
			public function __call(string $name, array $arguments): void {
				unset($name, $arguments);
			}
		};

		(new RegistrationBootstrap())->register(context: $context);

		return $context->pairs;
	}//end registeredListeners()

	/**
	 * The flow node listener is registered for OpenRegister's event, by name.
	 *
	 * @return void
	 */
	public function testTheFlowNodeListenerIsRegisteredByTheBootstrap(): void {
		$this->assertContains(
			['OCA\\OpenRegister\\Service\\Flow\\RegisterFlowNodesEvent', FilinqFlowNodeListener::class],
			$this->registeredListeners()
		);
	}//end testTheFlowNodeListenerIsRegisteredByTheBootstrap()

	/**
	 * The command listener is registered for the command event.
	 *
	 * @return void
	 */
	public function testTheCommandListenerIsRegisteredByTheBootstrap(): void {
		$this->assertContains(
			[DocumentGenerationRequestedEvent::class, DocumentGenerationRequestedListener::class],
			$this->registeredListeners()
		);
	}//end testTheCommandListenerIsRegisteredByTheBootstrap()

	/**
	 * The stamp listener is registered for the stamp command.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
	 */
	public function testTheStampListenerIsRegisteredByTheBootstrap(): void {
		$this->assertContains(
			[DocumentStampRequestedEvent::class, DocumentStampRequestedListener::class],
			$this->registeredListeners()
		);
	}//end testTheStampListenerIsRegisteredByTheBootstrap()
}//end class
