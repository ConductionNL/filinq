<?php

/**
 * Filinq Abstract File Operation
 *
 * What the four Filinq Flow operations share: they act on the file entity, in
 * the admin and the personal scope, and onEvent() only queues (D2). Each
 * subclass names itself, validates its own options and does its work in
 * execute(), which the background job calls in the file owner's context.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow\Operation
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Flow\Operation;

use JsonException;
use OCA\Filinq\Flow\FlowOperationOutcome;
use OCA\Filinq\Flow\FlowOperationTrigger;
use OCP\EventDispatcher\Event;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IRuleMatcher;
use OCP\WorkflowEngine\ISpecificOperation;
use UnexpectedValueException;

/**
 * Base of the Filinq Flow operations.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow\Operation
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
 */
abstract class AbstractFileOperation implements ISpecificOperation {

	/**
	 * The Flow engine's file entity.
	 */
	public const FILE_ENTITY = 'OCA\WorkflowEngine\Entity\File';

	/**
	 * Constructor.
	 *
	 * @param IL10N                $l10n         Translates the name and description.
	 * @param IURLGenerator        $urlGenerator Builds the icon URL.
	 * @param FlowOperationTrigger $trigger      Queues the run.
	 *
	 * @return void
	 */
	public function __construct(
		protected readonly IL10N $l10n,
		protected readonly IURLGenerator $urlGenerator,
		private readonly FlowOperationTrigger $trigger,
	) {

	}//end __construct()

	/**
	 * The operation key recorded on its runs (anonymize, ocr, pdfa or validate).
	 *
	 * @return string The key.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	abstract public function key(): string;

	/**
	 * Do the operation's work on a file, as its owner.
	 *
	 * @param File                 $file      The file, resolved in the owner's folder tree.
	 * @param array<string, mixed> $config    The rule's validated options.
	 * @param Folder               $ownerHome The owner's home folder.
	 *
	 * @return FlowOperationOutcome What the run came to.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.5
	 */
	abstract public function execute(File $file, array $config, Folder $ownerHome): FlowOperationOutcome;

	/**
	 * Check the decoded options; throw for anything the operation cannot run with.
	 *
	 * @param array<string, mixed> $config The decoded options.
	 *
	 * @return void
	 *
	 * @throws UnexpectedValueException For a malformed option.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	protected function validateConfig(array $config): void {
		if ($config !== []) {
			throw new UnexpectedValueException($this->l10n->t('This operation takes no options'));
		}

	}//end validateConfig()

	/**
	 * {@inheritDoc}
	 *
	 * @return string The file entity.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	public function getEntityId(): string {
		return self::FILE_ENTITY;

	}//end getEntityId()

	/**
	 * {@inheritDoc}
	 *
	 * @return string The icon URL.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	public function getIcon(): string {
		return $this->urlGenerator->imagePath('filinq', 'app-dark.svg');

	}//end getIcon()

	/**
	 * {@inheritDoc}
	 *
	 * @param integer $scope The scope.
	 *
	 * @return bool True for the admin and the personal scope.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	public function isAvailableForScope(int $scope): bool {
		return $scope === IManager::SCOPE_ADMIN || $scope === IManager::SCOPE_USER;

	}//end isAvailableForScope()

	/**
	 * {@inheritDoc}
	 *
	 * @param string             $name      The rule name.
	 * @param array<int, mixed>  $checks    The rule's checks; the engine owns them.
	 * @param string             $operation The options, as the JSON the rule stores.
	 *
	 * @return void
	 *
	 * @throws UnexpectedValueException When the options are malformed.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	public function validateOperation(string $name, array $checks, string $operation): void {
		$this->validateConfig(config: $this->decodeConfig(operation: $operation));

	}//end validateOperation()

	/**
	 * {@inheritDoc}
	 *
	 * @param string       $eventName   The event name.
	 * @param Event        $event       The event.
	 * @param IRuleMatcher $ruleMatcher The matcher.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.3
	 */
	public function onEvent(string $eventName, Event $event, IRuleMatcher $ruleMatcher): void {
		$this->trigger->trigger(operation: $this, eventName: $eventName, event: $event, ruleMatcher: $ruleMatcher);

	}//end onEvent()

	/**
	 * Decode the stored options: empty means none, anything else must be a JSON object.
	 *
	 * @param string $operation The stored options.
	 *
	 * @return array<string, mixed> The options.
	 *
	 * @throws UnexpectedValueException When the options are not a JSON object.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	public function decodeConfig(string $operation): array {
		if (trim($operation) === '') {
			return [];
		}

		try {
			$decoded = json_decode($operation, true, 8, JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			throw new UnexpectedValueException($this->l10n->t('The operation options are not valid JSON'));
		}

		if (is_array($decoded) === false || ($decoded !== [] && array_is_list($decoded) === true)) {
			throw new UnexpectedValueException($this->l10n->t('The operation options must be an object'));
		}

		return $decoded;

	}//end decodeConfig()
}//end class
