<?php

/**
 * Field Value Checker
 *
 * Checks what a signer typed into the text fields placed for them, through
 * OpenRegister's formats.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Signing;

use Closure;
use OCA\Filinq\Exception\SigningFieldsRefusedException;
use RuntimeException;

/**
 * Maps a field's declared check onto an OpenRegister format and runs it.
 *
 * Filinq carries no checksum of its own (ADR-011): `iban` and `iban-nl` both
 * use OpenRegister's `iban` format, found by name and duck-typed on
 * `validate(mixed): bool`. `iban-nl` adds only the country the number must be
 * from. When OpenRegister has no such format, a request that uses the check
 * is refused at creation, so a signer never meets that refusal.
 *
 * No message carries the value that was typed (D8).
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.1
 */
class FieldValueChecker {

	/**
	 * Each declared check: the OpenRegister format it runs, the country it
	 * demands (or null) and the message for a value that fails it.
	 *
	 * @var array<string, array{format: string, country: ?string, message: string}>
	 */
	public const CHECKS = [
		'iban-nl' => [
			'format'  => 'iban',
			'country' => 'NL',
			'message' => 'Type a valid Dutch IBAN, for example NL91 ABNA 0417 1643 00',
		],
		'iban'    => [
			'format'  => 'iban',
			'country' => null,
			'message' => 'Type a valid IBAN, for example NL91 ABNA 0417 1643 00',
		],
	];

	/**
	 * The refusal when OpenRegister lacks the format a check needs.
	 */
	public const UNAVAILABLE = 'This check is not available on this server';

	/**
	 * The refusal for an empty required field.
	 */
	public const REQUIRED = 'Fill in this field';

	/**
	 * The refusal for a value longer than a field holds.
	 */
	public const TOO_LONG = 'This value is too long';

	/**
	 * Why a sign act without fields is refused for a signer who has some: a
	 * folder selection or bulk sign leaves the document out (D7).
	 */
	public const FIELDS_TO_FILL_IN = 'This document has fields to fill in. Open it to sign.';

	/**
	 * The longest value a field holds, in characters.
	 */
	public const MAX_LENGTH = 500;

	/**
	 * Finds an OpenRegister format by name: `fn(string $name): ?object`.
	 *
	 * @var Closure(string): ?object
	 */
	private readonly Closure $formatLookup;

	/**
	 * Constructor
	 *
	 * @param Closure|null $formatLookup Finds a format by name; null looks for
	 *                                   `OCA\OpenRegister\Formats\<Name>Format`.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.1
	 */
	public function __construct(?Closure $formatLookup = null) {
		$this->formatLookup = ($formatLookup ?? static fn (string $name): ?object => self::openRegisterFormat(name: $name));

	}//end __construct()

	/**
	 * OpenRegister's format of this name, or null when it has none.
	 *
	 * @param string $name The format name, such as `iban`.
	 *
	 * @return object|null The format, or null.
	 *
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.1
	 */
	public static function openRegisterFormat(string $name): ?object {
		$class = 'OCA\\OpenRegister\\Formats\\' . str_replace('-', '', ucwords($name, '-')) . 'Format';
		if (class_exists($class) === false) {
			return null;
		}

		return new $class();

	}//end openRegisterFormat()

	/**
	 * Refuse placements whose check this server cannot run.
	 *
	 * @param array<int, array<string, mixed>> $placements The normalised placements.
	 *
	 * @return void
	 *
	 * @throws SigningFieldsRefusedException With code 400, per placement whose check is unavailable.
	 *
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.1
	 */
	public function assertAvailable(array $placements): void {
		$errors = [];
		foreach ($placements as $position => $placement) {
			$check = ($placement['validation'] ?? null);
			if (is_string($check) === true && $this->format(check: $check) === null) {
				$errors[(int) $position] = self::UNAVAILABLE;
			}
		}

		if ($errors !== []) {
			throw new SigningFieldsRefusedException(fieldErrors: $errors, code: 400);
		}

	}//end assertAvailable()

	/**
	 * Check a sign act's values and answer what goes onto the signer record.
	 *
	 * A signer without text fields gets nothing. A signer with fields whose
	 * act carries none (a folder selection or bulk sign) is refused, so such a
	 * document is signed one at a time (D7). Otherwise every value is checked
	 * before anything is written, and the normalised values are stored in the
	 * same save as SIGNED (REQ-SFV-003, REQ-SFV-004).
	 *
	 * @param array<string, mixed>         $request  The signing request.
	 * @param string                       $signerId The signer record ID.
	 * @param array<array-key, mixed>|null $fields   What the signer typed, by placement position; null for none.
	 *
	 * @return array<string, mixed> `['fieldValues' => ...]`, or nothing when the signer has no fields.
	 *
	 * @throws SigningFieldsRefusedException With code 422 and a message per refused field.
	 * @throws RuntimeException With code 422 when the signer has fields and the act came without any.
	 *
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.2
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-3.2
	 */
	public function valuesForAct(array $request, string $signerId, ?array $fields): array {
		$placements = $request['fieldPlacements'] ?? [];
		$position   = array_search($signerId, array_map('strval', array_values((array) ($request['signerIds'] ?? []))), true);
		if (is_array($placements) === false || $position === false
			|| $this->hasFieldsFor(placements: $placements, signerIndex: (int) $position) === false
		) {
			return [];
		}

		if ($fields === null) {
			throw new RuntimeException(message: self::FIELDS_TO_FILL_IN, code: 422);
		}

		return ['fieldValues' => $this->check(placements: $placements, signerIndex: (int) $position, fields: $fields)];

	}//end valuesForAct()

	/**
	 * Whether any text field is placed for this signer.
	 *
	 * @param array<int, array<string, mixed>> $placements  The request's placements.
	 * @param int                              $signerIndex The signer's position in `signerIds`.
	 *
	 * @return bool True when the signer has something to fill in.
	 *
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.2
	 */
	public function hasFieldsFor(array $placements, int $signerIndex): bool {
		return $this->fieldsFor(placements: $placements, signerIndex: $signerIndex) !== [];

	}//end hasFieldsFor()

	/**
	 * Check every text field placed for this signer and return the values to store.
	 *
	 * `$fields` maps a placement position to what was typed. Positions that
	 * are not a text field of this signer are ignored.
	 *
	 * @param array<int, array<string, mixed>> $placements  The request's placements.
	 * @param int                              $signerIndex The signer's position in `signerIds`.
	 * @param array<array-key, mixed>          $fields      What the signer typed, by placement position.
	 *
	 * @return list<array{placement: int, label: string, value: string, validation?: string}> The normalised values.
	 *
	 * @throws SigningFieldsRefusedException With code 422 and a message per refused field.
	 *
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.2
	 */
	public function check(array $placements, int $signerIndex, array $fields): array {
		$values = [];
		$errors = [];
		foreach ($this->fieldsFor(placements: $placements, signerIndex: $signerIndex) as $position => $placement) {
			$check = ($placement['validation'] ?? null);
			$value = $this->normalise(value: ($fields[$position] ?? ($fields[(string) $position] ?? null)), check: $check);
			$error = $this->refusal(value: $value, placement: $placement);
			if ($error !== null) {
				$errors[$position] = $error;
				continue;
			}

			if ($value === '') {
				continue;
			}

			$entry = [
				'placement' => $position,
				'label'     => (string) ($placement['label'] ?? ''),
				'value'     => $value,
			];
			if ($check !== null) {
				$entry['validation'] = $check;
			}

			$values[] = $entry;
		}//end foreach

		if ($errors !== []) {
			throw new SigningFieldsRefusedException(fieldErrors: $errors, code: 422);
		}

		return $values;

	}//end check()

	/**
	 * The text placements of one signer, keyed by placement position.
	 *
	 * @param array<int, array<string, mixed>> $placements  The request's placements.
	 * @param int                              $signerIndex The signer's position.
	 *
	 * @return array<int, array<string, mixed>> The signer's text fields.
	 *
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.2
	 */
	private function fieldsFor(array $placements, int $signerIndex): array {
		$fields = [];
		foreach ($placements as $position => $placement) {
			if (is_array($placement) === true
				&& ($placement['type'] ?? null) === 'text'
				&& (int) ($placement['signerIndex'] ?? -1) === $signerIndex
			) {
				$fields[(int) $position] = $placement;
			}
		}

		return $fields;

	}//end fieldsFor()

	/**
	 * The value as stored: trimmed, and an IBAN in capitals without spaces.
	 *
	 * @param mixed       $value What was typed.
	 * @param string|null $check The field's check.
	 *
	 * @return string The normalised value; '' for nothing or a non-text value.
	 *
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.2
	 */
	private function normalise(mixed $value, ?string $check): string {
		if (is_string($value) === false) {
			return '';
		}

		$value = trim($value);
		if ($check !== null && isset(self::CHECKS[$check]) === true) {
			$value = strtoupper((string) preg_replace('/\s+/u', '', $value));
		}

		return $value;

	}//end normalise()

	/**
	 * Why a value cannot be accepted for its field, or null when it can.
	 *
	 * @param string               $value     The normalised value.
	 * @param array<string, mixed> $placement The field.
	 *
	 * @return string|null The refusal, or null.
	 *
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.2
	 */
	private function refusal(string $value, array $placement): ?string {
		if ($value === '') {
			if (($placement['required'] ?? false) === true) {
				return self::REQUIRED;
			}

			return null;
		}

		if (mb_strlen($value) > self::MAX_LENGTH) {
			return self::TOO_LONG;
		}

		$check = ($placement['validation'] ?? null);
		if (is_string($check) === false) {
			return null;
		}

		$format = $this->format(check: $check);
		if ($format === null) {
			return self::UNAVAILABLE;
		}

		$country = (self::CHECKS[$check]['country'] ?? null);
		$passes  = ($format->validate($value) === true);
		if ($passes === false || ($country !== null && str_starts_with($value, $country) === false)) {
			return self::CHECKS[$check]['message'];
		}

		return null;

	}//end refusal()

	/**
	 * The OpenRegister format behind a declared check, or null when unavailable.
	 *
	 * @param string $check The declared check, such as `iban-nl`.
	 *
	 * @return object|null A format with a `validate()` method, or null.
	 *
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.1
	 */
	private function format(string $check): ?object {
		$name = (self::CHECKS[$check]['format'] ?? null);
		if ($name === null) {
			return null;
		}

		$format = ($this->formatLookup)($name);
		if (is_object($format) === false || method_exists($format, 'validate') === false) {
			return null;
		}

		return $format;

	}//end format()
}//end class
