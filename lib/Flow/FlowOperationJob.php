<?php

/**
 * Filinq Flow Operation Job
 *
 * The cron-side half of a Filinq Flow operation: takes the queued run, starts
 * it, resolves the file in its owner's folder tree, runs the operation as the
 * owner and records how it ended. Everything that can go wrong ends the run
 * failed with a reason; nothing leaves a run stuck in queued.
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
use OCA\Filinq\Flow\Operation\AnonymizeDocumentOperation;
use OCA\Filinq\Flow\Operation\ConvertToPdfaOperation;
use OCA\Filinq\Flow\Operation\RunOcrOperation;
use OCA\Filinq\Flow\Operation\RunValidationOperation;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Runs one queued Flow operation.
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
class FlowOperationJob extends QueuedJob {

	/**
	 * The operations a job may run: a stored job argument names a class, and
	 * only these are ever resolved from it.
	 *
	 * @var array<int, class-string<AbstractFileOperation>>
	 */
	public const OPERATIONS = [
		AnonymizeDocumentOperation::class,
		RunOcrOperation::class,
		ConvertToPdfaOperation::class,
		RunValidationOperation::class,
	];

	/**
	 * Reason: the job named no known operation or carried malformed options.
	 */
	public const REASON_INVALID = 'invalid_operation';

	/**
	 * Constructor.
	 *
	 * @param ITimeFactory               $clock       The clock.
	 * @param FlowOperationRunRepository $runs        The run store.
	 * @param IRootFolder                $rootFolder  Resolves the owner's folder tree.
	 * @param IUserManager               $users       Resolves the owner.
	 * @param IUserSession               $userSession Carries the owner while the operation runs.
	 * @param ContainerInterface         $container   Resolves the operation.
	 * @param LoggerInterface            $logger      The logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ITimeFactory $clock,
		private readonly FlowOperationRunRepository $runs,
		private readonly IRootFolder $rootFolder,
		private readonly IUserManager $users,
		private readonly IUserSession $userSession,
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(time: $clock);

	}//end __construct()

	/**
	 * Run the queued operation and record the outcome on its run.
	 *
	 * @param mixed $argument `operationClass`, `config` and the queued `run`.
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) FlowOperationOutcome is a stateless helper or named
	 * constructor; an instance would add nothing.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.3
	 */
	protected function run(mixed $argument): void {
		$argument = (array) $argument;
		$run = (array) ($argument['run'] ?? []);
		if ((string) ($run['uuid'] ?? '') === '') {
			$this->logger->warning('Filinq Flow: a queued job carried no run');
			return;
		}

		$run = $this->runs->save(
			run: array_merge($run, ['status' => FlowRunStatus::RUNNING, 'startedAt' => $this->now()])
		);

		try {
			$outcome = $this->outcome(
				operationClass: (string) ($argument['operationClass'] ?? ''),
				config: (string) ($argument['config'] ?? ''),
				run: $run
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				'Filinq Flow: the operation failed',
				['run' => $run['uuid'], 'exception' => $e->getMessage()]
			);
			$outcome = FlowOperationOutcome::failed(reason: FlowRunStatus::REASON_ERROR, message: $e->getMessage());
		}

		$this->runs->save(
			run: array_merge(
				$run,
				[
					'status' => $outcome->status,
					'statusReason' => $outcome->reason,
					'finishedAt' => $this->now(),
					'resultSummary' => $outcome->resultSummary,
					'producedFileId' => $outcome->producedFileId,
					'errorMessage' => $outcome->errorMessage,
				]
			)
		);

	}//end run()

	/**
	 * Resolve the operation, the owner and the file, and run the operation as the owner.
	 *
	 * @param string               $operationClass The operation class the run was queued for.
	 * @param string               $config         The rule's stored options.
	 * @param array<string, mixed> $run            The started run.
	 *
	 * @return FlowOperationOutcome The outcome.
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) FlowOperationOutcome is a stateless helper or named
	 * constructor; an instance would add nothing.
	 */
	private function outcome(string $operationClass, string $config, array $run): FlowOperationOutcome {
		if (in_array($operationClass, self::OPERATIONS, true) === false) {
			return FlowOperationOutcome::failed(reason: self::REASON_INVALID, message: 'Unknown Flow operation');
		}

		$operation = $this->container->get($operationClass);
		if ($operation instanceof AbstractFileOperation === false) {
			return FlowOperationOutcome::failed(reason: self::REASON_INVALID, message: 'Unknown Flow operation');
		}

		try {
			// The options are checked again: a rule stored before a profile was removed is still refused.
			$operation->validateOperation(name: '', checks: [], operation: $config);
			$options = $operation->decodeConfig(operation: $config);
		} catch (Throwable $e) {
			return FlowOperationOutcome::failed(reason: self::REASON_INVALID, message: $e->getMessage());
		}

		$uid = (string) ($run['ownerUserId'] ?? '');
		$owner = $this->users->get($uid);
		if ($owner === null) {
			return FlowOperationOutcome::failed(reason: FlowRunStatus::REASON_OWNER_GONE, message: 'The file owner no longer exists');
		}

		$previous = $this->userSession->getUser();
		$this->userSession->setVolatileActiveUser($owner);
		try {
			$home = $this->rootFolder->getUserFolder($uid);
			$file = $home->getFirstNodeById((int) ($run['fileId'] ?? 0));
			if ($file instanceof File === false) {
				return FlowOperationOutcome::failed(reason: FlowRunStatus::REASON_FILE_GONE, message: 'The file is gone');
			}

			return FlowOutputGuard::producing(
				static fn (): FlowOperationOutcome => $operation->execute(file: $file, config: $options, ownerHome: $home)
			);
		} finally {
			$this->userSession->setVolatileActiveUser($previous);
		}

	}//end outcome()

	/**
	 * The current moment, as the register stores it.
	 *
	 * @return string The moment.
	 */
	private function now(): string {
		return $this->clock->getDateTime()->format(DATE_ATOM);

	}//end now()
}//end class
