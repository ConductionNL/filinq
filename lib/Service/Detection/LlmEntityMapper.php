<?php

/**
 * LLM Entity Mapper
 *
 * Maps an LLM agentflow's entity list into OpenRegister's entity shape.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Detection
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Detection;

use UnexpectedValueException;

/**
 * Turns `state.entities[]` items (`value`, `type`, `start`, `end`, `score`)
 * into OpenRegister-shaped entities.
 *
 * The value is kept verbatim. A recognised type is mapped to the type name
 * Filinq's other detections use, and an unrecognised one is kept verbatim (in
 * upper case) rather than dropped. Positions are carried through when both are
 * valid and null otherwise, so a position-less occurrence is still stored. The
 * score is clamped to 0..1, with the configured default when absent. Anything
 * that is not a list of entity objects with a non-empty value is malformed and
 * refused as a whole, so the caller can fall back instead of storing half of it.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Detection
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */
class LlmEntityMapper {

	/**
	 * The detection method stamped on every mapped entity.
	 *
	 * @var string
	 */
	public const DETECTION_METHOD = 'llm';

	/**
	 * The category for a type with no known category.
	 *
	 * @var string
	 */
	public const DEFAULT_CATEGORY = 'contextual_data';

	/**
	 * Common LLM/NER type spellings to the type names Filinq's detections use.
	 *
	 * @var array<string, string>
	 */
	private const TYPE_ALIASES = [
		'PER'           => 'PERSON',
		'PERSON'        => 'PERSON',
		'NAME'          => 'PERSON',
		'LOC'           => 'LOCATION',
		'GPE'           => 'LOCATION',
		'LOCATION'      => 'LOCATION',
		'ORG'           => 'ORGANIZATION',
		'ORGANISATION'  => 'ORGANIZATION',
		'ORGANIZATION'  => 'ORGANIZATION',
		'EMAIL'         => 'EMAIL',
		'EMAIL_ADDRESS' => 'EMAIL',
		'PHONE'         => 'PHONE',
		'PHONE_NUMBER'  => 'PHONE',
		'DATE'          => 'DATE',
		'DATE_TIME'     => 'DATE',
		'BSN'           => 'BSN',
		'IBAN'          => 'IBAN',
		'IBAN_CODE'     => 'IBAN',
		'KENTEKEN'      => 'KENTEKEN',
		'ADDRESS'       => 'STREET_ADDRESS',
		'STREET_ADDRESS' => 'STREET_ADDRESS',
	];

	/**
	 * The category per mapped type.
	 *
	 * @var array<string, string>
	 */
	private const CATEGORIES = [
		'PERSON'         => 'personal_data',
		'EMAIL'          => 'personal_data',
		'PHONE'          => 'personal_data',
		'STREET_ADDRESS' => 'personal_data',
		'BSN'            => 'personal_data',
		'IBAN'           => 'personal_data',
		'KENTEKEN'       => 'personal_data',
		'DATE'           => 'personal_data',
		'LOCATION'       => 'contextual_data',
		'ORGANIZATION'   => 'business_data',
	];

	/**
	 * Map an agentflow's entity list.
	 *
	 * @param mixed $items             The `state.entities` value as decoded.
	 * @param float $defaultConfidence The confidence for an item without a numeric score.
	 *
	 * @return list<array<string, mixed>> Each with value, type, category, positionStart,
	 *                                    positionEnd (int|null), confidence and detectionMethod.
	 *
	 * @throws UnexpectedValueException When the list or one of its items is malformed.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.2
	 */
	public function map(mixed $items, float $defaultConfidence): array {
		if (is_array($items) === false || array_is_list($items) === false) {
			throw new UnexpectedValueException('The agentflow did not return an entity list.');
		}

		$mapped = [];
		foreach ($items as $index => $item) {
			$mapped[] = $this->mapOne(item: $item, index: $index, defaultConfidence: $defaultConfidence);
		}

		return $mapped;
	}//end map()

	/**
	 * The type name an LLM type maps to: a known alias, else the type verbatim in upper case.
	 *
	 * @param string $type The LLM's type.
	 *
	 * @return string The mapped type.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.2
	 */
	public function mapType(string $type): string {
		$key = strtoupper(trim($type));
		return (self::TYPE_ALIASES[$key] ?? $key);
	}//end mapType()

	/**
	 * Map one item.
	 *
	 * @param mixed $item              The item.
	 * @param int   $index             Its index, for the refusal message.
	 * @param float $defaultConfidence The confidence when it has no score.
	 *
	 * @return array<string, mixed> The mapped entity.
	 *
	 * @throws UnexpectedValueException When the item is malformed.
	 */
	private function mapOne(mixed $item, int $index, float $defaultConfidence): array {
		if (is_array($item) === false) {
			throw new UnexpectedValueException(sprintf('Entity %d is not an object.', $index));
		}

		$value = self::requiredText(value: ($item['value'] ?? null), refusal: sprintf('Entity %d has no value.', $index));
		$type  = self::requiredText(value: ($item['type'] ?? null), refusal: sprintf('Entity %d has no type.', $index));

		$mappedType = $this->mapType(type: $type);
		[$start, $end] = $this->positions(start: ($item['start'] ?? null), end: ($item['end'] ?? null));

		return [
			'value'           => $value,
			'type'            => $mappedType,
			'category'        => (self::CATEGORIES[$mappedType] ?? self::DEFAULT_CATEGORY),
			'positionStart'   => $start,
			'positionEnd'     => $end,
			'confidence'      => $this->confidence(score: ($item['score'] ?? null), defaultConfidence: $defaultConfidence),
			'detectionMethod' => self::DETECTION_METHOD,
		];
	}//end mapOne()

	/**
	 * A non-blank string field of an item.
	 *
	 * @param mixed  $value   The field.
	 * @param string $refusal The message when it is missing or blank.
	 *
	 * @return string The value as given.
	 *
	 * @throws UnexpectedValueException When it is not a non-blank string.
	 */
	private static function requiredText(mixed $value, string $refusal): string {
		if (is_string($value) === false || trim($value) === '') {
			throw new UnexpectedValueException($refusal);
		}

		return $value;
	}//end requiredText()

	/**
	 * Both positions when they form a valid range, else both null.
	 *
	 * @param mixed $start The start offset.
	 * @param mixed $end   The end offset.
	 *
	 * @return array{0: int|null, 1: int|null} The range.
	 */
	private function positions(mixed $start, mixed $end): array {
		if (is_int($start) === false || is_int($end) === false || $start < 0 || $end <= $start) {
			return [null, null];
		}

		return [$start, $end];
	}//end positions()

	/**
	 * A score clamped to 0..1, or the default when it is not numeric.
	 *
	 * @param mixed $score             The score.
	 * @param float $defaultConfidence The default.
	 *
	 * @return float The confidence.
	 */
	private function confidence(mixed $score, float $defaultConfidence): float {
		if (is_int($score) === false && is_float($score) === false) {
			return $defaultConfidence;
		}

		return max(0.0, min(1.0, (float) $score));
	}//end confidence()
}//end class
