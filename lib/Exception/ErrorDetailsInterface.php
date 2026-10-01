<?php

/**
 * Error Details Interface
 *
 * An exception that carries more than a message for the JSON error body:
 * the conversion attempts of a failed PDF conversion, or the per-question
 * errors of a refused wizard run. A controller merges the details into its
 * error response without knowing every exception class that has some.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

/**
 * Extra fields for the JSON error body of an exception.
 */
interface ErrorDetailsInterface {

	/**
	 * The fields to add to the error body, next to `error`.
	 *
	 * @return array<string, mixed> The fields; empty when there is nothing to add.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-4
	 */
	public function getErrorDetails(): array;
}//end interface
