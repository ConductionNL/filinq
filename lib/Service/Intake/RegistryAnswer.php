<?php

/**
 * Registry Answer
 *
 * Reads the name and address out of what a base registry answered: a row of
 * the Handelsregister search, or a person of the BRP. Only those two fields
 * leave this class, so a suggestion never carries the full registry record.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Intake
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Intake;

/**
 * Name and address of a registry answer.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Intake
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md
 */
final class RegistryAnswer {

	/**
	 * The company name and visiting address of a Handelsregister row.
	 *
	 * The KvK search answers `naam` and an `adres.binnenlandsAdres` block; an
	 * older answer carries the street and place on the row itself.
	 *
	 * @param array<string, mixed> $row One row of the KvK answer.
	 *
	 * @return array{name: string, address: string} The fields; empty strings where the row has none.
	 *
	 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-kvk-number-on-an-arrival-becomes-a-registry-suggestion-req-ibr-001
	 */
	public static function company(array $row): array {
		$name = self::text(value: ($row['naam'] ?? ($row['handelsnaam'] ?? '')));

		$address = [];
		if (is_array($row['adres'] ?? null) === true) {
			$address = $row['adres'];
			if (is_array($address['binnenlandsAdres'] ?? null) === true) {
				$address = $address['binnenlandsAdres'];
			}
		}

		if ($address === []) {
			$address = $row;
		}

		return [
			'name' => $name,
			'address' => self::addressLine(
				street: self::text(value: ($address['straatnaam'] ?? '')),
				number: self::text(value: ($address['huisnummer'] ?? '')) . self::text(value: ($address['huisletter'] ?? '')),
				postcode: self::text(value: ($address['postcode'] ?? '')),
				place: self::text(value: ($address['plaats'] ?? ''))
			),
		];

	}//end company()

	/**
	 * The name and residential address of a BRP person, nothing else.
	 *
	 * @param array<string, mixed> $person One person of the HaalCentraal answer.
	 *
	 * @return array{name: string, address: string} The fields; empty strings where the person has none.
	 *
	 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-the-brp-is-asked-only-under-a-declared-purpose-req-ibr-002
	 */
	public static function person(array $person): array {
		$naam = [];
		if (is_array($person['naam'] ?? null) === true) {
			$naam = $person['naam'];
		}

		$name = self::text(value: ($naam['volledigeNaam'] ?? ''));
		if ($name === '') {
			$name = self::joined(
				parts: [
					self::text(value: ($naam['voornamen'] ?? '')),
					self::text(value: ($naam['voorvoegsel'] ?? '')),
					self::text(value: ($naam['geslachtsnaam'] ?? '')),
				]
			);
		}

		$place = [];
		if (is_array($person['verblijfplaats'] ?? null) === true) {
			$place = $person['verblijfplaats'];
		}

		$address = $place;
		if (is_array($place['verblijfadres'] ?? null) === true) {
			$address = $place['verblijfadres'];
		}

		$line = self::addressLine(
			street: self::text(value: ($address['officieleStraatnaam'] ?? ($address['korteStraatnaam'] ?? ''))),
			number: self::text(value: ($address['huisnummer'] ?? '')) . self::text(value: ($address['huisletter'] ?? '')),
			postcode: self::text(value: ($address['postcode'] ?? '')),
			place: self::text(value: ($address['woonplaats'] ?? ''))
		);
		if ($line === '' && is_array($place['adressering'] ?? null) === true) {
			$line = self::joined(
				parts: [
					self::text(value: ($place['adressering']['adresregel1'] ?? '')),
					self::text(value: ($place['adressering']['adresregel2'] ?? '')),
				],
				glue: ', '
			);
		}

		return ['name' => $name, 'address' => $line];

	}//end person()

	/**
	 * One address line: street and number, then postcode and place.
	 *
	 * @param string $street   The street.
	 * @param string $number   The house number with its letter.
	 * @param string $postcode The postcode.
	 * @param string $place    The place.
	 *
	 * @return string The line, or an empty string when nothing is known.
	 */
	private static function addressLine(string $street, string $number, string $postcode, string $place): string {
		return self::joined(
			parts: [
				self::joined(parts: [$street, $number]),
				self::joined(parts: [$postcode, $place]),
			],
			glue: ', '
		);

	}//end addressLine()

	/**
	 * The non-empty parts joined.
	 *
	 * @param array<int, string> $parts The parts.
	 * @param string             $glue  What goes between them.
	 *
	 * @return string The joined text.
	 */
	private static function joined(array $parts, string $glue = ' '): string {
		return implode($glue, array_values(array_filter($parts, static fn (string $part): bool => $part !== '')));

	}//end joined()

	/**
	 * A scalar as trimmed text; anything else as an empty string.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string The text.
	 */
	private static function text(mixed $value): string {
		if (is_string($value) === false && is_int($value) === false) {
			return '';
		}

		return trim((string)$value);

	}//end text()
}//end class
