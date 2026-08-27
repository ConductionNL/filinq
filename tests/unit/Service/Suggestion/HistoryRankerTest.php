<?php

/**
 * Unit tests for HistoryRanker
 *
 * Covers REQ-GLS-02: windowed frequency ranking, rationale text, the
 * short-window case, the no-history case, and the consumer-supplied
 * candidate-set constraint.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Suggestion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Suggestion;

use OCA\Filinq\Service\Suggestion\HistoryRanker;
use PHPUnit\Framework\TestCase;

/**
 * Tests for HistoryRanker.
 */
class HistoryRankerTest extends TestCase {

	private HistoryRanker $ranker;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ranker = new HistoryRanker();

	}//end setUp()

	/**
	 * Build a fixture of 10 bookings: 8 to `4300`, 2 to `4200`, most recent
	 * first, one per month.
	 *
	 * @return array<int, array{accountCode: string, accountLabel: string, bookedAt: string}>
	 */
	private function tenBookingFixture(): array {
		$bookings = [];
		for ($i = 0; $i < 8; $i++) {
			$bookings[] = [
				'accountCode' => '4300',
				'accountLabel' => 'Kantoorkosten',
				'bookedAt' => sprintf('2024-%02d-15T09:00:00+00:00', ($i + 1)),
			];
		}

		for ($i = 8; $i < 10; $i++) {
			$bookings[] = [
				'accountCode' => '4200',
				'accountLabel' => 'Reiskosten',
				'bookedAt' => sprintf('2024-%02d-15T09:00:00+00:00', ($i + 1)),
			];
		}

		return $bookings;
	}//end tenBookingFixture()

	/**
	 * @return void
	 */
	public function testDominantAccountRankedFirstWithRationale(): void {
		$result = $this->ranker->rank(bookings: $this->tenBookingFixture());

		$this->assertSame('4300', $result[0]['code']);
		$this->assertSame(0.8, $result[0]['confidence']);
		$this->assertStringContainsString('4300', $result[0]['rationale']);
		$this->assertStringContainsString('8', $result[0]['rationale']);
		$this->assertStringContainsString('10', $result[0]['rationale']);

	}//end testDominantAccountRankedFirstWithRationale()

	/**
	 * @return void
	 */
	public function testSecondPlaceAccountAlsoRanked(): void {
		$result = $this->ranker->rank(bookings: $this->tenBookingFixture());

		$this->assertCount(2, $result);
		$this->assertSame('4200', $result[1]['code']);
		$this->assertSame(0.2, $result[1]['confidence']);

	}//end testSecondPlaceAccountAlsoRanked()

	/**
	 * @return void
	 */
	public function testFewerThanTenBookingsUsesAvailableWindow(): void {
		$bookings = [
			['accountCode' => '5100', 'accountLabel' => null, 'bookedAt' => '2024-01-01T00:00:00+00:00'],
			['accountCode' => '5100', 'accountLabel' => null, 'bookedAt' => '2024-02-01T00:00:00+00:00'],
			['accountCode' => '5100', 'accountLabel' => null, 'bookedAt' => '2024-03-01T00:00:00+00:00'],
		];

		$result = $this->ranker->rank(bookings: $bookings);

		$this->assertSame('5100', $result[0]['code']);
		$this->assertSame(1.0, $result[0]['confidence']);
		$this->assertStringContainsString('3', $result[0]['rationale']);

	}//end testFewerThanTenBookingsUsesAvailableWindow()

	/**
	 * @return void
	 */
	public function testNoHistoryYieldsEmptyResultWithoutError(): void {
		$result = $this->ranker->rank(bookings: []);

		$this->assertSame([], $result);

	}//end testNoHistoryYieldsEmptyResultWithoutError()

	/**
	 * @return void
	 */
	public function testCandidateSetConstrainsRanking(): void {
		$bookings = [
			['accountCode' => '4300', 'accountLabel' => null, 'bookedAt' => '2024-01-01T00:00:00+00:00'],
			['accountCode' => '9999', 'accountLabel' => null, 'bookedAt' => '2024-02-01T00:00:00+00:00'],
		];

		$result = $this->ranker->rank(bookings: $bookings, candidateCodes: ['4300']);

		$this->assertCount(1, $result);
		$this->assertSame('4300', $result[0]['code']);

	}//end testCandidateSetConstrainsRanking()

	/**
	 * @return void
	 */
	public function testResultCappedToThreeSuggestions(): void {
		$bookings = [
			['accountCode' => 'A', 'accountLabel' => null, 'bookedAt' => '2024-01-01T00:00:00+00:00'],
			['accountCode' => 'B', 'accountLabel' => null, 'bookedAt' => '2024-02-01T00:00:00+00:00'],
			['accountCode' => 'C', 'accountLabel' => null, 'bookedAt' => '2024-03-01T00:00:00+00:00'],
			['accountCode' => 'D', 'accountLabel' => null, 'bookedAt' => '2024-04-01T00:00:00+00:00'],
		];

		$result = $this->ranker->rank(bookings: $bookings);

		$this->assertCount(3, $result);

	}//end testResultCappedToThreeSuggestions()
}//end class
