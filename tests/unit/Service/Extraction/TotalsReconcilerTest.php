<?php

/**
 * Unit tests for TotalsReconciler
 *
 * @category  Tests
 * @package   OCA\DocuDesk\Tests\Unit\Service\Extraction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service\Extraction;

use OCA\DocuDesk\Service\Extraction\TotalsReconciler;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TotalsReconciler.
 */
class TotalsReconcilerTest extends TestCase {

	private TotalsReconciler $reconciler;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->reconciler = new TotalsReconciler();

	}//end setUp()

	/**
	 * 100.00 + 21.00 reconciles with 121.00.
	 *
	 * @return void
	 */
	public function testReconcilingTotalsReturnTrue(): void {
		$this->assertTrue($this->reconciler->reconciles(100.00, 21.00, 121.00));

	}//end testReconcilingTotalsReturnTrue()

	/**
	 * 100.00 + 21.00 does not reconcile with 130.00.
	 *
	 * @return void
	 */
	public function testNonReconcilingTotalsReturnFalse(): void {
		$this->assertFalse($this->reconciler->reconciles(100.00, 21.00, 130.00));

	}//end testNonReconcilingTotalsReturnFalse()

	/**
	 * A missing (null) value always fails reconciliation.
	 *
	 * @return void
	 */
	public function testMissingValueReturnsFalse(): void {
		$this->assertFalse($this->reconciler->reconciles(100.00, null, 121.00));
		$this->assertFalse($this->reconciler->reconciles(null, null, null));

	}//end testMissingValueReturnsFalse()

	/**
	 * A rounding-level discrepancy within tolerance still reconciles.
	 *
	 * @return void
	 */
	public function testWithinToleranceReconciles(): void {
		$this->assertTrue($this->reconciler->reconciles(100.00, 21.00, 121.005));

	}//end testWithinToleranceReconciles()
}//end class
