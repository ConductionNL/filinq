<?php

/**
 * Contract of CaseSystemDeliveryService
 *
 * The public surface of CaseSystemDeliveryService that other components call, so they depend
 * on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction b.v. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://github.com/ConductionNL/filinq
 *
 * @spec openspec/specs/method-decomposition/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use InvalidArgumentException;
use RuntimeException;

/**
 * Contract of CaseSystemDeliveryService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction b.v. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://github.com/ConductionNL/filinq
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface CaseSystemDeliveryServiceInterface {

	/**
	 * Every declared destination, for the bridge panel.
	 *
	 * @return array<int, array<string, mixed>> The destinations.
	 *
	 * @spec openspec/changes/archive/2026-11-25-generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-an-admin-declares-where-generated-documents-go-in-the-case-system-req-gsc-001
	 */
	public function listDestinations(): array;

	/**
	 * The waiting and failed deliveries, for the bridge panel.
	 *
	 * @return array<int, array<string, mixed>> The deliveries, waiting ones first.
	 *
	 * @spec openspec/changes/archive/2026-11-25-generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
	 */
	public function openDeliveries(): array;

	/**
	 * Put a failed delivery back in the queue, on the same object (REQ-GSC-003, design D5).
	 *
	 * @param string $id The delivery id.
	 *
	 * @return array<string, mixed> The delivery, waiting again.
	 *
	 * @throws RuntimeException 404 when there is no such delivery, 409 when it is not in `writeback_failed`.
	 *
	 * @spec openspec/changes/archive/2026-11-25-generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
	 */
	public function retry(string $id): array;

	/**
	 * Create or update a destination; a second active one for the same template or namespace is refused.
	 *
	 * @param array<string, mixed> $input The destination fields, with `id` to update an existing one.
	 *
	 * @return array<string, mixed> The destination as stored.
	 *
	 * @throws InvalidArgumentException When it names neither a template nor a namespace, has no source,
	 *                                  or would be a second active destination.
	 *
	 * @spec openspec/changes/archive/2026-11-25-generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-an-admin-declares-where-generated-documents-go-in-the-case-system-req-gsc-001
	 */
	public function saveDestination(array $input): array;
}//end interface
