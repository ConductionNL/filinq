<?php

/**
 * Unit tests for SeparatorSheetService and ScanProfileService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\ScanProfileService;
use OCA\Filinq\Service\SeparatorSheetService;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Asserts that what filinq prints is what filinq reads back, and that a page
 * carrying anything else is an ordinary page rather than a separator.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SeparatorSheetServiceTest extends TestCase {

	/**
	 * The sheet service over a renderer that echoes the HTML it is given.
	 *
	 * @param string|null $rendered What the renderer produces, or null to make it throw.
	 *
	 * @return SeparatorSheetService The service under test.
	 */
	private function sheets(?string $rendered = 'PDF'): SeparatorSheetService {
		$pdf = $this->createMock(PdfService::class);
		if ($rendered === null) {
			$pdf->method('generatePdfFromHtml')->willThrowException(new RuntimeException('mpdf is unhappy'));
		} else {
			$pdf->method('generatePdfFromHtml')->willReturnCallback(
				static fn (string $html): string => $html
			);
		}

		return new SeparatorSheetService($pdf);

	}//end sheets()

	/**
	 * What is printed is what is read back.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testThePayloadPrintedIsThePayloadRead(): void {
		$service = $this->sheets();

		$withCase = $service->payload(profileId: 'postkamer', caseNumber: '2026-0042');
		$withoutCase = $service->payload(profileId: 'postkamer');

		$this->assertSame('filinq:sep:v1:postkamer:2026-0042', $withCase);
		$this->assertSame('filinq:sep:v1:postkamer', $withoutCase);
		$this->assertSame('2026-0042', $service->readPayload(payload: $withCase));
		$this->assertSame('', $service->readPayload(payload: $withoutCase));

	}//end testThePayloadPrintedIsThePayloadRead()

	/**
	 * A payload read off a page with other text around it is still read.
	 *
	 * OCR of a scanned sheet returns the heading and the instruction line too,
	 * so a reader that demanded the payload be the WHOLE text would see no
	 * separators at all.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testThePayloadIsFoundAmongTheRestOfTheSheet(): void {
		$page = "Scheidingsvel voor zaak 2026-0042\nLeg dit vel tussen twee documenten.\n"
			. "filinq:sep:v1:postkamer:2026-0042\n";

		$this->assertSame('2026-0042', $this->sheets()->readPayload(payload: $page));

	}//end testThePayloadIsFoundAmongTheRestOfTheSheet()

	/**
	 * An ordinary page is not a separator, however much text it has.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testAnOrdinaryPageIsNotASeparator(): void {
		$service = $this->sheets();

		$this->assertNull($service->readPayload(payload: 'Geachte heer, mevrouw,'));
		$this->assertNull($service->readPayload(payload: ''));
		$this->assertNull(
			$service->readPayload(payload: 'filinq:sep:v0:postkamer'),
			'A sheet printed by another version is not silently read as this one.'
		);

	}//end testAnOrdinaryPageIsNotASeparator()

	/**
	 * Three case numbers make three sheets, each carrying its own number twice.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testThreeCaseNumbersMakeThreeSheets(): void {
		$html = $this->sheets()->render(
			profileId: 'postkamer',
			caseNumbers: ['2026-0001', '2026-0002', '2026-0003']
		);

		$this->assertSame(2, substr_count($html, '<pagebreak />'), 'Three sheets means two breaks.');
		foreach (['2026-0001', '2026-0002', '2026-0003'] as $number) {
			$this->assertStringContainsString(
				'filinq:sep:v1:postkamer:' . $number,
				$html,
				'Every sheet carries its own payload.'
			);
		}

		$this->assertStringContainsString('type="QR"', $html, 'And a QR code, drawn locally by the renderer.');

	}//end testThreeCaseNumbersMakeThreeSheets()

	/**
	 * A print with no case numbers still produces one sheet.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testAPrintWithoutCaseNumbersStillProducesOneSheet(): void {
		$html = $this->sheets()->render(profileId: 'postkamer');

		$this->assertStringNotContainsString('<pagebreak />', $html);
		$this->assertStringContainsString('filinq:sep:v1:postkamer', $html);

	}//end testAPrintWithoutCaseNumbersStillProducesOneSheet()

	/**
	 * Sheets without a profile are refused: the payload would name nothing.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testSheetsWithoutAProfileAreRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->sheets()->render(profileId: '  ');

	}//end testSheetsWithoutAProfileAreRefused()

	/**
	 * A renderer that fails says so rather than producing a blank sheet.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testARendererThatFailsSaysSo(): void {
		$this->expectException(RuntimeException::class);
		$this->sheets(rendered: null)->render(profileId: 'postkamer');

	}//end testARendererThatFailsSaysSo()

	/**
	 * The profile watching the deepest matching folder wins.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testTheDeepestWatchedFolderWins(): void {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn(
			json_encode(
				[
					['id' => 'algemeen', 'watchedFolder' => 'Scans'],
					['id' => 'bezwaar', 'watchedFolder' => 'Scans/bezwaar'],
				]
			)
		);

		$profiles = new ScanProfileService($config, $this->createMock(LoggerInterface::class));

		$this->assertSame('bezwaar', $profiles->forPath(path: '/Scans/bezwaar/batch.pdf')['id']);
		$this->assertSame('algemeen', $profiles->forPath(path: '/Scans/batch.pdf')['id']);
		$this->assertNull($profiles->forPath(path: '/Documenten/batch.pdf'));

	}//end testTheDeepestWatchedFolderWins()

	/**
	 * A profile without an id or a folder is refused, and a bad mode too.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testAProfileMustNameAnIdAFolderAndAKnownMode(): void {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn('');
		$profiles = new ScanProfileService($config, $this->createMock(LoggerInterface::class));

		try {
			$profiles->declare(profiles: [['id' => 'postkamer']]);
			$this->fail('A profile must name the folder its scanner writes to.');
		} catch (RuntimeException $refusal) {
			$this->assertStringContainsString('folder', $refusal->getMessage());
		}

		try {
			$profiles->declare(
				profiles: [['id' => 'postkamer', 'watchedFolder' => 'Scans', 'separatorMode' => 'magie']]
			);
			$this->fail('A profile cuts on qr or on blankPage.');
		} catch (RuntimeException $refusal) {
			$this->assertStringContainsString('blankPage', $refusal->getMessage());
		}

	}//end testAProfileMustNameAnIdAFolderAndAKnownMode()

	/**
	 * Profiles that are not valid JSON watch nothing, loudly.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testUnreadableProfilesWatchNothing(): void {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn('{ this is not json');

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('error');

		$profiles = new ScanProfileService($config, $logger);

		$this->assertSame([], $profiles->all());

	}//end testUnreadableProfilesWatchNothing()
}//end class
