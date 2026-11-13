<?php

/**
 * Unit tests for FieldValueChecker.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Exception\SigningFieldsRefusedException;
use OCA\Filinq\Service\Signing\FieldValueChecker;
use PHPUnit\Framework\TestCase;

/**
 * Checking what a signer typed, through a stand-in for OpenRegister's `iban` format.
 *
 * The stand-in has the shape OpenRegister's formats have (`validate(mixed): bool`,
 * as `BsnFormat`) and runs the ISO 13616 mod-97 check, so a valid and an
 * invalid number can be told apart. filinq itself carries no checksum.
 *
 * @covers \OCA\Filinq\Service\Signing\FieldValueChecker
 * @uses   \OCA\Filinq\Exception\SigningFieldsRefusedException
 */
class FieldValueCheckerTest extends TestCase {

	/**
	 * The published example Dutch IBAN.
	 */
	private const DUTCH = 'NL91ABNA0417164300';

	/**
	 * A valid German IBAN.
	 */
	private const GERMAN = 'DE89370400440532013000';

	/**
	 * A required Dutch-IBAN field for signer 0, a free-text field for signer 0,
	 * a signature for signer 0 and a field for signer 1.
	 *
	 * @var list<array<string, mixed>>
	 */
	private const PLACEMENTS = [
		['signerIndex' => 0, 'page' => 1, 'type' => 'text', 'label' => 'Account for the payment', 'required' => true, 'validation' => 'iban-nl'],
		['signerIndex' => 0, 'page' => 1, 'type' => 'text', 'label' => 'Reference'],
		['signerIndex' => 0, 'page' => 1, 'type' => 'signature'],
		['signerIndex' => 1, 'page' => 1, 'type' => 'text', 'required' => true, 'validation' => 'iban'],
	];

	/**
	 * The names the checker asked the lookup for.
	 *
	 * @var list<string>
	 */
	private array $asked = [];

	/**
	 * A checker whose lookup knows an `iban` format.
	 *
	 * @return FieldValueChecker The checker.
	 */
	private function checker(): FieldValueChecker {
		return new FieldValueChecker(
			formatLookup: function (string $name): ?object {
				$this->asked[] = $name;
				if ($name !== 'iban') {
					return null;
				}

				return new class {

					/**
					 * ISO 13616 mod-97.
					 *
					 * @param mixed $data The value.
					 *
					 * @return bool Whether it is a well-formed IBAN.
					 */
					public function validate(mixed $data): bool {
						if (is_string($data) === false || preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $data) !== 1) {
							return false;
						}

						$digits = '';
						foreach (str_split(substr($data, 4).substr($data, 0, 4)) as $char) {
							$digits .= ctype_alpha($char) ? (string) (ord($char) - 55) : $char;
						}

						$remainder = 0;
						foreach (str_split($digits, 7) as $chunk) {
							$remainder = (int) ($remainder.$chunk) % 97;
						}

						return $remainder === 1;
					}
				};
			}
		);
	}//end checker()

	/**
	 * The refusal a check raises.
	 *
	 * @param callable $act What to run.
	 *
	 * @return SigningFieldsRefusedException The refusal.
	 */
	private function refusal(callable $act): SigningFieldsRefusedException {
		try {
			$act();
		} catch (SigningFieldsRefusedException $e) {
			return $e;
		}

		$this->fail('Expected the fields to be refused');
	}//end refusal()

	/**
	 * A valid Dutch IBAN, typed with spaces and in lower case, is stored normalised.
	 *
	 * @return void
	 */
	public function testAValidDutchIbanIsStoredInCapitalsWithoutSpaces(): void {
		$values = $this->checker()->check(
			placements: self::PLACEMENTS,
			signerIndex: 0,
			fields: ['0' => 'nl91 abna 0417 1643 00', '1' => '  ref-7 ']
		);

		$this->assertSame(
			[
				['placement' => 0, 'label' => 'Account for the payment', 'value' => self::DUTCH, 'validation' => 'iban-nl'],
				['placement' => 1, 'label' => 'Reference', 'value' => 'ref-7'],
			],
			$values
		);
		$this->assertSame(['iban'], array_values(array_unique($this->asked)));
	}//end testAValidDutchIbanIsStoredInCapitalsWithoutSpaces()

	/**
	 * A wrong check digit is refused with the message on that field, and the message holds no value.
	 *
	 * @return void
	 */
	public function testAWrongCheckDigitIsRefusedOnThatField(): void {
		$refusal = $this->refusal(
			fn () => $this->checker()->check(placements: self::PLACEMENTS, signerIndex: 0, fields: [0 => 'NL91 ABNA 0417 1643 01'])
		);

		$this->assertSame(422, $refusal->getCode());
		$this->assertSame([0 => 'Type a valid Dutch IBAN, for example NL91 ABNA 0417 1643 00'], $refusal->fieldErrors());
		$this->assertStringNotContainsString('NL91ABNA0417164301', $refusal->getMessage());
	}//end testAWrongCheckDigitIsRefusedOnThatField()

	/**
	 * A valid German IBAN passes the any-IBAN check but not the Dutch one.
	 *
	 * @return void
	 */
	public function testAForeignIbanPassesOnlyTheAnyIbanCheck(): void {
		$refusal = $this->refusal(
			fn () => $this->checker()->check(placements: self::PLACEMENTS, signerIndex: 0, fields: [0 => self::GERMAN])
		);
		$this->assertSame([0 => 'Type a valid Dutch IBAN, for example NL91 ABNA 0417 1643 00'], $refusal->fieldErrors());

		$values = $this->checker()->check(placements: self::PLACEMENTS, signerIndex: 1, fields: [3 => self::GERMAN]);
		$this->assertSame([['placement' => 3, 'label' => '', 'value' => self::GERMAN, 'validation' => 'iban']], $values);
	}//end testAForeignIbanPassesOnlyTheAnyIbanCheck()

	/**
	 * An invalid value under the any-IBAN check names that check's message.
	 *
	 * @return void
	 */
	public function testAnInvalidIbanUnderTheAnyIbanCheckIsRefused(): void {
		$refusal = $this->refusal(
			fn () => $this->checker()->check(placements: self::PLACEMENTS, signerIndex: 1, fields: [3 => 'DE00370400440532013000'])
		);

		$this->assertSame([3 => 'Type a valid IBAN, for example NL91 ABNA 0417 1643 00'], $refusal->fieldErrors());
	}//end testAnInvalidIbanUnderTheAnyIbanCheckIsRefused()

	/**
	 * An empty or missing required field is refused; an empty optional one is left out.
	 *
	 * @return void
	 */
	public function testAnEmptyRequiredFieldIsRefusedAndAnEmptyOptionalOneLeftOut(): void {
		$refusal = $this->refusal(
			fn () => $this->checker()->check(placements: self::PLACEMENTS, signerIndex: 0, fields: [0 => '   ', 1 => ''])
		);
		$this->assertSame([0 => 'Fill in this field'], $refusal->fieldErrors());

		$refusal = $this->refusal(fn () => $this->checker()->check(placements: self::PLACEMENTS, signerIndex: 0, fields: []));
		$this->assertSame([0 => 'Fill in this field'], $refusal->fieldErrors());

		$values = $this->checker()->check(placements: self::PLACEMENTS, signerIndex: 0, fields: [0 => self::DUTCH, 1 => '']);
		$this->assertSame([0], array_column($values, 'placement'));
	}//end testAnEmptyRequiredFieldIsRefusedAndAnEmptyOptionalOneLeftOut()

	/**
	 * A value that is not text counts as empty, and an over-long one is refused.
	 *
	 * @return void
	 */
	public function testANonTextOrOverLongValueIsRefused(): void {
		$refusal = $this->refusal(
			fn () => $this->checker()->check(placements: self::PLACEMENTS, signerIndex: 0, fields: [0 => ['NL'], 1 => str_repeat('a', 501)])
		);

		$this->assertSame([0 => 'Fill in this field', 1 => 'This value is too long'], $refusal->fieldErrors());
	}//end testANonTextOrOverLongValueIsRefused()

	/**
	 * Values for another signer's field or a non-text field are ignored.
	 *
	 * @return void
	 */
	public function testOnlyTheSignersOwnTextFieldsAreTaken(): void {
		$values = $this->checker()->check(
			placements: self::PLACEMENTS,
			signerIndex: 0,
			fields: [0 => self::DUTCH, 2 => 'drawn', 3 => self::GERMAN, 9 => 'stray']
		);

		$this->assertSame([0], array_column($values, 'placement'));
		$this->assertTrue($this->checker()->hasFieldsFor(placements: self::PLACEMENTS, signerIndex: 1));
		$this->assertFalse($this->checker()->hasFieldsFor(placements: self::PLACEMENTS, signerIndex: 2));
		$this->assertFalse($this->checker()->hasFieldsFor(placements: [self::PLACEMENTS[2]], signerIndex: 0));
	}//end testOnlyTheSignersOwnTextFieldsAreTaken()

	/**
	 * A check OpenRegister lacks refuses the request at creation with a 400 per field.
	 *
	 * @return void
	 */
	public function testAMissingFormatRefusesTheRequest(): void {
		$checker = new FieldValueChecker(formatLookup: fn (string $name): ?object => null);

		$refusal = $this->refusal(fn () => $checker->assertAvailable(placements: self::PLACEMENTS));

		$this->assertSame(400, $refusal->getCode());
		$this->assertSame(
			[0 => 'This check is not available on this server', 3 => 'This check is not available on this server'],
			$refusal->fieldErrors()
		);
	}//end testAMissingFormatRefusesTheRequest()

	/**
	 * A format without `validate()` counts as missing; a known one lets the request through.
	 *
	 * @return void
	 */
	public function testAFormatMustBeAbleToValidate(): void {
		$checker = new FieldValueChecker(formatLookup: fn (string $name): ?object => new \stdClass());
		$refusal = $this->refusal(fn () => $checker->assertAvailable(placements: [self::PLACEMENTS[3]]));
		$this->assertSame([0 => 'This check is not available on this server'], $refusal->fieldErrors());

		$this->checker()->assertAvailable(placements: self::PLACEMENTS);
		$this->assertSame(['iban', 'iban'], $this->asked);
	}//end testAFormatMustBeAbleToValidate()

	/**
	 * Without OpenRegister's format class the default lookup finds nothing.
	 *
	 * @return void
	 */
	public function testTheDefaultLookupFindsNoFormatThatDoesNotExist(): void {
		$this->assertNull(FieldValueChecker::openRegisterFormat(name: 'no-such-check'));

		$checker = new FieldValueChecker();
		$refusal = $this->refusal(
			fn () => $checker->check(
				placements: [['signerIndex' => 0, 'type' => 'text', 'validation' => 'no-such-check']],
				signerIndex: 0,
				fields: [0 => 'x']
			)
		);
		$this->assertSame([0 => 'This check is not available on this server'], $refusal->fieldErrors());
	}//end testTheDefaultLookupFindsNoFormatThatDoesNotExist()
}//end class
