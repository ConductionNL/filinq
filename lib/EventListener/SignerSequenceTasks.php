<?php

/**
 * Signer Sequence Tasks
 *
 * Reads every position's task of an OR task sequence through OR's task
 * mapper, resolved by FQN string at call time so this class loads with OR
 * absent.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The tasks of one task sequence, or null when they cannot be read.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */
class SignerSequenceTasks {

	/**
	 * OR's task mapper, resolved by name so no OR type is imported.
	 *
	 * @var string
	 */
	private const TASK_MAPPER_CLASS = 'OCA\OpenRegister\Db\TaskMapper';

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container Resolves OR's task mapper.
	 * @param LoggerInterface    $logger    Logger.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Every task of a sequence, in position order.
	 *
	 * @param string $sequenceUuid UUID of the OR task sequence.
	 *
	 * @return array<int, object>|null The tasks, or null when the mapper is
	 *                                 unavailable or the read fails.
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function forSequence(string $sequenceUuid): ?array {
		try {
			$tasks = $this->container->get(self::TASK_MAPPER_CLASS)->findBySequence($sequenceUuid);
		} catch (Throwable) {
			$this->logger->warning(
				'SigningTaskListener: could not read the tasks of a sequence',
				['sequenceUuid' => $sequenceUuid]
			);
			return null;
		}

		if (is_array($tasks) === false) {
			return null;
		}

		return array_values(array_filter($tasks, 'is_object'));
	}//end forSequence()

	/**
	 * The tasks of a sequence at a position before the given one.
	 *
	 * @param string $sequenceUuid UUID of the OR task sequence.
	 * @param int    $position     The position whose predecessors to read.
	 *
	 * @return array<int, object> The earlier tasks; empty when none can be read.
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function before(string $sequenceUuid, int $position): array {
		$earlier = [];
		foreach (($this->forSequence(sequenceUuid: $sequenceUuid) ?? []) as $task) {
			if (is_callable([$task, 'getSequencePosition']) === true && (int) $task->getSequencePosition() < $position) {
				$earlier[] = $task;
			}
		}

		return $earlier;
	}//end before()
}//end class
