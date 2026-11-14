<?php

/**
 * Contract of DataResolverService
 *
 * The public surface of DataResolverService that other components call, so they depend
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

use Exception;

/**
 * Contract of DataResolverService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction b.v. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://github.com/ConductionNL/filinq
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface DataResolverServiceInterface {

	/**
	 * Resolve data from OpenRegister objects
	 *
	 * Fetches objects from OpenRegister by register, schema, and UUID.
	 * Returns resolved data keyed by schema name. listRefs are resolved
	 * next, each producing an array of objects under its 'as' key (or
	 * schema name + '_list' by default) — see {@see ListReferenceResolver::resolve()}.
	 * Ad-hoc data is merged on top of both, overriding resolved values when
	 * keys conflict. Precedence is therefore: dataRefs < listRefs < adHocData.
	 *
	 * @param array $dataRefs Array of object references, each with
	 *                        'register', 'schema', and 'id' keys
	 * @param array $listRefs Array of collection references, each with
	 *                        'register', 'schema' and optional 'filter',
	 *                        'limit', 'order', 'as' keys
	 * @param array $adHocData Ad-hoc data to merge on top of resolved data
	 *
	 * @return array{data: array, errors: array, warnings: array} Resolved data and any errors
	 *
	 * @throws Exception If listRefs violate a request-level guardrail (too
	 *                   many entries, invalid filter values, invalid or
	 *                   colliding 'as' key) — these are malformed-request
	 *                   errors (HTTP 400) rather than per-item resolution
	 *                   failures
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-correspondence-generation-api
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function resolve(array $dataRefs, array $listRefs = [], array $adHocData = []): array;
}//end interface
