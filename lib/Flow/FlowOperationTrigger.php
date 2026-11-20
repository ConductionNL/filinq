<?php

/**
 * Filinq Flow Operation Trigger
 *
 * The request-side half of every Filinq Flow operation. onEvent() runs inside
 * the request that touched the file, so this does only the cheap part: find
 * the file, ask the engine which of the operation's rules matched, skip the
 * operation's own output and in-flight duplicates, record a queued run and
 * enqueue one FlowOperationJob. No document is read here.
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
 * @spec openspec/changes/flow-operations/tasks.md#task-2.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Flow;

use OCA\Filinq\Flow\Operation\AbstractFileOperation;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\GenericEvent;
use OCP\Files\Events\Node\AbstractNodeEvent;
use OCP\Files\Events\Node\AbstractNodesEvent;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\SystemTag\MapperEvent;
use OCP\WorkflowEngine\IRuleMatcher;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Matches a Flow event and queues the run.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) The class joins the Flow event, the operation
 * registry and the run log of one Filinq operation; its value objects and exceptions count toward
 * the coupling too.
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-2.3
 */
class FlowOperationTrigger {

	/**
	 * Short names for the file entity's trigger events.
	 *
	 * @var array<string, string>
	 */
	private const EVENT_NAMES = [
		'\OCP\Files::postCreate' => 'postCreate',
		'\OCP\Files::postWrite' => 'postWrite',
		'\OCP\Files::postRename' => 'postRename',
		'\OCP\Files::postCopy' => 'postCopy',
		'\OCP\Files::postTouch' => 'postTouch',
		MapperEvent::EVENT_ASSIGN => 'tag_assigned',
	];

	/**
	 * Constructor.
	 *
	 * @param FlowOperationRunRepository $runs       The run store.
	 * @param IJobList                   $jobList    The background job list.
	 * @param IRootFolder                $rootFolder Resolves a tagged file by id.
	 * @param LoggerInterface            $logger     The logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly FlowOperationRunRepository $runs,
		private readonly IJobList $jobList,
		private readonly IRootFolder $rootFolder,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Queue a run of the operation for the event's file when one of its rules matched.
	 *
	 * Never throws: a Flow operation that fails here would fail the upload
	 * that triggered it.
	 *
	 * @param AbstractFileOperation $operation   The operation the engine called.
	 * @param string                $eventName   The engine's event name.
	 * @param Event                 $event       The event.
	 * @param IRuleMatcher          $ruleMatcher The engine's matcher for this event.
	 *
	 * @return string|null The queued run's uuid, or null when nothing was queued.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.3
	 */
	public function trigger(AbstractFileOperation $operation, string $eventName, Event $event, IRuleMatcher $ruleMatcher): ?string {
		try {
			return $this->queue(operation: $operation, eventName: $eventName, event: $event, ruleMatcher: $ruleMatcher);
		} catch (Throwable $e) {
			$this->logger->warning(
				'Filinq Flow: the operation could not be queued',
				['operation' => $operation->key(), 'event' => $eventName, 'exception' => $e->getMessage()]
			);
			return null;
		}

	}//end trigger()

	/**
	 * The work of trigger(), allowed to throw.
	 *
	 * @param AbstractFileOperation $operation   The operation.
	 * @param string                $eventName   The event name.
	 * @param Event                 $event       The event.
	 * @param IRuleMatcher          $ruleMatcher The matcher.
	 *
	 * @return string|null The queued run's uuid, or null.
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) FlowOutputGuard is a stateless helper or named
	 * constructor; an instance would add nothing.
	 */
	private function queue(AbstractFileOperation $operation, string $eventName, Event $event, IRuleMatcher $ruleMatcher): ?string {
		$file = $this->fileOf(event: $event);
		if ($file === null) {
			// Folders and non-file entities are not Filinq's business.
			return null;
		}

		$flows = $ruleMatcher->getFlows(false);
		if ($flows === []) {
			return null;
		}

		$owner = (string) ($file->getOwner()?->getUID() ?? '');
		if ($owner === '') {
			return null;
		}

		$fileId = (int) $file->getId();
		$run = [
			'operation' => $operation->key(),
			'fileId' => $fileId,
			'fileName' => $file->getName(),
			'ownerUserId' => $owner,
			'triggerEvent' => (self::EVENT_NAMES[$eventName] ?? $this->shortName(eventName: $eventName)),
			'resultSummary' => [],
		];

		if (FlowOutputGuard::isProducing() === true || $this->runs->isProducedFile(fileId: $fileId) === true) {
			$this->runs->save(
				run: $run + ['status' => FlowRunStatus::SKIPPED, 'statusReason' => FlowRunStatus::REASON_OWN_OUTPUT]
			);
			return null;
		}

		if ($this->runs->hasRunInFlight(operation: $operation->key(), fileId: $fileId) === true) {
			return null;
		}

		// One run per {operation, file}: the first matching rule's options win.
		$first = reset($flows);
		$config = '';
		if (is_array($first) === true) {
			$config = (string) ($first['operation'] ?? '');
		}

		$stored = $this->runs->save(run: $run + ['status' => FlowRunStatus::QUEUED]);
		$this->jobList->add(
			FlowOperationJob::class,
			[
				'operationClass' => $operation::class,
				'config' => $config,
				'run' => $stored,
			]
		);

		return (string) ($stored['uuid'] ?? '');

	}//end queue()

	/**
	 * The file an event is about, or null for a folder, a non-file object or an unknown event shape.
	 *
	 * @param Event $event The event.
	 *
	 * @return File|null The file.
	 */
	private function fileOf(Event $event): ?File {
		$node = null;
		if ($event instanceof MapperEvent) {
			if ($event->getObjectType() !== 'files') {
				return null;
			}

			$node = $this->rootFolder->getFirstNodeById((int) $event->getObjectId());
		} else if ($event instanceof AbstractNodesEvent) {
			$node = $event->getTarget();
		} else if ($event instanceof AbstractNodeEvent) {
			$node = $event->getNode();
		} else if ($event instanceof GenericEvent) {
			$node = $event->getSubject();
			if (is_array($node) === true) {
				// Rename and copy carry [source, target]; the target is the file now.
				$node = end($node);
			}
		}

		if ($node instanceof File) {
			return $node;
		}

		return null;

	}//end fileOf()

	/**
	 * The part of an event name after its last `::`.
	 *
	 * @param string $eventName The event name.
	 *
	 * @return string The short name.
	 */
	private function shortName(string $eventName): string {
		$position = strrpos($eventName, '::');
		if ($position === false) {
			return $eventName;
		}

		return substr($eventName, ($position + 2));

	}//end shortName()
}//end class
