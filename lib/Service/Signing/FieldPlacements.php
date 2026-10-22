<?php

/**
 * Field Placements
 *
 * The rules a signing request's `fieldPlacements` follow: which types exist,
 * how coordinates are bounded, and which signer a placement may name. Pure
 * logic, shared by request creation and by the renderer.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Signing;

use InvalidArgumentException;

/**
 * Validates and normalises field placements.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.1
 */
class FieldPlacements {

	/**
	 * The five static field types. Conditional fields are future scope.
	 *
	 * @var list<string>
	 */
	public const TYPES = ['signature', 'initials', 'date', 'text', 'checkbox'];

	/**
	 * Rounding slack for a box that ends exactly at the page edge (0.7 + 0.3 is not 1.0 in floating point).
	 *
	 * @var float
	 */
	private const EPSILON = 1e-9;

	/**
	 * Most placements one request may carry.
	 *
	 * @var int
	 */
	public const MAX = 200;

	/**
	 * Validate the placements a caller sent and return them normalised.
	 *
	 * A placement names a signer by position in the request's signer list, a
	 * page (1-based) and a box in page-relative coordinates between 0 and 1,
	 * origin top left. A box that leaves the page, a signer that does not
	 * exist, an unknown type or any key outside the shape is refused, so
	 * nothing half-understood is ever drawn into a signed document.
	 *
	 * @param mixed $placements  The `fieldPlacements` value from the request body.
	 * @param int   $signerCount How many signers the request names.
	 *
	 * @return list<array{signerIndex: int, page: int, x: float, y: float, width: float, height: float, type: string}> The placements.
	 *
	 * @throws InvalidArgumentException When a placement breaks a rule; the message names which.
	 *
	 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.1
	 */
	public function normalise(mixed $placements, int $signerCount): array {
		if ($placements === null || $placements === []) {
			return [];
		}

		if (is_array($placements) === false || array_is_list($placements) === false) {
			throw new InvalidArgumentException('fieldPlacements must be a list');
		}

		if (count($placements) > self::MAX) {
			throw new InvalidArgumentException('fieldPlacements holds more than ' . self::MAX . ' fields');
		}

		$result = [];
		foreach ($placements as $position => $placement) {
			$result[] = $this->normaliseOne(placement: $placement, signerCount: $signerCount, position: (int) $position);
		}

		return $result;

	}//end normalise()

	/**
	 * Validate one placement.
	 *
	 * @param mixed $placement   One entry.
	 * @param int   $signerCount How many signers the request names.
	 * @param int   $position    The entry's position, for the message.
	 *
	 * @return array{signerIndex: int, page: int, x: float, y: float, width: float, height: float, type: string} The placement.
	 *
	 * @throws InvalidArgumentException When the entry breaks a rule.
	 */
	private function normaliseOne(mixed $placement, int $signerCount, int $position): array {
		$where = 'fieldPlacements[' . $position . ']';
		$keys  = ['signerIndex', 'page', 'x', 'y', 'width', 'height', 'type'];
		if (is_array($placement) === false || array_diff(array_keys($placement), $keys) !== []) {
			throw new InvalidArgumentException($where . ' must hold only ' . implode(', ', $keys));
		}

		$type = ($placement['type'] ?? null);
		if (in_array($type, self::TYPES, true) === false) {
			throw new InvalidArgumentException($where . '.type must be one of ' . implode(', ', self::TYPES));
		}

		$signerIndex = $this->integer(value: ($placement['signerIndex'] ?? null), minimum: 0, where: $where . '.signerIndex');
		if ($signerIndex >= $signerCount) {
			throw new InvalidArgumentException($where . '.signerIndex names signer ' . $signerIndex . ' of ' . $signerCount);
		}

		$box = $this->box(placement: $placement, where: $where);

		return [
			'signerIndex' => $signerIndex,
			'page'        => $this->integer(value: ($placement['page'] ?? null), minimum: 1, where: $where . '.page'),
			'x'           => $box['x'],
			'y'           => $box['y'],
			'width'       => $box['width'],
			'height'      => $box['height'],
			'type'        => $type,
		];

	}//end normaliseOne()

	/**
	 * Read a placement's box: four shares of the page that keep it on the page.
	 *
	 * @param array  $placement The entry.
	 * @param string $where     The entry's name, for the message.
	 *
	 * @return array{x: float, y: float, width: float, height: float} The box.
	 *
	 * @throws InvalidArgumentException When a coordinate is not a number from 0 to 1, or the box leaves the page.
	 */
	private function box(array $placement, string $where): array {
		$box = [];
		foreach (['x', 'y', 'width', 'height'] as $key) {
			$value = ($placement[$key] ?? null);
			if ((is_int($value) === false && is_float($value) === false) || $value < 0 || $value > 1) {
				throw new InvalidArgumentException($where . '.' . $key . ' must be a number from 0 to 1');
			}

			$box[$key] = (float) $value;
		}

		$sized  = $box['width'] > 0 && $box['height'] > 0;
		$onPage = max($box['x'] + $box['width'], $box['y'] + $box['height']) <= 1 + self::EPSILON;
		if ($sized === false || $onPage === false) {
			throw new InvalidArgumentException($where . ' must be a box of some size that stays on the page');
		}

		return $box;

	}//end box()

	/**
	 * Read a whole number no lower than a minimum.
	 *
	 * @param mixed  $value   The value.
	 * @param int    $minimum The lowest allowed.
	 * @param string $where   The key, for the message.
	 *
	 * @return int The number.
	 *
	 * @throws InvalidArgumentException When the value is not such a number.
	 */
	private function integer(mixed $value, int $minimum, string $where): int {
		if (is_int($value) === false || $value < $minimum) {
			throw new InvalidArgumentException($where . ' must be a whole number from ' . $minimum);
		}

		return $value;

	}//end integer()
}//end class
