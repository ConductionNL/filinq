<?php

/**
 * Contract of DocumentMergeService
 *
 * The public surface of the DocumentMergeService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use OCA\Filinq\Exception\MergeRefusedException;

/**
 * Contract of DocumentMergeService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface DocumentMergeServiceInterface {
	/**
	 * The schema holding the jobs.
	 *
	 * @var string
	 */
	public const SCHEMA = 'mergeJob';

	/**
	 * Queued, not started.
	 *
	 * @var string
	 */
	public const STATUS_QUEUED = 'queued';

	/**
	 * Running.
	 *
	 * @var string
	 */
	public const STATUS_RUNNING = 'running';

	/**
	 * Finished, with a result file.
	 *
	 * @var string
	 */
	public const STATUS_DONE = 'done';

	/**
	 * Stopped, with a reason and no result file.
	 *
	 * @var string
	 */
	public const STATUS_FAILED = 'failed';

	/**
	 * Merge a selection into one PDF.
	 *
	 * @param array<int, array<string, mixed>> $inputs The documents, in order, each `{fileId, label}`.
	 * @param array<string, mixed> $options The cover template, the bookmarks toggle, the target folder and the name.
	 * @param array<string, mixed> $hostObject The object the merge was started from.
	 *
	 * @return array<string, mixed> The finished job.
	 *
	 * @throws MergeRefusedException When the caller may not read an input or write the target.
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function merge(array $inputs, array $options = [], array $hostObject = []): array;

	/**
	 * Record a merge to be run later, once the checks have passed.
	 *
	 * The checks run HERE, while the person who asked is still on the other end
	 * of the request. A queued job is the record that they passed, which is why
	 * the background job does not repeat them as somebody else.
	 *
	 * @param array<int, array<string, mixed>> $inputs The documents, in order.
	 * @param array<string, mixed> $options What is asked for.
	 * @param array<string, mixed> $hostObject The object the merge was started from.
	 *
	 * @return array<string, mixed> The queued job.
	 *
	 * @throws MergeRefusedException When the caller may not read an input or write the target.
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function queue(array $inputs, array $options = [], array $hostObject = []): array;

	/**
	 * Run a job that was queued earlier.
	 *
	 * Every input is resolved as the person who ASKED, not as whoever the
	 * background runner happens to be. A merge that ran as an administrator
	 * would quietly include files the requester could not read, and the bundle
	 * would look exactly the same.
	 *
	 * @param array<string, mixed> $job The queued job.
	 *
	 * @return array<string, mixed> The finished job.
	 *
	 * @throws MergeRefusedException When the inputs no longer resolve for that person.
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function resume(array $job): array;

	/**
	 * Whether a selection is large enough to be queued rather than waited for.
	 *
	 * The page count is an ESTIMATE: the real one is only known after every
	 * input has been converted, and converting them to decide whether to
	 * convert them in the background defeats the point. A selection that says
	 * how many pages each input has is believed; one that does not is estimated
	 * from the file sizes.
	 *
	 * @param array<int, array<string, mixed>> $inputs The requested inputs.
	 * @param int $threshold The page count above which a merge is queued.
	 *
	 * @return bool True when the merge should be queued.
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function shouldQueue(array $inputs, int $threshold): bool;
}//end interface
