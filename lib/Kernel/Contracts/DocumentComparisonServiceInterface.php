<?php

/**
 * Contract of DocumentComparisonService
 *
 * The public surface of the DocumentComparisonService service, so callers in other
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

use OCA\Filinq\Exception\ComparisonException;

/**
 * Contract of DocumentComparisonService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface DocumentComparisonServiceInterface {
	/**
	 * Compare two document subjects.
	 *
	 * @param array{fileId:int, versionTimestamp?:int} $left Left subject.
	 * @param array{fileId:int, versionTimestamp?:int} $right Right subject.
	 *
	 * @return array<string, mixed> The structured comparison response.
	 *
	 * @throws ComparisonException On any resolvable failure (404/413/415/422).
	 *
	 * @spec openspec/specs/document-comparison/spec.md
	 */
	public function compare(array $left, array $right): array;
}//end interface
