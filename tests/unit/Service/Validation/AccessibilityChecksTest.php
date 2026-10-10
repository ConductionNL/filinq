<?php

/**
 * The accessibility checks of document validation, run through the real
 * DocumentValidationService against the PDF fixtures in
 * tests/sample-documents/pdfua (hand-built minimal PDFs, plus one untagged
 * PDF that mPDF produced).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Validation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Validation;

use OCA\Filinq\Service\DocumentValidationService;
use OCP\Files\File;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AccessibilityChecksTest extends TestCase {

	private const FIXTURES = __DIR__ . '/../../../sample-documents/pdfua/';

	/**
	 * Validate one fixture under the given profiles.
	 *
	 * @param string               $fixture  The fixture file name.
	 * @param array<string, mixed> $profiles The configured profiles, [] for none.
	 *
	 * @return array{validationStatus: string, validationFindings: array<int, array<string, mixed>>}
	 */
	private function validate(string $fixture, array $profiles = []): array {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = '') => ($key === 'validation.profiles' && $profiles !== [] ? json_encode($profiles) : $default)
		);
		$appConfig->method('getValueInt')->willReturnCallback(static fn (string $a, string $k, int $d) => $d);

		$file = $this->createMock(File::class);
		$file->method('getContent')->willReturn((string) file_get_contents(self::FIXTURES . $fixture));
		$file->method('getMimeType')->willReturn('application/pdf');
		$file->method('getName')->willReturn($fixture);

		$service = new DocumentValidationService($this->createMock(LoggerInterface::class), $appConfig);
		return $service->validate($file, [], 'default');
	}//end validate()

	/**
	 * The accessibility findings of a result, checkId => finding.
	 *
	 * @param array<string, mixed> $result The validation result.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function accessibility(array $result): array {
		$found = [];
		foreach ($result['validationFindings'] as $finding) {
			if (($finding['category'] ?? '') === 'accessibility') {
				$found[$finding['checkId']] = $finding;
			}
		}

		return $found;
	}//end accessibility()

	public function testAnUntaggedMpdfPdfIsNotTaggedAndTheIdentifierCheckStaysQuiet(): void {
		$found = $this->accessibility($this->validate('untagged-mpdf.pdf'));

		$this->assertArrayHasKey('pdf-not-tagged', $found);
		$this->assertSame('warning', $found['pdf-not-tagged']['severity']);
		$this->assertArrayHasKey('pdf-language-missing', $found);
		$this->assertArrayNotHasKey('pdfua-identifier-missing', $found, 'suppressed when pdf-not-tagged fired');
		$this->assertArrayNotHasKey('pdf-title-missing', $found, 'mPDF wrote an Info /Title');
	}//end testAnUntaggedMpdfPdfIsNotTaggedAndTheIdentifierCheckStaysQuiet()

	public function testATaggedPdfWithoutLanguageFiresOnlyTheLanguageCheck(): void {
		$this->assertSame(['pdf-language-missing'], array_keys($this->accessibility($this->validate('tagged-no-lang.pdf'))));
	}//end testATaggedPdfWithoutLanguageFiresOnlyTheLanguageCheck()

	public function testATaggedPdfWithoutTitleFiresOnlyTheTitleCheck(): void {
		$this->assertSame(['pdf-title-missing'], array_keys($this->accessibility($this->validate('tagged-no-title.pdf'))));
	}//end testATaggedPdfWithoutTitleFiresOnlyTheTitleCheck()

	public function testATaggedPdfWithoutTheIdentifierFiresOnlyTheIdentifierCheck(): void {
		$this->assertSame(['pdfua-identifier-missing'], array_keys($this->accessibility($this->validate('tagged-no-pdfuaid.pdf'))));
	}//end testATaggedPdfWithoutTheIdentifierFiresOnlyTheIdentifierCheck()

	public function testTheAccessibleFixturePassesTheCategory(): void {
		$result = $this->validate('tagged-pdfua.pdf');

		$this->assertSame([], $this->accessibility($result));
	}//end testTheAccessibleFixturePassesTheCategory()

	public function testAStructTreeWithoutMarkedTrueIsNotTagged(): void {
		$bytes = str_replace('/Marked true', '/Marked false', (string) file_get_contents(self::FIXTURES . 'tagged-pdfua.pdf'));
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(static fn (string $a, string $k, string $d = '') => $d);
		$appConfig->method('getValueInt')->willReturnCallback(static fn (string $a, string $k, int $d) => $d);
		$file = $this->createMock(File::class);
		$file->method('getContent')->willReturn($bytes);
		$file->method('getMimeType')->willReturn('application/pdf');
		$file->method('getName')->willReturn('x.pdf');

		$result = (new DocumentValidationService($this->createMock(LoggerInterface::class), $appConfig))->validate($file);

		$this->assertSame(['pdf-not-tagged'], array_keys($this->accessibility($result)));
	}//end testAStructTreeWithoutMarkedTrueIsNotTagged()

	public function testAnEscalatedCheckFailsTheVerdict(): void {
		$result = $this->validate('untagged-mpdf.pdf', ['default' => ['severities' => ['pdf-not-tagged' => 'blocking']]]);

		$this->assertSame('failed', $result['validationStatus']);
		$this->assertSame('blocking', $this->accessibility($result)['pdf-not-tagged']['severity']);
	}//end testAnEscalatedCheckFailsTheVerdict()

	public function testACheckSwitchedOffIsSkipped(): void {
		$result = $this->validate('untagged-mpdf.pdf', ['default' => ['severities' => ['pdf-not-tagged' => 'off', 'pdf-language-missing' => 'off']]]);

		$this->assertSame([], $this->accessibility($result));
	}//end testACheckSwitchedOffIsSkipped()

	public function testTheChecksRunOnPdfOnlyAndNeverEmbedContent(): void {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(static fn (string $a, string $k, string $d = '') => $d);
		$appConfig->method('getValueInt')->willReturnCallback(static fn (string $a, string $k, int $d) => $d);
		$file = $this->createMock(File::class);
		$file->method('getContent')->willReturn('plain text without tags');
		$file->method('getMimeType')->willReturn('text/plain');
		$file->method('getName')->willReturn('x.txt');
		$result = (new DocumentValidationService($this->createMock(LoggerInterface::class), $appConfig))->validate($file);
		$this->assertSame([], $this->accessibility($result));

		foreach ($this->validate('untagged-mpdf.pdf')['validationFindings'] as $finding) {
			$this->assertStringNotContainsString('Demostad', json_encode($finding));
		}
	}//end testTheChecksRunOnPdfOnlyAndNeverEmbedContent()

	public function testEveryAccessibilityFindingCarriesItsCategoryAndOthersDefaultToDocument(): void {
		foreach ($this->validate('untagged-mpdf.pdf')['validationFindings'] as $finding) {
			$this->assertContains($finding['category'], ['document', 'accessibility']);
			if (str_starts_with($finding['checkId'], 'pdf-not') === true || str_starts_with($finding['checkId'], 'pdf-lang') === true) {
				$this->assertSame('accessibility', $finding['category']);
			}
		}
	}//end testEveryAccessibilityFindingCarriesItsCategoryAndOthersDefaultToDocument()
}//end class
