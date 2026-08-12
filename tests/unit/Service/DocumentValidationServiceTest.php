<?php

/**
 * Document Validation Service Test
 *
 * @category  Test
 * @package   OCA\DocuDesk\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-validation-checks/specs/document-validation-checks/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\DocumentValidationService;
use OCP\Files\File;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for DocumentValidationService.
 *
 * @category Test
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class DocumentValidationServiceTest extends TestCase {
	/**
	 * Logger mock.
	 *
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $logger;

	/**
	 * App config mock.
	 *
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $appConfig;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		// Default: no profiles configured, default text-layer threshold.
		$this->appConfig->method('getValueString')->willReturn('');
		$this->appConfig->method('getValueInt')->willReturnCallback(
			static fn (string $a, string $k, int $d) => $d
		);

	}//end setUp()

	/**
	 * Build the service.
	 *
	 * @return DocumentValidationService The service.
	 */
	private function service(): DocumentValidationService {
		return new DocumentValidationService($this->logger, $this->appConfig);
	}//end service()

	/**
	 * Build a file mock.
	 *
	 * @param string $content Content bytes.
	 * @param string $mime Mime type.
	 * @param string $name File name.
	 *
	 * @return File|MockObject The file mock.
	 */
	private function file(string $content, string $mime, string $name): File|MockObject {
		$file = $this->createMock(File::class);
		$file->method('getContent')->willReturn($content);
		$file->method('getMimeType')->willReturn($mime);
		$file->method('getName')->willReturn($name);
		return $file;
	}//end file()

	/**
	 * Configure a profile JSON on the app config.
	 *
	 * @param array<string, mixed> $profiles Profiles map.
	 *
	 * @return void
	 */
	private function withProfiles(array $profiles): void {
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appConfig->method('getValueInt')->willReturnCallback(
			static fn (string $a, string $k, int $d) => $d
		);
		$this->appConfig->method('getValueString')->willReturn(json_encode($profiles));

	}//end withProfiles()

	/**
	 * A clean, allowed, text-bearing PDF passes all checks.
	 *
	 * @return void
	 */
	public function testCleanDocumentPasses(): void {
		// A PDF with one page and plenty of text operators.
		$pdf = "%PDF-1.7\n/Type /Page \n" . str_repeat('(text) Tj ', 40);
		$file = $this->file($pdf, 'application/pdf', 'clean.pdf');
		$result = $this->service()->validate($file, [], 'default');

		$this->assertSame('passed', $result['validationStatus']);
		$this->assertSame([], $result['validationFindings']);

	}//end testCleanDocumentPasses()

	/**
	 * Encrypted PDF fires pdf-encrypted (warning by default).
	 *
	 * @return void
	 */
	public function testEncryptedPdfDetected(): void {
		$pdf = "%PDF-1.7\n/Type /Page \n/Encrypt 5 0 R\n" . str_repeat('(t) Tj ', 40);
		$file = $this->file($pdf, 'application/pdf', 'secret.pdf');
		$result = $this->service()->validate($file, [], 'default');

		$ids = array_column($result['validationFindings'], 'checkId');
		$this->assertContains('pdf-encrypted', $ids);
		$this->assertSame('warnings', $result['validationStatus']);

	}//end testEncryptedPdfDetected()

	/**
	 * Scan-only PDF triggers text-layer-missing with an OCR suggestion.
	 *
	 * @return void
	 */
	public function testScanOnlyPdfTriggersTextLayerCheck(): void {
		// A PDF with pages but no text operators.
		$pdf = "%PDF-1.7\n/Type /Page \n/Type /Page \n%%EOF";
		$file = $this->file($pdf, 'application/pdf', 'scan.pdf');
		$result = $this->service()->validate($file, [], 'default');

		$byId = [];
		foreach ($result['validationFindings'] as $f) {
			$byId[$f['checkId']] = $f;
		}

		$this->assertArrayHasKey('text-layer-missing', $byId);
		$this->assertSame('ocr', $byId['text-layer-missing']['suggestedAction']);

	}//end testScanOnlyPdfTriggersTextLayerCheck()

	/**
	 * Disallowed mime fires format-not-allowed.
	 *
	 * @return void
	 */
	public function testFormatNotAllowed(): void {
		$file = $this->file('some bytes', 'application/x-zip', 'archive.zip');
		$result = $this->service()->validate($file, [], 'default');

		$this->assertContains('format-not-allowed', array_column($result['validationFindings'], 'checkId'));

	}//end testFormatNotAllowed()

	/**
	 * Extension/mime mismatch is detected.
	 *
	 * @return void
	 */
	public function testExtensionMimeMismatch(): void {
		// Named .pdf but the detected mime is plain text.
		$file = $this->file('hello', 'text/plain', 'invoice.pdf');
		$result = $this->service()->validate($file, [], 'default');

		$this->assertContains('extension-mime-mismatch', array_column($result['validationFindings'], 'checkId'));

	}//end testExtensionMimeMismatch()

	/**
	 * Missing required metadata names the field.
	 *
	 * @return void
	 */
	public function testMetadataIncompleteNamesField(): void {
		$this->withProfiles([
			'default' => [
				'requiredFields' => ['documentType', 'language'],
				'allowedMimes' => ['text/plain'],
			],
		]);

		$file = $this->file('hello world', 'text/plain', 'note.txt');
		$result = $this->service()->validate($file, ['documentType' => 'memo', 'language' => ''], 'default');

		$missing = array_values(array_filter(
			$result['validationFindings'],
			static fn ($f) => $f['checkId'] === 'metadata-incomplete'
		));
		$this->assertCount(1, $missing);
		$this->assertSame('language', $missing[0]['field']);

	}//end testMetadataIncompleteNamesField()

	/**
	 * Per-type profile resolution; other types are not checked against its fields.
	 *
	 * @return void
	 */
	public function testPerTypeProfileResolution(): void {
		$this->withProfiles([
			'factuur' => ['requiredFields' => ['invoiceNumber'], 'allowedMimes' => ['text/plain']],
			'default' => ['requiredFields' => [], 'allowedMimes' => ['text/plain']],
		]);

		$file = $this->file('x', 'text/plain', 'a.txt');

		$factuur = $this->service()->validate($file, [], 'factuur');
		$this->assertContains('metadata-incomplete', array_column($factuur['validationFindings'], 'checkId'));

		$other = $this->service()->validate($file, [], 'brief');
		$this->assertNotContains('metadata-incomplete', array_column($other['validationFindings'], 'checkId'));

	}//end testPerTypeProfileResolution()

	/**
	 * Unknown type falls back to the default profile.
	 *
	 * @return void
	 */
	public function testUnknownTypeFallsBackToDefault(): void {
		$this->withProfiles([
			'default' => ['requiredFields' => ['title'], 'allowedMimes' => ['text/plain']],
		]);

		$file = $this->file('x', 'text/plain', 'a.txt');
		$result = $this->service()->validate($file, [], 'no-such-type');

		$missing = array_values(array_filter(
			$result['validationFindings'],
			static fn ($f) => $f['checkId'] === 'metadata-incomplete'
		));
		$this->assertSame('title', $missing[0]['field']);

	}//end testUnknownTypeFallsBackToDefault()

	/**
	 * A check set to off is skipped.
	 *
	 * @return void
	 */
	public function testCheckOffIsSkipped(): void {
		$this->withProfiles([
			'default' => [
				'allowedMimes' => ['text/plain'],
				'severities' => ['extension-mime-mismatch' => 'off'],
			],
		]);

		$file = $this->file('hello', 'text/plain', 'invoice.pdf');
		$result = $this->service()->validate($file, [], 'default');

		$this->assertNotContains('extension-mime-mismatch', array_column($result['validationFindings'], 'checkId'));

	}//end testCheckOffIsSkipped()

	/**
	 * Blocking severity aggregates to a failed verdict.
	 *
	 * @return void
	 */
	public function testBlockingAggregatesToFailed(): void {
		$this->withProfiles([
			'default' => [
				'allowedMimes' => ['text/plain'],
				'severities' => ['format-not-allowed' => 'blocking'],
			],
		]);

		$file = $this->file('bytes', 'application/x-zip', 'a.zip');
		$result = $this->service()->validate($file, [], 'default');

		$this->assertSame('failed', $result['validationStatus']);

	}//end testBlockingAggregatesToFailed()

	/**
	 * Default deployment (no profiles configured) never produces blocking.
	 *
	 * @return void
	 */
	public function testDefaultDeploymentNeverBlocks(): void {
		// Broken-ish file: disallowed mime + mismatch. Default = warn only.
		$file = $this->file('x', 'application/x-zip', 'a.pdf');
		$result = $this->service()->validate($file, [], '');

		foreach ($result['validationFindings'] as $f) {
			$this->assertNotSame('blocking', $f['severity']);
		}
		$this->assertNotSame('failed', $result['validationStatus']);

	}//end testDefaultDeploymentNeverBlocks()

	/**
	 * Findings never embed document content.
	 *
	 * @return void
	 */
	public function testFindingsNeverEmbedContent(): void {
		$secret = 'TOPSECRET-PII-VALUE-12345';
		$file = $this->file($secret, 'application/x-zip', 'leak.zip');
		$result = $this->service()->validate($file, [], 'default');

		$json = json_encode($result['validationFindings']);
		$this->assertStringNotContainsString($secret, (string)$json);
		foreach ($result['validationFindings'] as $f) {
			$this->assertArrayHasKey('checkId', $f);
			$this->assertArrayHasKey('severity', $f);
			$this->assertArrayHasKey('message', $f);
		}

	}//end testFindingsNeverEmbedContent()

	/**
	 * Verdict aggregation: one warning, no blocking → warnings.
	 *
	 * @return void
	 */
	public function testVerdictAggregationWarnings(): void {
		$status = $this->service()->aggregate([
			['checkId' => 'pdf-encrypted', 'severity' => 'warning'],
		]);
		$this->assertSame('warnings', $status);

	}//end testVerdictAggregationWarnings()
}//end class
