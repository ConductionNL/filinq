<?php

/**
 * Contract of SubjectErasureService
 *
 * The public surface of the SubjectErasureService service, so callers in other
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

use OCA\Filinq\Exception\SubjectErasureRefusedException;

/**
 * Contract of SubjectErasureService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SubjectErasureServiceInterface {
	/**
	 * States from which a preview may be (re)built.
	 *
	 * @var string[]
	 */
	public const PREVIEWABLE = ['received', 'previewed', 'partially_completed'];

	/**
	 * Refuse unless the user may act, and record the denial.
	 *
	 * @param string $userId      The caller.
	 * @param string $requestUuid The request the attempt was about, '' for none.
	 *
	 * @return void
	 *
	 * @throws SubjectErasureRefusedException When the caller may not act.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-4.1
	 */
	public function assertMayErase(string $userId, string $requestUuid = ''): void;

	/**
	 * Every request, newest first.
	 *
	 * @param string $userId The caller.
	 *
	 * @return array<int, array<string, mixed>> The requests.
	 *
	 * @throws SubjectErasureRefusedException When the caller may not act.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.1
	 */
	public function list(string $userId): array;

	/**
	 * One request.
	 *
	 * @param string $uuid   The request uuid.
	 * @param string $userId The caller.
	 *
	 * @return array<string, mixed> The request.
	 *
	 * @throws SubjectErasureRefusedException When the caller may not act, or there is no such request.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.1
	 */
	public function get(string $uuid, string $userId): array;

	/**
	 * Place a request. The subject, the ground and the requester are recorded
	 * before anything is looked up (REQ-EPR-01).
	 *
	 * @param array<string, mixed> $input  subject, identifiers, ground, requester, dueAt.
	 * @param string               $userId The caller.
	 *
	 * @return array<string, mixed> The stored request.
	 *
	 * @throws SubjectErasureRefusedException When the caller may not act, or the input is incomplete.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-1.1
	 */
	public function create(array $input, string $userId): array;

	/**
	 * Build the preview. Nothing is written but the counts on the request.
	 *
	 * @param string $uuid   The request uuid.
	 * @param string $userId The caller.
	 *
	 * @return array<string, mixed> The preview: documents (capped), totals, cap,
	 *                              unprocessable, and each document's name and values.
	 *
	 * @throws SubjectErasureRefusedException When refused, or the catalogue cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.1
	 */
	public function preview(string $uuid, string $userId): array;

	/**
	 * Record exclusions. Each needs a reason; one without is refused and nothing is stored.
	 *
	 * @param string                           $uuid       The request uuid.
	 * @param array<int, array<string, mixed>> $exclusions {occurrence: "<fileId>" or "<fileId>:<value>", reason}.
	 * @param string                           $userId     The caller.
	 *
	 * @return array<string, mixed> The request with its exclusions.
	 *
	 * @throws SubjectErasureRefusedException When refused, not previewed, or an exclusion is incomplete.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.2
	 */
	public function exclude(string $uuid, array $exclusions, string $userId): array;

	/**
	 * A request, or not_found.
	 *
	 * @param string $uuid The uuid.
	 *
	 * @return array<string, mixed> The request.
	 *
	 * @throws SubjectErasureRefusedException When there is none.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.1
	 */
	public function load(string $uuid): array;

	/**
	 * Refuse a step the request's state does not allow.
	 *
	 * @param array<string, mixed> $request The request.
	 * @param array<int, string>   $allowed The states the step accepts.
	 *
	 * @return void
	 *
	 * @throws SubjectErasureRefusedException When the state does not fit.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-3.1
	 */
	public function assertState(array $request, array $allowed): void;
}//end interface
