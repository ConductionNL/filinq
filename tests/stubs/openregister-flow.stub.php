<?php

/**
 * Stub for OpenRegister's flow-node contract (the parts filinq implements).
 *
 * Used twice: PHPStan reads it through `scanFiles` and psalm through
 * `<stubs>`, and the unit bootstrap requires it so the node and its listener
 * can be exercised against the real interface shape. It is never loaded at
 * runtime: the runtime classes live in the openregister sibling app, which
 * is co-installed on the instance but is not a composer dependency.
 *
 * Why a stub rather than a suppression: PHPStan refuses to silence
 * "implements unknown interface" through `ignoreErrors`, and a suppression
 * would also stop it checking that GenerateDocumentNode's methods match the
 * contract. The signatures mirror, verbatim, openregister `development`:
 *   lib/Service/Flow/IFlowNode.php
 *   lib/Service/Flow/IFlowNodeConfigKeys.php
 *   lib/Service/Flow/IFlowNodeConfigForm.php
 *   lib/Service/Flow/IFlowNodeTaxonomy.php
 *   lib/Service/Flow/RegisterFlowNodesEvent.php
 *   lib/Service/Flow/FlowNodeRegistry.php (register() and all() only)
 * Keep them in sync when OpenRegister changes one.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\OpenRegister\Service\Flow;

use OCP\EventDispatcher\Event;

/**
 * A node type contributed to the flow palette by an app.
 */
interface IFlowNode {
	/**
	 * The step `type` this node answers to.
	 *
	 * @return string The type identifier.
	 */
	public function getId(): string;

	/**
	 * Human-readable name for the palette.
	 *
	 * @return string The display name.
	 */
	public function getDisplayName(): string;

	/**
	 * What this node does, in one sentence.
	 *
	 * @return string The description.
	 */
	public function getDescription(): string;

	/**
	 * Absolute URL of the palette icon.
	 *
	 * @return string The icon URL.
	 */
	public function getIcon(): string;

	/**
	 * Whether this node is offered in the given scope.
	 *
	 * @param int $scope The scope constant.
	 *
	 * @return bool Whether it is available.
	 */
	public function isAvailableForScope(int $scope): bool;

	/**
	 * Reject a configuration the author cannot have meant.
	 *
	 * @param array $config The step's authored configuration.
	 *
	 * @return void
	 *
	 * @throws \UnexpectedValueException When the configuration is unusable.
	 */
	public function validateConfig(array $config): void;

	/**
	 * Do the work: items in, items out.
	 *
	 * @param array $items The input items.
	 * @param array $config The step's authored configuration.
	 * @param array $context Run-level metadata.
	 *
	 * @return array The output items.
	 */
	public function execute(array $items, array $config, array $context): array;
}//end interface

/**
 * Declares which configuration keys a node reads.
 */
interface IFlowNodeConfigKeys {
	/**
	 * The configuration keys this node reads.
	 *
	 * @return array<int, string> The accepted keys.
	 */
	public function configKeys(): array;
}//end interface

/**
 * Declares an editable form for a node type's configuration.
 */
interface IFlowNodeConfigForm {
	/**
	 * The fields this node's configuration is edited through.
	 *
	 * @return array<int, array<string, mixed>> The field descriptions.
	 */
	public function configForm(): array;
}//end interface

/**
 * A node that can say what kind of step it is, and where it belongs.
 */
interface IFlowNodeTaxonomy {
	public const KIND_USER_TASK = 'userTask';
	public const KIND_SERVICE_TASK = 'serviceTask';
	public const KIND_SCRIPT_TASK = 'scriptTask';
	public const KIND_BUSINESS_RULE_TASK = 'businessRuleTask';
	public const KIND_SEND_TASK = 'sendTask';
	public const KIND_RECEIVE_TASK = 'receiveTask';
	public const KIND_MANUAL_TASK = 'manualTask';
	public const KIND_GATEWAY = 'gateway';
	public const KIND_EVENT = 'event';
	public const KIND_SUB_PROCESS = 'subProcess';
	public const KINDS = [
		self::KIND_USER_TASK,
		self::KIND_SERVICE_TASK,
		self::KIND_SCRIPT_TASK,
		self::KIND_BUSINESS_RULE_TASK,
		self::KIND_SEND_TASK,
		self::KIND_RECEIVE_TASK,
		self::KIND_MANUAL_TASK,
		self::KIND_GATEWAY,
		self::KIND_EVENT,
		self::KIND_SUB_PROCESS,
	];
	public const CATEGORY_TRIGGERS = 'triggers';
	public const CATEGORY_HUMAN = 'human';
	public const CATEGORY_OBJECTS = 'objects';
	public const CATEGORY_LOGIC = 'logic';
	public const CATEGORY_MESSAGING = 'messaging';
	public const CATEGORY_AI = 'ai';
	public const CATEGORY_INTEGRATIONS = 'integrations';
	public const CATEGORY_OTHER = 'other';
	public const CATEGORIES = [
		self::CATEGORY_TRIGGERS,
		self::CATEGORY_HUMAN,
		self::CATEGORY_OBJECTS,
		self::CATEGORY_LOGIC,
		self::CATEGORY_MESSAGING,
		self::CATEGORY_AI,
		self::CATEGORY_INTEGRATIONS,
		self::CATEGORY_OTHER,
	];

	/**
	 * What kind of step this is, in BPMN's vocabulary.
	 *
	 * @return string One of {@see self::KINDS}.
	 */
	public function getKind(): string;

	/**
	 * Where in the palette an author should find this step.
	 *
	 * @return string One of {@see self::CATEGORIES}.
	 */
	public function getCategory(): string;
}//end interface

/**
 * The node catalogue (register() and all() only).
 */
class FlowNodeRegistry {
	/**
	 * The registered nodes, keyed by id.
	 *
	 * @var array<string, IFlowNode>
	 */
	private array $nodes = [];

	/**
	 * Contribute a node type.
	 *
	 * @param IFlowNode $node The node type.
	 *
	 * @return void
	 */
	public function register(IFlowNode $node): void {
		$this->nodes[$node->getId()] = $node;
	}//end register()

	/**
	 * Every registered node, optionally filtered by scope.
	 *
	 * @param int|null $scope The scope constant, or null for all.
	 *
	 * @return array<string, IFlowNode> The nodes, keyed by id.
	 */
	public function all(?int $scope = null): array {
		if ($scope === null) {
			return $this->nodes;
		}

		return array_filter(
			$this->nodes,
			static fn (IFlowNode $node): bool => $node->isAvailableForScope($scope)
		);
	}//end all()
}//end class

/**
 * Carries the registry an app registers its node types on.
 */
class RegisterFlowNodesEvent extends Event {
	/**
	 * Constructor.
	 *
	 * @param FlowNodeRegistry $registry The registry to contribute to.
	 */
	public function __construct(
		private readonly FlowNodeRegistry $registry,
	) {
		parent::__construct();
	}//end __construct()

	/**
	 * Contribute a node type.
	 *
	 * @param IFlowNode $node The node type.
	 *
	 * @return void
	 */
	public function registerNode(IFlowNode $node): void {
		$this->registry->register(node: $node);
	}//end registerNode()
}//end class
