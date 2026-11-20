<?php

/**
 * Filinq Flow Output Guard
 *
 * Marks the stretch of a process in which a Filinq Flow operation writes its
 * output. Nextcloud dispatches "File created" synchronously inside the write,
 * so a create event that arrives while an operation is producing is that
 * operation's own output and must not start another run (the converter loop).
 * The register lookup on producedFileId covers every later event.
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
 * @spec openspec/changes/flow-operations/tasks.md#task-2.4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Flow;

/**
 * The in-process half of the loop guard.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-2.4
 */
final class FlowOutputGuard {

	/**
	 * How many operations of this process are writing output right now.
	 *
	 * @var integer
	 */
	private static int $depth = 0;

	/**
	 * Run a callable as an operation that may write output.
	 *
	 * @param callable(): T $work The work.
	 *
	 * @return T What the work returns.
	 *
	 * @template T
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.4
	 */
	public static function producing(callable $work): mixed {
		self::$depth++;
		try {
			return $work();
		} finally {
			self::$depth--;
		}

	}//end producing()

	/**
	 * Whether an operation of this process is writing output now.
	 *
	 * @return bool True while producing.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.4
	 */
	public static function isProducing(): bool {
		return self::$depth > 0;

	}//end isProducing()
}//end class
