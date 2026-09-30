<?php

/**
 * Unit tests for ContractDates and ContractTermExtractor
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Contract
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#4-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Contract;

use OCA\Filinq\Service\Contract\ContractDates;
use OCA\Filinq\Service\Contract\ContractTermExtractor;
use PHPUnit\Framework\TestCase;

/**
 * The notice-deadline rule and the local term patterns.
 */
class ContractTermsTest extends TestCase {

	/**
	 * End date minus notice period, only when nobody entered a deadline.
	 *
	 * @return void
	 */
	public function testTheNoticeDeadlineDefaultsAndAManualOneIsKept(): void {
		$dates = new ContractDates();

		$this->assertSame('2028-10-02', $dates->withNoticeDeadline(contract: ['endDate' => '2028-12-31', 'noticePeriodDays' => 90])['noticeDeadline']);
		$this->assertSame('2028-06-01', $dates->withNoticeDeadline(contract: ['endDate' => '2028-12-31', 'noticePeriodDays' => 90, 'noticeDeadline' => '2028-06-01'])['noticeDeadline']);
		$this->assertArrayNotHasKey('noticeDeadline', $dates->withNoticeDeadline(contract: ['endDate' => '2028-12-31']));
		$this->assertArrayNotHasKey('noticeDeadline', $dates->withNoticeDeadline(contract: ['endDate' => '2028-02-30', 'noticePeriodDays' => 1]));
		$this->assertArrayNotHasKey('noticeDeadline', $dates->withNoticeDeadline(contract: ['endDate' => '2028-12-31', 'noticePeriodDays' => 'soon']));

	}//end testTheNoticeDeadlineDefaultsAndAManualOneIsKept()

	/**
	 * A Dutch contract text yields its dates, notice period, value and supplier.
	 *
	 * @return void
	 */
	public function testADutchContractYieldsItsTerms(): void {
		$text = "Opdrachtnemer: Groenbedrijf Demostad B.V., gevestigd te Demostad.\n"
			. "De overeenkomst gaat in op 1 januari 2026 en heeft als einddatum 31-12-2028.\n"
			. "De opzegtermijn van 3 maanden. De contractwaarde bedraagt € 240.000,00 exclusief btw.";

		$terms = $this->byField((new ContractTermExtractor())->extract(text: $text));

		$this->assertSame('2026-01-01', $terms['startDate']['value']);
		$this->assertSame('2028-12-31', $terms['endDate']['value']);
		$this->assertSame('90', $terms['noticePeriodDays']['value']);
		$this->assertLessThan($terms['endDate']['confidence'], $terms['noticePeriodDays']['confidence']);
		$this->assertSame('240000', $terms['value']['value']);
		$this->assertSame('EUR', $terms['currency']['value']);
		$this->assertSame('Groenbedrijf Demostad B.V.', $terms['party']['value']);

	}//end testADutchContractYieldsItsTerms()

	/**
	 * English wording and English number formatting.
	 *
	 * @return void
	 */
	public function testAnEnglishContractYieldsItsTerms(): void {
		$text = 'This agreement expires on 2027-06-30. Notice period of 60 days. Total value EUR 12,500.50.';

		$terms = $this->byField((new ContractTermExtractor())->extract(text: $text));

		$this->assertSame('2027-06-30', $terms['endDate']['value']);
		$this->assertSame('60', $terms['noticePeriodDays']['value']);
		$this->assertSame('12500.5', $terms['value']['value']);

	}//end testAnEnglishContractYieldsItsTerms()

	/**
	 * Text without terms proposes nothing, and an impossible date is not a date.
	 *
	 * @return void
	 */
	public function testNoTermsNoProposals(): void {
		$extractor = new ContractTermExtractor();

		$this->assertSame([], $extractor->extract(text: 'Een brief zonder afspraken.'));
		$this->assertSame([], $extractor->extract(text: 'Einddatum: 31-02-2028.'));

	}//end testNoTermsNoProposals()

	/**
	 * Proposals keyed by field.
	 *
	 * @param list<array{field: string, value: string, confidence: float}> $terms The proposals.
	 *
	 * @return array<string, array{field: string, value: string, confidence: float}> By field.
	 */
	private function byField(array $terms): array {
		$keyed = [];
		foreach ($terms as $term) {
			$keyed[$term['field']] = $term;
		}

		return $keyed;

	}//end byField()
}//end class
