<?php

/**
 * The acceptance a machine draft waits for.
 *
 * 🔴 HALF AN ACCEPTANCE IS NOT AN ACCEPTANCE. A name with no moment cannot be
 * placed in time when somebody asks a year later whether the draft was read
 * before or after the correction, and a moment with no name records that
 * somebody accepted it without saying who. Filling in the missing half is how a
 * record stops meaning anything.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\PlainRenditionRefusedException;
use OCA\Filinq\Service\PlainRenditionAcceptanceGate;
use PHPUnit\Framework\TestCase;

/**
 * `PlainRenditionAcceptanceGate`.
 *
 * @covers \OCA\Filinq\Service\PlainRenditionAcceptanceGate
 */
class PlainRenditionAcceptanceGateTest extends TestCase {

	/**
	 * Text rendered from the counterpart template needs no acceptance.
	 *
	 * @return void
	 */
	public function testTemplateTextNeedsNoAcceptance(): void {
		$gate = new PlainRenditionAcceptanceGate();

		self::assertFalse($gate->needsAcceptance(source: PlainRenditionAcceptanceGate::SOURCE_TEMPLATE));
		self::assertSame(
			['acceptedBy' => '', 'acceptedAt' => ''],
			$gate->require(source: PlainRenditionAcceptanceGate::SOURCE_TEMPLATE, acceptance: [])
		);
	}//end testTemplateTextNeedsNoAcceptance()

	/**
	 * A machine draft with a complete acceptance passes, and it is recorded.
	 *
	 * @return void
	 */
	public function testACompleteAcceptancePasses(): void {
		$gate = new PlainRenditionAcceptanceGate();

		$accepted = $gate->require(
			source: PlainRenditionAcceptanceGate::SOURCE_MACHINE,
			acceptance: ['acceptedBy' => 'anne', 'acceptedAt' => '2026-09-18T10:00:00+02:00']
		);

		self::assertSame('anne', $accepted['acceptedBy']);
		self::assertSame('2026-09-18T10:00:00+02:00', $accepted['acceptedAt']);
	}//end testACompleteAcceptancePasses()

	/**
	 * A machine draft nobody accepted is refused, and says the draft is waiting.
	 *
	 * @return void
	 */
	public function testAnUnacceptedMachineDraftIsRefused(): void {
		$this->expectException(PlainRenditionRefusedException::class);
		$this->expectExceptionMessage('still waiting');

		(new PlainRenditionAcceptanceGate())->require(
			source: PlainRenditionAcceptanceGate::SOURCE_MACHINE,
			acceptance: []
		);
	}//end testAnUnacceptedMachineDraftIsRefused()

	/**
	 * A name with no moment is refused, and the refusal says which half is missing.
	 *
	 * @return void
	 */
	public function testANameWithoutAMomentIsRefused(): void {
		$this->expectException(PlainRenditionRefusedException::class);
		$this->expectExceptionMessage('a person and no moment');

		(new PlainRenditionAcceptanceGate())->require(
			source: PlainRenditionAcceptanceGate::SOURCE_MACHINE,
			acceptance: ['acceptedBy' => 'anne']
		);
	}//end testANameWithoutAMomentIsRefused()

	/**
	 * A moment with no name is refused too.
	 *
	 * @return void
	 */
	public function testAMomentWithoutANameIsRefused(): void {
		$this->expectException(PlainRenditionRefusedException::class);
		$this->expectExceptionMessage('a moment and no person');

		(new PlainRenditionAcceptanceGate())->require(
			source: PlainRenditionAcceptanceGate::SOURCE_MACHINE,
			acceptance: ['acceptedAt' => '2026-09-18T10:00:00+02:00']
		);
	}//end testAMomentWithoutANameIsRefused()
}//end class
