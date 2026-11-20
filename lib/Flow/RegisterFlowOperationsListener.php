<?php

/**
 * Filinq Register Flow Operations Listener
 *
 * Offers Filinq's four file operations to Nextcloud Flow: registers them on
 * RegisterOperationsEvent and loads their rule-builder script on
 * LoadSettingsScriptsEvent. An operation that cannot be constructed is logged
 * and left out, so the others still register.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Flow;

use OCA\Filinq\Kernel\AppIdentity;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;
use OCP\WorkflowEngine\Events\LoadSettingsScriptsEvent;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;
use OCP\WorkflowEngine\IOperation;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Registers the Filinq Flow operations and their settings script.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-2.1
 */
class RegisterFlowOperationsListener implements IEventListener {

	/**
	 * The rule-builder script, a webpack entry of its own.
	 */
	public const SCRIPT = 'filinq-flow-operations';

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container Resolves each operation.
	 * @param LoggerInterface    $logger    The logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Register the operations, or load their script.
	 *
	 * @param Event $event The event.
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) \OCP\Util::addScript() is Nextcloud's only API for
	 * adding a script to a page Filinq does not own.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.1
	 */
	public function handle(Event $event): void {
		if ($event instanceof LoadSettingsScriptsEvent) {
			Util::addScript(AppIdentity::APP_ID, self::SCRIPT);
			return;
		}

		if ($event instanceof RegisterOperationsEvent === false) {
			return;
		}

		foreach (FlowOperationJob::OPERATIONS as $class) {
			try {
				$operation = $this->container->get($class);
			} catch (Throwable $e) {
				$this->logger->warning(
					'Filinq Flow: an operation could not be registered',
					['operation' => $class, 'exception' => $e->getMessage()]
				);
				continue;
			}

			if ($operation instanceof IOperation) {
				$event->registerOperation($operation);
			}
		}

	}//end handle()
}//end class
