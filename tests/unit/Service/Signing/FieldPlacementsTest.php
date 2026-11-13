<?php

/**
 * Unit tests for FieldPlacements.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use InvalidArgumentException;
use OCA\Filinq\Service\Signing\FieldPlacements;
use PHPUnit\Framework\TestCase;

/**
 * Normalising the signature field placements of a signing request.
 *
 * @covers \OCA\Filinq\Service\Signing\FieldPlacements
 */
class FieldPlacementsTest extends TestCase {

	/**
	 * A valid placement.
	 *
	 * @var array<string, mixed>
	 */
	private const VALID = ['signerIndex' => 1, 'page' => 2, 'x' => 0, 'y' => 0.5, 'width' => 1, 'height' => 0.5, 'type' => 'signature'];

	/**
	 * Assert that the placements are refused with the given message.
	 *
	 * @param mixed  $placements The placements.
	 * @param string $message    The expected message.
	 *
	 * @return void
	 */
	private function assertRefused(mixed $placements, string $message): void {
		try {
			(new FieldPlacements())->normalise(placements: $placements, signerCount: 2);
			self::fail('Expected the placements to be refused: ' . $message);
		} catch (InvalidArgumentException $e) {
			self::assertSame($message, $e->getMessage());
		}
	}//end assertRefused()

	/**
	 * No placements normalise to an empty list.
	 *
	 * @return void
	 */
	public function testNoPlacementsAreEmpty(): void {
		self::assertSame([], (new FieldPlacements())->normalise(placements: null, signerCount: 0));
		self::assertSame([], (new FieldPlacements())->normalise(placements: [], signerCount: 0));
	}//end testNoPlacementsAreEmpty()

	/**
	 * A valid placement is answered in key order with the box as floats, edges on the page allowed.
	 *
	 * @return void
	 */
	public function testAValidPlacementIsNormalised(): void {
		$placement = array_reverse(self::VALID, true);

		self::assertSame(
			[['signerIndex' => 1, 'page' => 2, 'x' => 0.0, 'y' => 0.5, 'width' => 1.0, 'height' => 0.5, 'type' => 'signature']],
			(new FieldPlacements())->normalise(placements: [$placement], signerCount: 2)
		);
	}//end testAValidPlacementIsNormalised()

	/**
	 * Placements must be a list of at most MAX entries.
	 *
	 * @return void
	 */
	public function testThePlacementsMustBeAShortList(): void {
		$this->assertRefused('signature', 'fieldPlacements must be a list');
		$this->assertRefused(['a' => self::VALID], 'fieldPlacements must be a list');
		$this->assertRefused(array_fill(0, FieldPlacements::MAX + 1, self::VALID), 'fieldPlacements holds more than 200 fields');
	}//end testThePlacementsMustBeAShortList()

	/**
	 * A placement must be an array of the known keys with a known type.
	 *
	 * @return void
	 */
	public function testAPlacementMustHoldOnlyKnownKeysAndAType(): void {
		$keys = 'fieldPlacements[1] must hold only signerIndex, page, x, y, width, height, type, label, required, validation';
		$this->assertRefused([self::VALID, 'box'], $keys);
		$this->assertRefused([self::VALID, self::VALID + ['colour' => 'red']], $keys);
		$this->assertRefused(
			[array_merge(self::VALID, ['type' => 'stamp'])],
			'fieldPlacements[0].type must be one of signature, initials, date, text, checkbox'
		);
	}//end testAPlacementMustHoldOnlyKnownKeysAndAType()

	/**
	 * The signer index must name one of the signers and the page must be a whole number from one.
	 *
	 * @return void
	 */
	public function testTheSignerAndPageMustBeInRange(): void {
		$this->assertRefused([array_merge(self::VALID, ['signerIndex' => '1'])], 'fieldPlacements[0].signerIndex must be a whole number from 0');
		$this->assertRefused([array_merge(self::VALID, ['signerIndex' => 2])], 'fieldPlacements[0].signerIndex names signer 2 of 2');
		$this->assertRefused([array_merge(self::VALID, ['page' => 0])], 'fieldPlacements[0].page must be a whole number from 1');
	}//end testTheSignerAndPageMustBeInRange()

	/**
	 * Every box value must be a number from 0 to 1, and the box must have size and stay on the page.
	 *
	 * @return void
	 */
	public function testTheBoxMustBeSizedAndOnThePage(): void {
		$this->assertRefused([array_merge(self::VALID, ['x' => '0.1'])], 'fieldPlacements[0].x must be a number from 0 to 1');
		$this->assertRefused([array_merge(self::VALID, ['height' => 1.5])], 'fieldPlacements[0].height must be a number from 0 to 1');
		$this->assertRefused([array_merge(self::VALID, ['width' => -0.1])], 'fieldPlacements[0].width must be a number from 0 to 1');
		$this->assertRefused([array_merge(self::VALID, ['width' => 0])], 'fieldPlacements[0] must be a box of some size that stays on the page');
		$this->assertRefused([array_merge(self::VALID, ['x' => 0.5, 'width' => 0.6])], 'fieldPlacements[0] must be a box of some size that stays on the page');
	}//end testTheBoxMustBeSizedAndOnThePage()

	/**
	 * A text field keeps its label, required flag and check (signing-field-validation REQ-SFV-001).
	 *
	 * @return void
	 */
	public function testATextFieldKeepsItsLabelRequiredFlagAndCheck(): void {
		$text = array_merge(self::VALID, ['type' => 'text', 'label' => ' Account for the payment ', 'required' => true, 'validation' => 'iban-nl']);

		self::assertSame(
			[['signerIndex' => 1, 'page' => 2, 'x' => 0.0, 'y' => 0.5, 'width' => 1.0, 'height' => 0.5, 'type' => 'text', 'label' => 'Account for the payment', 'required' => true, 'validation' => 'iban-nl']],
			(new FieldPlacements())->normalise(placements: [$text], signerCount: 2)
		);
		self::assertSame(
			[['signerIndex' => 1, 'page' => 2, 'x' => 0.0, 'y' => 0.5, 'width' => 1.0, 'height' => 0.5, 'type' => 'text', 'required' => false]],
			(new FieldPlacements())->normalise(placements: [array_merge(self::VALID, ['type' => 'text', 'required' => false, 'validation' => null])], signerCount: 2)
		);
	}//end testATextFieldKeepsItsLabelRequiredFlagAndCheck()

	/**
	 * Other field types ignore the three text settings.
	 *
	 * @return void
	 */
	public function testOtherTypesIgnoreTheTextSettings(): void {
		self::assertSame(
			[['signerIndex' => 1, 'page' => 2, 'x' => 0.0, 'y' => 0.5, 'width' => 1.0, 'height' => 0.5, 'type' => 'signature']],
			(new FieldPlacements())->normalise(placements: [self::VALID + ['label' => 7, 'required' => 'yes', 'validation' => 'bsn']], signerCount: 2)
		);
	}//end testOtherTypesIgnoreTheTextSettings()

	/**
	 * A text field's settings must have the right shape and a known check.
	 *
	 * @return void
	 */
	public function testATextFieldsSettingsMustHaveTheRightShape(): void {
		$text = array_merge(self::VALID, ['type' => 'text']);
		$this->assertRefused([$text + ['label' => 7]], 'fieldPlacements[0].label must be text of at most 200 characters');
		$this->assertRefused([$text + ['label' => str_repeat('a', 201)]], 'fieldPlacements[0].label must be text of at most 200 characters');
		$this->assertRefused([$text + ['required' => 'yes']], 'fieldPlacements[0].required must be true or false');
		$this->assertRefused([$text + ['validation' => 'bsn']], 'fieldPlacements[0].validation must be one of iban-nl, iban');
	}//end testATextFieldsSettingsMustHaveTheRightShape()
}//end class
