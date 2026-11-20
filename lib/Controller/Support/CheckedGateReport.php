<?php

/**
 * Puts the advisory checked-gate verdict on a commit's answer.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.4
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller\Support;

use OCP\AppFramework\Http\JSONResponse;

/**
 * Adds `checkedGate` to a successful commit answer when the gate is advisory.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#requirement-checked-gate-blocks-anonymize-commit-and-export-req-ddarw-008
 */
class CheckedGateReport {

	/**
	 * The answer of a single-file commit, with the report when there is one.
	 *
	 * @param JSONResponse              $response The commit's answer.
	 * @param array<string, mixed>|null $gate     The `commitGate()` result, or null when no gate ran.
	 *
	 * @return JSONResponse The answer.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.4
	 */
	public function onto(JSONResponse $response, ?array $gate): JSONResponse {
		$data = $response->getData();
		if (($gate['report'] ?? null) === null || is_array($data) === false || $response->getStatus() >= 300) {
			return $response;
		}

		return $response->setData($this->ontoArray(result: $data, gate: $gate));

	}//end onto()

	/**
	 * A commit result, with the report under `checkedGate` when there is one.
	 *
	 * @param array<string, mixed>      $result The commit result.
	 * @param array<string, mixed>|null $gate   The `commitGate()` result, or null when no gate ran.
	 *
	 * @return array<string, mixed> The result.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.4
	 */
	public function ontoArray(array $result, ?array $gate): array {
		if (($gate['report'] ?? null) !== null) {
			$result['checkedGate'] = $gate['report'];
		}

		return $result;

	}//end ontoArray()
}//end class
