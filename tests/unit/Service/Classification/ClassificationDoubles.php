<?php

/**
 * Test doubles for inbound classification
 *
 * An in-memory OpenRegister ObjectService that holds classificationResult
 * and dossier rows and validates every classificationResult write against
 * the real fragment in lib/Settings/filinq_register.json with Opis (the
 * shape OpenRegister's hard validation would refuse is refused here too).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Classification
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#4-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Classification;

use OCA\Filinq\Tests\Unit\Service\Wizard\WizardObjectStore;
use OCA\OpenRegister\Service\ObjectService;
use RuntimeException;

/**
 * In-memory classificationResult and dossier rows.
 */
class ClassificationObjectStore extends ObjectService {

	/**
	 * Rows per schema, by uuid.
	 *
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	public array $rows = ['classificationResult' => [], 'dossier' => []];

	/**
	 * Every classificationResult write, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public array $saves = [];

	/**
	 * Save a row; classificationResult rows are validated against the real fragment.
	 *
	 * @param array       $object        The fields.
	 * @param string      $register      The register slug.
	 * @param string      $schema        The schema slug.
	 * @param string|null $uuid          The uuid to update.
	 * @param bool        $_rbac         Ignored.
	 * @param bool        $_multitenancy Ignored.
	 *
	 * @return array The stored row.
	 */
	public function saveObject(
		array $object = [],
		string $register = '',
		string $schema = '',
		?string $uuid = null,
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		if ($register !== 'filinq' || $schema !== 'classificationResult') {
			throw new RuntimeException('unexpected target ' . $register . '/' . $schema);
		}

		WizardObjectStore::assertValid(schema: 'classificationResult', payload: $object);
		$this->saves[] = $object;
		$uuid ??= 'classification-' . (count($this->rows[$schema]) + 1);
		$this->rows[$schema][$uuid] = $object;

		return $object + ['@self' => ['id' => $uuid]];

	}//end saveObject()

	/**
	 * Rows of one schema matching every filter exactly.
	 *
	 * @param string $registerSlug  The register slug.
	 * @param string $schemaSlug    The schema slug.
	 * @param array  $filters       Field filters.
	 * @param bool   $_rbac         Ignored.
	 * @param bool   $_multitenancy Ignored.
	 *
	 * @return array The rows.
	 */
	public function searchObjectsBySlug(
		string $registerSlug,
		string $schemaSlug,
		array $filters = [],
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		$hits = [];
		foreach ($this->rows[$schemaSlug] ?? [] as $id => $row) {
			foreach ($filters as $key => $value) {
				if (($row[$key] ?? null) !== $value) {
					continue 2;
				}
			}

			$hits[] = $row + ['@self' => ['id' => (string) $id]];
		}

		return $hits;

	}//end searchObjectsBySlug()
}//end class
