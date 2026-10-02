<?php

/**
 * Data path
 *
 * Reads a dotted path (`aanvrager.naam`) from the data context a template
 * is filled with, the same nesting the Twig path sees.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

/**
 * Dotted-path reads from template data.
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-3
 */
class DataPath {

	/**
	 * The raw value at a path, or null.
	 *
	 * @param array<string, mixed> $data The data.
	 * @param string               $path The dotted path.
	 *
	 * @return mixed The value.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-3
	 */
	public function value(array $data, string $path): mixed {
		if (array_key_exists($path, $data) === true) {
			return $data[$path];
		}

		$node = $data;
		foreach (explode('.', $path) as $segment) {
			if (is_object($node) === true) {
				$node = (array) $node;
			}

			if (is_array($node) === false || array_key_exists($segment, $node) === false) {
				return null;
			}

			$node = $node[$segment];
		}

		return $node;

	}//end value()

	/**
	 * The value at a path as text, or null when there is none. A list of
	 * scalars is joined with commas; true and false read as ja and nee.
	 *
	 * @param array<string, mixed> $data The data.
	 * @param string               $path The dotted path.
	 *
	 * @return string|null The text.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-3
	 */
	public function scalar(array $data, string $path): ?string {
		$value = $this->value(data: $data, path: $path);
		if (is_bool($value) === true) {
			return ['nee', 'ja'][(int) $value];
		}

		if (is_scalar($value) === true) {
			return (string) $value;
		}

		if (is_array($value) === true && $value !== [] && array_is_list($value) === true) {
			$scalars = array_filter($value, 'is_scalar');
			if (count($scalars) === count($value)) {
				return implode(', ', array_map('strval', $value));
			}
		}

		return null;

	}//end scalar()

	/**
	 * The rows at a path, for a repeating block: a list of arrays, or null.
	 *
	 * @param array<string, mixed> $data The data.
	 * @param string               $path The dotted path.
	 *
	 * @return array<int, array<string, mixed>>|null The rows.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-3
	 */
	public function rows(array $data, string $path): ?array {
		$value = $this->value(data: $data, path: $path);
		if (is_array($value) === false || array_is_list($value) === false) {
			return null;
		}

		return array_values(array_map(static fn (mixed $row): array => (array) json_decode((string) json_encode($row), true), $value));

	}//end rows()
}//end class
