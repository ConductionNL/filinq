<?php

/**
 * Contract of GenerationSourceObject
 *
 * The public surface of the GenerationSourceObject service, so callers in other
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

use Exception;

/**
 * Contract of GenerationSourceObject.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface GenerationSourceObjectInterface {
	/**
	 * Take `sourceObject` out of the options and work out what it changes.
	 *
	 * @param array<int, mixed>    $dataRefs The request's data references.
	 * @param array<string, mixed> $options  The request's options.
	 * @param string               $userId   The caller.
	 *
	 * @return array{options: array<string, mixed>, recordFields: array<string, mixed>}
	 *     The options without `sourceObject` (with the host folder as target when it applies),
	 *     and the fields for the generated document's entry.
	 *
	 * @throws Exception Code 400 when the source object is not one of the data references.
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.3
	 */
	public function apply(array $dataRefs, array $options, string $userId): array;
}//end interface
