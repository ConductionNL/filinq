<?php

/**
 * Field value check
 *
 * Whether a value a model suggested fits the schema property it is for: its type, format and allowed values, checked with opis/json-schema.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\FieldFill
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\FieldFill;

use Opis\JsonSchema\Validator;
use Throwable;

/**
 * Checks one suggested value against one schema property.
 *
 * Only the keywords that describe a value are carried over (type, format,
 * enum, pattern, length, range, items). OpenRegister's own keywords such as
 * `visible` or `facetable` are not JSON Schema and are left out, so they can
 * not make a valid value fail.
 *
 * @category Service
 * @package  OCA\Filinq\Service\FieldFill
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 */
class FieldValueCheck {

	/**
	 * The JSON Schema keywords a property's value is checked against.
	 *
	 * @var array<int, string>
	 */
	private const VALUE_KEYWORDS = [
		'type',
		'format',
		'enum',
		'const',
		'pattern',
		'minLength',
		'maxLength',
		'minimum',
		'maximum',
		'exclusiveMinimum',
		'exclusiveMaximum',
		'multipleOf',
		'items',
		'minItems',
		'maxItems',
	];

	/**
	 * Whether a value fits a property.
	 *
	 * A `null` never fits: the model was told to leave out what it could not
	 * find, so a null is an answer of nothing, not a value.
	 *
	 * @param mixed                $value    The suggested value.
	 * @param array<string, mixed> $property The schema property.
	 *
	 * @return bool True when the value fits.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function fits(mixed $value, array $property): bool {
		if ($value === null) {
			return false;
		}

		try {
			$result = (new Validator())->validate(
				data: json_decode((string)json_encode($value)),
				schema: json_decode((string)json_encode($this->valueSchema(property: $property)))
			);
		} catch (Throwable $e) {
			return false;
		}

		return $result->isValid();

	}//end fits()

	/**
	 * The part of a property that describes its value, as a JSON Schema object.
	 *
	 * @param array<string, mixed> $property The schema property.
	 *
	 * @return array<string, mixed>|object The value schema; an empty object accepts anything.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function valueSchema(array $property): array|object {
		$schema = [];
		foreach (self::VALUE_KEYWORDS as $keyword) {
			if (array_key_exists($keyword, $property) === true) {
				$schema[$keyword] = $property[$keyword];
			}
		}

		if ($schema === []) {
			return (object)[];
		}

		return $schema;

	}//end valueSchema()
}//end class
