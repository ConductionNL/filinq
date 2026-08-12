<?php

/**
 * Unit tests for CategoryKeywordMapper
 *
 * Covers REQ-GLS-03: cold-start keyword matching, priority ordering, the
 * disabled-rule case, and the no-match case.
 *
 * @category  Tests
 * @package   OCA\DocuDesk\Tests\Unit\Service\Suggestion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service\Suggestion;

use OCA\DocuDesk\Service\Suggestion\CategoryKeywordMapper;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CategoryKeywordMapper.
 */
class CategoryKeywordMapperTest extends TestCase {

	private CategoryKeywordMapper $mapper;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->mapper = new CategoryKeywordMapper();

	}//end setUp()

	/**
	 * @return void
	 */
	public function testKeywordRuleMatchesOnColdStart(): void {
		$rules = [
			['keywords' => ['lunch'], 'accountCode' => '4400', 'accountLabel' => 'Representatiekosten', 'priority' => 0, 'enabled' => true],
		];

		$result = $this->mapper->match(text: 'Lunchroom De Hoek', rules: $rules);

		$this->assertSame('4400', $result['code']);
		$this->assertSame(CategoryKeywordMapper::COLD_START_CONFIDENCE, $result['confidence']);
		$this->assertStringContainsString('lunch', $result['rationale']);
		$this->assertStringContainsString('4400', $result['rationale']);

	}//end testKeywordRuleMatchesOnColdStart()

	/**
	 * @return void
	 */
	public function testColdStartConfidenceLowerThanTypicalHistoryConfidence(): void {
		$rules = [
			['keywords' => ['lunch'], 'accountCode' => '4400', 'priority' => 0, 'enabled' => true],
		];

		$result = $this->mapper->match(text: 'Lunchroom De Hoek', rules: $rules);

		// 0.8 mirrors HistoryRankerTest's dominant-account example.
		$this->assertLessThan(0.8, $result['confidence']);

	}//end testColdStartConfidenceLowerThanTypicalHistoryConfidence()

	/**
	 * @return void
	 */
	public function testHigherPriorityRuleWinsOverLowerPriorityMatch(): void {
		$rules = [
			['keywords' => ['hosting'], 'accountCode' => '4300', 'priority' => 5, 'enabled' => true],
			['keywords' => ['hosting'], 'accountCode' => '4999', 'priority' => 10, 'enabled' => true],
		];

		$result = $this->mapper->match(text: 'Managed hosting maart 2024', rules: $rules);

		$this->assertSame('4999', $result['code']);

	}//end testHigherPriorityRuleWinsOverLowerPriorityMatch()

	/**
	 * @return void
	 */
	public function testDisabledRuleIsSkipped(): void {
		$rules = [
			['keywords' => ['hosting'], 'accountCode' => '4300', 'priority' => 10, 'enabled' => false],
		];

		$result = $this->mapper->match(text: 'Managed hosting maart 2024', rules: $rules);

		$this->assertNull($result);

	}//end testDisabledRuleIsSkipped()

	/**
	 * @return void
	 */
	public function testNoHistoryAndNoRuleMatchReturnsNull(): void {
		$result = $this->mapper->match(text: 'Onbekende leverancier', rules: []);

		$this->assertNull($result);

	}//end testNoHistoryAndNoRuleMatchReturnsNull()
}//end class
