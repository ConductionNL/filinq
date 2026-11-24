<?php

/**
 * Contract of RefusalGroundsResolver
 *
 * The public surface of the RefusalGroundsResolver service that callers in other
 * components use, so they depend on this contract instead of the implementation.
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

use RuntimeException;

/**
 * Contract of RefusalGroundsResolver.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface RefusalGroundsResolverInterface {
	/**
	 * The grounds: from dossiq when it can answer, else from the snapshot.
	 *
	 * @return array{source: string, version: string, grounds: list<array<string, mixed>>}
	 *
	 * @throws RuntimeException When the snapshot is needed and cannot be read; an empty list is never answered.
	 *
	 * @spec openspec/changes/archive/2026-11-24-grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-one-resolver-reads-the-grounds-from-dossiq-or-from-a-read-only-snapshot-req-grd-001
	 */
	public function list(): array;
}//end interface
