<?php

/**
 * Unit tests for the seeded employer statement template
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/templates-employer-statement/specs
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Settings;

use OCA\Filinq\Service\Charts\ChartSvgRenderer;
use OCA\Filinq\Service\Charts\TableHtmlRenderer;
use OCA\Filinq\Service\TemplateRenderer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The seeded werkgeversverklaring renders, through the real sandbox, from
 * the data humaniq sends: merged values, "niet opgegeven" for what is
 * missing, and never a BSN.
 *
 * @covers \OCA\Filinq\Service\TemplateRenderer
 * @uses   \OCA\Filinq\Service\Charts\ChartSvgRenderer
 * @uses   \OCA\Filinq\Service\Charts\TableHtmlRenderer
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EmployerStatementTemplateTest extends TestCase {

	/**
	 * The seeded template object.
	 *
	 * @return array<string, mixed>
	 */
	private function seed(): array {
		$register = json_decode((string)file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json'), true);
		$this->assertIsArray($register);
		$matches = array_values(
			array_filter(
				$register['components']['objects'],
				static fn (array $o): bool => ($o['@self']['schema'] ?? '') === 'template' && ($o['@self']['slug'] ?? '') === 'werkgeversverklaring-standaard'
			)
		);
		$this->assertCount(1, $matches);

		return $matches[0];

	}//end seed()

	/**
	 * Render the seeded content with data.
	 *
	 * @param array<string, mixed> $data The merge data.
	 *
	 * @return string The rendered HTML.
	 */
	private function render(array $data): string {
		$renderer = new TemplateRenderer($this->createMock(LoggerInterface::class), new ChartSvgRenderer(), new TableHtmlRenderer());

		return $renderer->renderTemplate($this->seed()['content'], $data);

	}//end render()

	/**
	 * The employee, as humaniq resolves it, with a BSN the template must never print.
	 *
	 * @return array<string, mixed>
	 */
	private static function employee(): array {
		return [
			'firstName' => 'Sanne',
			'lastName' => 'de Vries',
			'dateOfBirth' => '1990-04-12',
			'startDate' => '2019-02-01',
			'grossMonthlySalary' => 3850.5,
			'employeeNumber' => 'P-1042',
			'bsn' => '123456782',
		];

	}//end employee()

	/**
	 * Exactly one template in namespace hrmq with category werkgeversverklaring, versioned for in-place edits.
	 *
	 * @return void
	 */
	public function testHumaniqFindsExactlyOneTemplate(): void {
		$register = json_decode((string)file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json'), true);
		$hits = array_filter(
			$register['components']['objects'],
			static fn (array $o): bool => ($o['@self']['schema'] ?? '') === 'template' && ($o['namespace'] ?? '') === 'hrmq' && ($o['category'] ?? '') === 'werkgeversverklaring'
		);

		$this->assertCount(1, $hits);
		$seed = $this->seed();
		$this->assertSame('1.0.0', $seed['@self']['version']);
		$this->assertSame(['A4', 'P'], [$seed['format'], $seed['orientation']]);

	}//end testHumaniqFindsExactlyOneTemplate()

	/**
	 * A permanent contract merges every value, the year salary and the statement block, and no BSN.
	 *
	 * @return void
	 */
	public function testAPermanentContractMergesEverything(): void {
		$html = $this->render(
			[
				'employer' => ['name' => 'Gemeente Zuiddrecht', 'address' => 'Markt 1, Zuiddrecht', 'kvkNumber' => '12345678', 'city' => 'Zuiddrecht', 'signatoryName' => 'P. Jansen', 'signatoryTitle' => 'Hoofd HR'],
				'Employee' => self::employee(),
				'EmploymentContract' => ['type' => 'permanent', 'startDate' => '2019-02-01', 'endDate' => null, 'hoursPerWeek' => 36],
				'document' => ['requestedAt' => '2026-11-13'],
				'statement' => ['holidayAllowance' => '8%', 'endOfYearPayment' => '8,3%', 'extensionIntended' => 'niet van toepassing', 'wageGarnishment' => 'nee', 'remarks' => 'Geen proeftijd.'],
			]
		);

		foreach (['Gemeente Zuiddrecht', 'Sanne de Vries', '12-04-1990', 'P-1042', '01-02-2019', 'onbepaalde tijd', 'Einddatum: geen', 'Uren per week: 36', '3.850,50', '46.206,00', 'Vakantietoeslag: 8%', 'Geen proeftijd.', 'te Zuiddrecht op 13-11-2026', 'Hoofd HR'] as $expected) {
			$this->assertStringContainsString($expected, $html);
		}

		$this->assertStringNotContainsString('niet opgegeven', $html);
		$this->assertStringNotContainsString('123456782', $html, 'the statement never carries a BSN');

	}//end testAPermanentContractMergesEverything()

	/**
	 * A fixed-term contract shows its end date, and every item humaniq does not hold reads "niet opgegeven".
	 *
	 * @return void
	 */
	public function testAFixedTermContractWithMissingItemsSaysNotStated(): void {
		$employee = self::employee();
		unset($employee['grossMonthlySalary'], $employee['dateOfBirth']);

		$html = $this->render(
			[
				'employer' => ['name' => 'Gemeente Zuiddrecht'],
				'Employee' => $employee,
				'EmploymentContract' => ['type' => 'temporary', 'startDate' => '2026-01-01', 'endDate' => '2026-12-31', 'hoursPerWeek' => 24],
			]
		);

		$this->assertStringContainsString('bepaalde tijd', $html);
		$this->assertStringContainsString('Einddatum: 31-12-2026', $html);
		$this->assertStringContainsString('Geboortedatum: niet opgegeven', $html);
		$this->assertStringContainsString('Bruto maandsalaris: niet opgegeven', $html);
		$this->assertStringContainsString('Vakantietoeslag: niet opgegeven', $html);
		$this->assertStringContainsString('Loonbeslag of looncessie: niet opgegeven', $html);
		$this->assertStringContainsString('op niet opgegeven', $html, 'no request date is never printed as today');
		$this->assertStringNotContainsString('123456782', $html);

	}//end testAFixedTermContractWithMissingItemsSaysNotStated()
}//end class
