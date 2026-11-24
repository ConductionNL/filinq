<?php

/**
 * Unit tests for EmploymentContractParser
 *
 * @package   OCA\Filinq\Tests\Unit\Service\Extraction

 * @category  Tests
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/extraction-employee-document/specs/employee-document-extraction/spec.md#REQ-EDE-002
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Extraction;

use OCA\Filinq\Service\Contract\ContractTermExtractor;
use OCA\Filinq\Service\Extraction\EmploymentContractParser;
use PHPUnit\Framework\TestCase;

/**
 * Tests for EmploymentContractParser.
 */
class EmploymentContractParserTest extends TestCase {

	/**
	 * The values of a parse, without confidences.
	 *
	 * @param string $text The contract text.
	 *
	 * @return array<string, string|float>
	 */
	private function values(string $text): array {
		return array_map(static fn (array $field): string|float => $field['value'], (new EmploymentContractParser(terms: new ContractTermExtractor()))->parse(text: $text));
	}//end values()

	/**
	 * The spec's Dutch contract.
	 *
	 * @return void
	 */
	public function testADutchContract(): void {
		$text = implode(
			"\n",
			[
				'ARBEIDSOVEREENKOMST',
				'Werknemer: de heer Jan de Boer, geboren te Utrecht',
				'De werknemer treedt met ingang van 1 maart 2026 in dienst voor bepaalde tijd,',
				'tot en met 28-02-2027.',
				'De arbeidsduur bedraagt 32 uur per week.',
				'Het salaris bedraagt € 3.250,00 bruto per maand.',
			]
		);

		$this->assertSame(
			expected: [
				'firstName' => 'Jan',
				'lastName' => 'de Boer',
				'startDate' => '2026-03-01',
				'endDate' => '2027-02-28',
				'hoursPerWeek' => 32.0,
				'monthlyWage' => 3250.0,
				'contractType' => 'temporary',
			],
			actual: $this->values(text: $text)
		);
	}//end testADutchContract()

	/**
	 * An English contract for an indefinite term, paid by the hour.
	 *
	 * @return void
	 */
	public function testAnEnglishHourlyContract(): void {
		$text = "Employee name: Ms. Anna Smith\nStart date: 2026-04-01\nThis is a permanent contract of 20 hours per week at EUR 18,50 gross per hour.";

		$this->assertSame(
			expected: [
				'firstName' => 'Anna',
				'lastName' => 'Smith',
				'startDate' => '2026-04-01',
				'hoursPerWeek' => 20.0,
				'hourlyWage' => 18.5,
				'contractType' => 'permanent',
			],
			actual: $this->values(text: $text)
		);
	}//end testAnEnglishHourlyContract()

	/**
	 * A labelled value is surer than a phrased one; an unknown term is absent.
	 *
	 * @return void
	 */
	public function testConfidenceAndAbsence(): void {
		$fields = (new EmploymentContractParser(terms: new ContractTermExtractor()))->parse(text: "Ingangsdatum: 31-02-2026\nStartdatum: 01-02-2026\n36 uur per week");

		$this->assertSame(expected: EmploymentContractParser::LABELLED, actual: $fields['startDate']['confidence']);
		$this->assertSame(expected: EmploymentContractParser::PHRASED, actual: $fields['hoursPerWeek']['confidence']);
		$this->assertArrayNotHasKey(key: 'contractType', array: $fields);
		$this->assertArrayNotHasKey(key: 'lastName', array: $fields);
	}//end testConfidenceAndAbsence()
}//end class
