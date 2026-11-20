<?php

/**
 * Which accessibility signal a document shows: the validator's verdict when
 * there is one, the heuristics labelled as heuristic otherwise.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\VeraPdf
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-validator-verdict-is-the-authoritative-conformance-heuristics-remain-the-floor-req-ddpum-004
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\VeraPdf;

use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceState;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\VeraPdf\AccessibilityConformanceState
 */
class AccessibilityConformanceStateTest extends TestCase {

	public function testAValidatorFailOverridesAHeuristicPass(): void {
		$state = (new AccessibilityConformanceState())->resolve(report: ['compliant' => false], heuristicFindings: [], validatorAvailable: true);

		$this->assertSame(['source' => 'validator', 'state' => 'non-conformant', 'validated' => true, 'validatorAvailable' => true], $state);

	}//end testAValidatorFailOverridesAHeuristicPass()

	public function testAValidatorPassIsConformantWhateverTheHeuristicsSay(): void {
		$state = (new AccessibilityConformanceState())->resolve(
			report: ['compliant' => true],
			heuristicFindings: [['checkId' => 'pdfua-identifier-missing']],
			validatorAvailable: true
		);

		$this->assertSame('validator', $state['source']);
		$this->assertSame('conformant', $state['state']);

	}//end testAValidatorPassIsConformantWhateverTheHeuristicsSay()

	public function testWithoutAReportAHeuristicPassOnlyLooksTagged(): void {
		$state = (new AccessibilityConformanceState())->resolve(report: null, heuristicFindings: [], validatorAvailable: false);

		$this->assertSame(['source' => 'heuristic', 'state' => 'looks-tagged', 'validated' => false, 'validatorAvailable' => false], $state);

	}//end testWithoutAReportAHeuristicPassOnlyLooksTagged()

	public function testWithoutAReportHeuristicFindingsAreHeuristicIssues(): void {
		$state = (new AccessibilityConformanceState())->resolve(report: null, heuristicFindings: [['checkId' => 'pdf-not-tagged']], validatorAvailable: true);

		$this->assertSame('heuristic', $state['source']);
		$this->assertSame('heuristic-issues', $state['state']);
		$this->assertFalse($state['validated']);
		$this->assertTrue($state['validatorAvailable'], 'Available but not yet run: the card can offer the check.');

	}//end testWithoutAReportHeuristicFindingsAreHeuristicIssues()

	public function testAReportWithoutAVerdictIsNoVerdict(): void {
		$state = (new AccessibilityConformanceState())->resolve(report: ['fileId' => 4], heuristicFindings: [], validatorAvailable: true);

		$this->assertSame('heuristic', $state['source']);
		$this->assertFalse($state['validated']);

	}//end testAReportWithoutAVerdictIsNoVerdict()
}//end class
