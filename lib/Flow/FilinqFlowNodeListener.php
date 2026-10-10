<?php

/**
 * Filinq Flow Node Listener
 *
 * Presents Filinq's flow steps to OpenRegister's flow engine. OpenRegister
 * owns the engine (ADR-065) and dispatches RegisterFlowNodesEvent when it
 * needs the node catalogue; Filinq answers by registering what it can do,
 * which is generate documents (ADR-075).
 *
 * Nodes are resolved from a class-string list, so adding one stays one line.
 * A node that cannot be constructed is logged and skipped rather than
 * aborting the loop: a missing node is visible (the editor does not offer
 * it), a failed registration of all of them is not.
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
 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-filinq-registers-its-node-and-still-boots-without-openregister
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Flow;

use OCA\OpenRegister\Service\Flow\RegisterFlowNodesEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Registers Filinq's nodes on OpenRegister's flow catalogue.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @template-implements IEventListener<Event>
 *
 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-filinq-registers-its-node-and-still-boots-without-openregister
 */
class FilinqFlowNodeListener implements IEventListener {

	/**
	 * The nodes Filinq contributes.
	 *
	 * @var array<int, class-string>
	 */
	private const NODES = [
		GenerateDocumentNode::class,
	];

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container Resolves each node.
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
	 * Register the Filinq nodes on the catalogue.
	 *
	 * @param Event $event The dispatched event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-filinq-registers-its-node-and-still-boots-without-openregister
	 */
	public function handle(Event $event): void {
		if (($event instanceof RegisterFlowNodesEvent) === false) {
			return;
		}

		foreach (self::NODES as $nodeClass) {
			try {
				$node = $this->container->get($nodeClass);
			} catch (Throwable $e) {
				$this->logger->warning(
					'filinq: flow node ' . $nodeClass . ' could not be registered: ' . $e->getMessage(),
					['app' => 'filinq']
				);
				continue;
			}

			$event->registerNode($node);
		}
	}//end handle()

	/**
	 * The node classes this listener registers.
	 *
	 * @return array<int, class-string> The node classes.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-filinq-registers-its-node-and-still-boots-without-openregister
	 */
	public static function nodeClasses(): array {
		return self::NODES;
	}//end nodeClasses()
}//end class
