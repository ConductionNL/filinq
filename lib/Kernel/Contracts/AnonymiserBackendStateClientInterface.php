<?php

/**
 * Contract of AnonymiserBackendStateClient
 *
 * The public surface of the AnonymiserBackendStateClient service, so callers in other
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

use OCA\Filinq\Exception\DetectionUnavailableException;

/**
 * Contract of AnonymiserBackendStateClient.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface AnonymiserBackendStateClientInterface {
	/**
	 * The class OpenRegister declares, under its Anonymisation namespace.
	 *
	 * @var string
	 */
	public const OR_SERVICE = 'OCA\OpenRegister\Service\Anonymisation\AnonymisationBackendService';

	/**
	 * Refusal: filinq could not read which detector is live.
	 *
	 * @var string
	 */
	public const REFUSE_UNKNOWN = DetectionUnavailableException::REASON_UNKNOWN;

	/**
	 * Refusal: entity recognition is switched off on this instance.
	 *
	 * @var string
	 */
	public const REFUSE_DISABLED = DetectionUnavailableException::REASON_DISABLED;

	/**
	 * Refusal: the backend OpenRegister would use says it is unavailable.
	 *
	 * @var string
	 */
	public const REFUSE_UNAVAILABLE = DetectionUnavailableException::REASON_UNAVAILABLE;

	/**
	 * Retrieve the current anonymisation backend state.
	 *
	 * Keys: `known` (bool, false when OpenRegister could not be read),
	 * `entityRecognitionEnabled` (bool|null), `activeMethod` and
	 * `effectiveMethod` (string|null, OpenRegister's own values),
	 * `effectiveAvailable` (bool, the probe of the effective backend) and
	 * `backends` (array, OpenRegister's per-backend records).
	 *
	 * @return array{known: bool, entityRecognitionEnabled: bool|null, activeMethod: string|null,
	 *               effectiveMethod: string|null, effectiveAvailable: bool, backends: array<string, mixed>}
	 *
	 * @spec openspec/changes/archive/2026-09-29-anonymisation-fails-closed-without-a-detector/tasks.md#task-1
	 */
	public function getState(): array;

	/**
	 * Why an anonymisation run must be refused under this state, or null when a
	 * detector is live.
	 *
	 * @param array<string, mixed> $state A state from {@see getState()}.
	 *
	 * @return string|null One of the REFUSE_* reasons, or null.
	 *
	 * @spec openspec/changes/archive/2026-09-29-anonymisation-fails-closed-without-a-detector/tasks.md#task-2
	 */
	public function refusalReason(array $state): ?string;

	/**
	 * Which admin warning this state calls for, or null when a real detector is live.
	 *
	 * `unknown`, `disabled` and `unavailable` mean anonymisation is refused;
	 * `regex` means it runs with pattern matching only, which finds numbers and
	 * addresses of a known shape but no names.
	 *
	 * @param array<string, mixed> $state A state from {@see getState()}.
	 *
	 * @return string|null One of unknown, disabled, unavailable, regex, or null.
	 *
	 * @spec openspec/changes/archive/2026-09-29-anonymisation-fails-closed-without-a-detector/tasks.md#task-4
	 */
	public function warningFor(array $state): ?string;
}//end interface
