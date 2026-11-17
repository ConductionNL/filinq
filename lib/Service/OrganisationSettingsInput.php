<?php

/**
 * Organisation Settings Input
 *
 * Turns a submitted organisation-settings form into the fields stored on the
 * `organisationSettings` object: only the schema's own fields, each checked
 * against the schema's range, an empty value stored as null ("inherit the
 * instance default").
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/admin-settings/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use InvalidArgumentException;

/**
 * Validates the fields of an organisation-settings write.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/admin-settings/spec.md#requirement-per-organisation-settings-are-stored-as-organisationsettings-objects-req-ddmth-007
 */
final class OrganisationSettingsInput {

	/**
	 * The fields an organisation may set, in the schema's order.
	 *
	 * @var array<int, string>
	 */
	public const FIELDS = ['name', 'consentPeriodDays', 'anonymizationProfile', 'defaultHuisstijl', 'notes'];

	/**
	 * Entity categories a profile may list.
	 *
	 * @var string
	 */
	private const CATEGORY_PATTERN = '/^[A-Z][A-Z_]{0,39}$/';

	/**
	 * The stored fields of a submitted form.
	 *
	 * Fields other than the schema's are dropped, so an organisation stamp,
	 * an owner or an id in the request never reaches the save.
	 *
	 * @param array<string, mixed> $input       The submitted fields.
	 * @param string               $defaultName The name used when none is given.
	 * @param array<string, int>   $maxLength   Longest `name` and `notes`.
	 *
	 * @return array<string, mixed> Every field of FIELDS.
	 *
	 * @throws InvalidArgumentException When a value is out of range.
	 *
	 * @spec openspec/changes/multi-tenant-hardening/specs/admin-settings/spec.md#requirement-per-organisation-settings-are-stored-as-organisationsettings-objects-req-ddmth-007
	 */
	public static function fields(array $input, string $defaultName, array $maxLength): array {
		$name = self::text(value: ($input['name'] ?? null), field: 'name', max: (int)($maxLength['name'] ?? 255));
		if ($name === null) {
			$name = trim($defaultName);
		}

		if ($name === '') {
			$name = 'Organisation';
		}

		return [
			'name'                 => $name,
			'consentPeriodDays'    => self::period(value: ($input['consentPeriodDays'] ?? null)),
			'anonymizationProfile' => self::profile(value: ($input['anonymizationProfile'] ?? null)),
			'defaultHuisstijl'     => self::reference(value: ($input['defaultHuisstijl'] ?? null)),
			'notes'                => self::text(value: ($input['notes'] ?? null), field: 'notes', max: (int)($maxLength['notes'] ?? 2000)),
		];
	}//end fields()

	/**
	 * The objection period: a whole number of days from 1 to 365, or null.
	 *
	 * @param mixed $value The submitted value.
	 *
	 * @return int|null The period.
	 *
	 * @throws InvalidArgumentException When it is not a whole number in range.
	 */
	private static function period(mixed $value): ?int {
		if (self::isBlank(value: $value) === true) {
			return null;
		}

		$days = self::wholeNumber(value: $value);
		if ($days < 1 || $days > 365) {
			throw new InvalidArgumentException('The objection period is between 1 and 365 days.');
		}

		return $days;
	}//end period()

	/**
	 * A submitted whole number: an int, or digits as text.
	 *
	 * @param mixed $value The submitted value.
	 *
	 * @return int The number.
	 *
	 * @throws InvalidArgumentException When it is not a whole number.
	 */
	private static function wholeNumber(mixed $value): int {
		if (is_int($value) === false && self::matches(value: $value, pattern: '/^\d+$/') === false) {
			throw new InvalidArgumentException('The objection period is a whole number of days.');
		}

		return (int)$value;
	}//end wholeNumber()

	/**
	 * The anonymisation profile: two lists of entity categories, or null.
	 *
	 * @param mixed $value The submitted value.
	 *
	 * @return array{anonymize: array<int, string>, keepVisible: array<int, string>}|null The profile.
	 *
	 * @throws InvalidArgumentException When it is not two lists of categories, or a category is in both.
	 */
	private static function profile(mixed $value): ?array {
		if (self::isBlank(value: $value) === true || $value === []) {
			return null;
		}

		if (is_array($value) === false) {
			throw new InvalidArgumentException('The anonymisation profile lists the categories to anonymise and to keep visible.');
		}

		return self::profileOf(
			anonymize: self::listed(value: $value, key: 'anonymize'),
			keepVisible: self::listed(value: $value, key: 'keepVisible')
		);
	}//end profile()

	/**
	 * The profile of two category lists, or null when both are empty.
	 *
	 * @param array<int, string> $anonymize   The categories to anonymise.
	 * @param array<int, string> $keepVisible The categories to keep visible.
	 *
	 * @return array{anonymize: array<int, string>, keepVisible: array<int, string>}|null The profile.
	 *
	 * @throws InvalidArgumentException When a category is in both.
	 */
	private static function profileOf(array $anonymize, array $keepVisible): ?array {
		if ($anonymize === [] && $keepVisible === []) {
			return null;
		}

		if (array_intersect($anonymize, $keepVisible) !== []) {
			throw new InvalidArgumentException('A category cannot be both anonymised and kept visible.');
		}

		return ['anonymize' => $anonymize, 'keepVisible' => $keepVisible];
	}//end profileOf()

	/**
	 * The categories listed under one key of a submitted profile.
	 *
	 * @param array<mixed> $value The submitted profile.
	 * @param string       $key   The list's key.
	 *
	 * @return array<int, string> The categories; none when the key is absent.
	 *
	 * @throws InvalidArgumentException When it is not a list of categories.
	 */
	private static function listed(array $value, string $key): array {
		return self::categories(value: ($value[$key] ?? []));
	}//end listed()

	/**
	 * A list of entity categories, upper case, without duplicates.
	 *
	 * @param mixed $value The submitted list.
	 *
	 * @return array<int, string> The categories.
	 *
	 * @throws InvalidArgumentException When it is not a list of categories.
	 */
	private static function categories(mixed $value): array {
		if (is_array($value) === false || array_is_list($value) === false) {
			throw new InvalidArgumentException('The anonymisation profile lists the categories to anonymise and to keep visible.');
		}

		$categories = [];
		foreach ($value as $category) {
			$categories[self::category(value: $category)] = $category;
		}

		return array_values($categories);
	}//end categories()

	/**
	 * One entity category.
	 *
	 * @param mixed $value The submitted category.
	 *
	 * @return string The category.
	 *
	 * @throws InvalidArgumentException When it is not a category name.
	 */
	private static function category(mixed $value): string {
		if (is_string($value) === false || preg_match(self::CATEGORY_PATTERN, $value) !== 1) {
			throw new InvalidArgumentException('An entity category is a name such as PERSON or BSN.');
		}

		return $value;
	}//end category()

	/**
	 * A huisstijl reference: a UUID, or null.
	 *
	 * @param mixed $value The submitted value.
	 *
	 * @return string|null The reference.
	 *
	 * @throws InvalidArgumentException When it is not a UUID.
	 */
	private static function reference(mixed $value): ?string {
		if (self::isBlank(value: $value) === true) {
			return null;
		}

		if (self::matches(value: $value, pattern: '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i') === false) {
			throw new InvalidArgumentException('The default huisstijl is not a huisstijl reference.');
		}

		return strtolower(trim($value));
	}//end reference()

	/**
	 * A single text value, trimmed, or null when empty.
	 *
	 * @param mixed  $value The submitted value.
	 * @param string $field The field, for the message.
	 * @param int    $max   Longest accepted.
	 *
	 * @return string|null The text.
	 *
	 * @throws InvalidArgumentException When it is not text or too long.
	 */
	private static function text(mixed $value, string $field, int $max): ?string {
		if ($value === null) {
			return null;
		}

		if (is_string($value) === false) {
			throw new InvalidArgumentException('The ' . $field . ' is text.');
		}

		$value = trim($value);
		if ($value === '') {
			return null;
		}

		return self::limited(value: $value, field: $field, max: $max);
	}//end text()

	/**
	 * A text that is at most a number of characters long.
	 *
	 * @param string $value The trimmed text.
	 * @param string $field The field, for the message.
	 * @param int    $max   Longest accepted.
	 *
	 * @return string The text.
	 *
	 * @throws InvalidArgumentException When it is too long.
	 */
	private static function limited(string $value, string $field, int $max): string {
		if (mb_strlen($value) > $max) {
			throw new InvalidArgumentException('The ' . $field . ' is at most ' . $max . ' characters.');
		}

		return $value;
	}//end limited()

	/**
	 * Whether a submitted value is absent: null or the empty string.
	 *
	 * @param mixed $value The submitted value.
	 *
	 * @return bool True when blank.
	 */
	private static function isBlank(mixed $value): bool {
		return $value === null || $value === '';
	}//end isBlank()

	/**
	 * Whether a submitted value is text that, trimmed, matches a pattern.
	 *
	 * @param mixed  $value   The submitted value.
	 * @param string $pattern The pattern.
	 *
	 * @return bool True when it matches.
	 */
	private static function matches(mixed $value, string $pattern): bool {
		return is_string($value) === true && preg_match($pattern, trim($value)) === 1;
	}//end matches()
}//end class
