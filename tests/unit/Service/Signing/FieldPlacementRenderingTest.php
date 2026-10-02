<?php

/**
 * Field placement rendering tests
 *
 * The placed fields are drawn into the PDF before the v2 MAC is computed, so
 * the real verifier accepts the artifact and refuses one whose block moved.
 * Real classes throughout: FieldPlacements, FieldPlacementRenderer,
 * NativeSigningProvider and SigningVerificationService.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use FPDF;
use InvalidArgumentException;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\Signing\FieldPlacementRenderer;
use OCA\Filinq\Service\Signing\FieldPlacements;
use OCA\Filinq\Service\Signing\NativeSigningProvider;
use OCA\Filinq\Service\SigningVerificationService;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PdfReader;

/**
 * Tests for placed fields in the native artifact.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class FieldPlacementRenderingTest extends TestCase {

	private const SECRET = 'placement-secret';

	/**
	 * A three-page PDF, made here so the test depends on no fixture file.
	 *
	 * @return string The PDF bytes.
	 */
	private function threePagePdf(): string {
		$pdf = new FPDF('P', 'pt', 'A4');
		$pdf->SetFont('Helvetica', '', 12);
		foreach (['een', 'twee', 'drie'] as $word) {
			$pdf->AddPage();
			$pdf->Cell(0, 20, 'Pagina ' . $word);
		}

		return $pdf->Output('S');

	}//end threePagePdf()

	/**
	 * The native provider with a configured secret.
	 *
	 * @return NativeSigningProvider The provider.
	 */
	private function provider(): NativeSigningProvider {
		return new NativeSigningProvider(
			logger: $this->createMock(LoggerInterface::class),
			settingsService: $this->createMock(SettingsService::class),
			config: $this->config()
		);

	}//end provider()

	/**
	 * An app config answering the signing secret.
	 *
	 * @return IAppConfig The config.
	 */
	private function config(): IAppConfig {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => $key === 'signing_verification_secret' ? self::SECRET : $default
		);

		return $config;

	}//end config()

	/**
	 * Run the real verifier's signature extraction over signed bytes.
	 *
	 * @param string $signed The signed bytes.
	 *
	 * @return array<int, array<string, mixed>> The signatures.
	 */
	private function verify(string $signed): array {
		$service = new SigningVerificationService(rootFolder: $this->createMock(IRootFolder::class), config: $this->config());
		$method  = (new ReflectionClass($service))->getMethod('extractSignatures');
		$method->setAccessible(true);

		return $method->invoke($service, $signed);

	}//end verify()

	/**
	 * A context with one signature placement for signer 2 on page 3.
	 *
	 * @param float $x Where the block starts, left to right.
	 *
	 * @return array<string, mixed> The context.
	 */
	private function context(float $x = 0.6): array {
		return [
			'signer' => 'Bram Jansen',
			'signers' => ['id-1', 'id-2'],
			'signerLabels' => ['Anna de Vries', 'Bram Jansen'],
			'timestamp' => '2026-09-30T09:00:00+02:00',
			'level' => 'SES',
			'fieldPlacements' => [
				['signerIndex' => 1, 'page' => 3, 'x' => $x, 'y' => 0.8, 'width' => 0.3, 'height' => 0.05, 'type' => 'signature'],
				['signerIndex' => 0, 'page' => 1, 'x' => 0.1, 'y' => 0.9, 'width' => 0.1, 'height' => 0.04, 'type' => 'initials'],
			],
		];

	}//end context()

	/**
	 * A placed field appears in the artifact at its position, and it verifies.
	 *
	 * @return void
	 */
	public function testAPlacedFieldIsDrawnAndTheArtifactVerifies(): void {
		$original = $this->threePagePdf();
		$signed   = $this->provider()->produceSignedArtifact(documentContent: $original, context: $this->context());

		$this->assertNotSame($original, substr($signed, 0, strlen($original)), 'The page content changed: the blocks were drawn');

		$reader = new Fpdi();
		$this->assertSame(3, $reader->setSourceFile(StreamReader::createByString($signed)), 'Every page is kept');

		$signatures = $this->verify(signed: $signed);
		$this->assertSame('verified', $signatures[0]['status']);

		$page3 = $this->pageText(pdf: $signed, page: 3);
		$this->assertStringContainsString('Bram Jansen', $page3);
		$this->assertStringContainsString('ADV', $this->pageText(pdf: $signed, page: 1));

	}//end testAPlacedFieldIsDrawnAndTheArtifactVerifies()

	/**
	 * Moving a rendered block after signing trips verification.
	 *
	 * The block is redrawn elsewhere and the original marker (with its MAC)
	 * is carried over onto the moved version.
	 *
	 * @return void
	 */
	public function testAMovedBlockFailsVerification(): void {
		$original = $this->threePagePdf();
		$signed   = $this->provider()->produceSignedArtifact(documentContent: $original, context: $this->context());
		$moved    = $this->provider()->produceSignedArtifact(documentContent: $original, context: $this->context(x: 0.1));

		$markerAt = strrpos($signed, "\n1 0 obj\n<< /Type /Sig");
		$forged   = substr($moved, 0, (int) strrpos($moved, "\n1 0 obj\n<< /Type /Sig")) . substr($signed, (int) $markerAt);

		$signatures = $this->verify(signed: $forged);
		$this->assertSame('invalid', $signatures[0]['status']);
		$this->assertFalse($signatures[0]['valid']);

	}//end testAMovedBlockFailsVerification()

	/**
	 * The placements join the MAC-covered assertion as data too.
	 *
	 * @return void
	 */
	public function testThePlacementsAreInTheAssertion(): void {
		$signed = $this->provider()->produceSignedArtifact(documentContent: $this->threePagePdf(), context: $this->context());

		preg_match('/\/DocuDesk-Signature\(([^)]*)\)/', $signed, $match);
		$assertion = json_decode((string) base64_decode($match[1]), true);

		$this->assertSame(3, $assertion['fieldPlacements'][0]['page']);
		$this->assertSame('signature', $assertion['fieldPlacements'][0]['type']);

	}//end testThePlacementsAreInTheAssertion()

	/**
	 * Placement-free requests are byte-compatible.
	 *
	 * @return void
	 */
	public function testWithoutPlacementsTheArtifactIsUnchanged(): void {
		$original = $this->threePagePdf();
		$context  = $this->context();
		unset($context['fieldPlacements']);

		$signed = $this->provider()->produceSignedArtifact(documentContent: $original, context: $context);

		$this->assertStringStartsWith($original . "\n1 0 obj\n<< /Type /Sig /SubFilter /DocuDesk.SES >>\n/DocuDesk-Signature(", $signed);
		preg_match('/\/DocuDesk-Signature\(([^)]*)\)/', $signed, $match);
		$this->assertArrayNotHasKey('fieldPlacements', json_decode((string) base64_decode($match[1]), true));
		$this->assertSame('verified', $this->verify(signed: $signed)[0]['status']);

	}//end testWithoutPlacementsTheArtifactIsUnchanged()

	/**
	 * A field placed past the last page is refused, not skipped.
	 *
	 * @return void
	 */
	public function testAFieldPastTheLastPageIsRefused(): void {
		$context = $this->context();
		$context['fieldPlacements'][0]['page'] = 4;

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('page 4 of a document with 3 pages');
		$this->provider()->produceSignedArtifact(documentContent: $this->threePagePdf(), context: $context);

	}//end testAFieldPastTheLastPageIsRefused()

	/**
	 * The renderer counts pages and refuses what is not a PDF.
	 *
	 * @return void
	 */
	public function testThePageCountAndANonPdf(): void {
		$renderer = new FieldPlacementRenderer();
		$this->assertSame(3, $renderer->pageCount(pdf: $this->threePagePdf()));

		$this->expectException(RuntimeException::class);
		$renderer->pageCount(pdf: 'not a pdf');

	}//end testThePageCountAndANonPdf()

	/**
	 * Valid placements come back normalised; every broken rule is refused.
	 *
	 * @return void
	 */
	public function testPlacementRules(): void {
		$rules = new FieldPlacements();
		$good  = ['signerIndex' => 1, 'page' => 2, 'x' => 0, 'y' => 0.5, 'width' => 1, 'height' => 0.5, 'type' => 'date'];

		$this->assertSame([], $rules->normalise(placements: null, signerCount: 2));
		$this->assertSame(
			[['signerIndex' => 1, 'page' => 2, 'x' => 0.0, 'y' => 0.5, 'width' => 1.0, 'height' => 0.5, 'type' => 'date']],
			$rules->normalise(placements: [$good], signerCount: 2)
		);

		$broken = [
			'unknown type' => ['type' => 'stamp'],
			'signer out of range' => ['signerIndex' => 2],
			'negative signer' => ['signerIndex' => -1],
			'page zero' => ['page' => 0],
			'page as text' => ['page' => '2'],
			'x above one' => ['x' => 1.2],
			'off the page' => ['y' => 0.6],
			'no size' => ['width' => 0],
			'a condition key' => ['condition' => 'x'],
		];
		foreach ($broken as $label => $change) {
			try {
				$rules->normalise(placements: [array_merge($good, $change)], signerCount: 2);
				$this->fail('Accepted: ' . $label);
			} catch (InvalidArgumentException $e) {
				$this->assertStringStartsWith('fieldPlacements[0]', $e->getMessage(), $label);
			}
		}

		$this->expectException(InvalidArgumentException::class);
		$rules->normalise(placements: ['a' => $good], signerCount: 2);

	}//end testPlacementRules()

	/**
	 * The text operators on one page, decompressed.
	 *
	 * @param string $pdf  The PDF.
	 * @param int    $page The page.
	 *
	 * @return string The page's content stream.
	 */
	private function pageText(string $pdf, int $page): string {
		$reader = new PdfReader(new PdfParser(StreamReader::createByString($pdf)));

		return $reader->getPage($page)->getContentStream();

	}//end pageText()
}//end class
