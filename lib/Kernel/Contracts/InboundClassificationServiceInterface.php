<?php

/**
 * Contract of InboundClassificationService
 *
 * The public surface of the InboundClassificationService service, so callers in other
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

/**
 * Contract of InboundClassificationService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface InboundClassificationServiceInterface {
	/**
	 * The admin toggle; on unless set to "0".
	 *
	 * @var string
	 */
	public const TOGGLE = 'enable_inbound_classification';

	/**
	 * Whether the admin toggle is on.
	 *
	 * @return bool True unless the setting is "0".
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-5
	 */
	public function isEnabled(): bool;

	/**
	 * Suggest a type, correspondent and dossier for one document object.
	 *
	 * @param array<string, mixed> $objectData The object's fields.
	 * @param array<string, string> $objectRef  The object: id, register, schema.
	 *
	 * @return array{outcome: string, reason?: string, record?: array<string, mixed>}
	 *     `suggested` with the record, or `skipped` with the reason.
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-2
	 */
	public function classify(array $objectData, array $objectRef): array;
}//end interface
