<?php

/**
 * OfficeTemplateRenderer tests
 *
 * The office render path over the real filler, fragment resolver and
 * converter (LibreOffice doubled): filled values, missing-data warnings,
 * fragments with their own tags, the missing-fragment marker, the field
 * mapping, XML escaping, repeating blocks, and the Twig fragment pass.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\OfficeTemplate
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

namespace OCA\Filinq\Tests\Unit\Service\OfficeTemplate;

require_once __DIR__ . '/OfficeTemplateDoubles.php';

use Exception;
use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCA\Filinq\Service\Conversion\PhpWordIo;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\OfficeTemplate\DataPath;
use OCA\Filinq\Service\OfficeTemplate\FragmentResolver;
use OCA\Filinq\Service\OfficeTemplate\OfficeConverter;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateFiller;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateRenderer;
use OCA\Filinq\Service\OfficeTemplate\TemplateObjectRepository;
use OCA\Filinq\Service\PdfService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\OfficeTemplate\OfficeTemplateRenderer
 * @covers \OCA\Filinq\Service\OfficeTemplate\OfficeTemplateFiller
 * @covers \OCA\Filinq\Service\OfficeTemplate\FragmentResolver
 * @covers \OCA\Filinq\Service\OfficeTemplate\DataPath
 * @covers \OCA\Filinq\Service\OfficeTemplate\OfficeConverter
 */
class OfficeTemplateRendererTest extends TestCase {

	private OfficeObjectStore $objects;

	private MemorySourceStore $store;

	private bool $libreOffice = true;

	/**
	 * LibreOffice calls: [method, from, to].
	 *
	 * @var array<int, array<int, string>>
	 */
	private array $calls = [];

	protected function setUp(): void {
		$this->objects = new OfficeObjectStore();
		$this->store = new MemorySourceStore();
		$this->libreOffice = true;
		$this->calls = [];
		$this->objects->saveObject(
			object: ['name' => 'Ondertekening burgemeester', 'slug' => 'ondertekening-burgemeester', 'namespace' => 'filinq', 'content' => "Hoogachtend,\nde burgemeester van \${gemeente},"],
			register: 'filinq',
			schema: 'textFragment'
		);
	}

	private function renderer(): OfficeTemplateRenderer {
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);
		$libreOffice = $this->createMock(LibreOfficeHeadlessBackend::class);
		$libreOffice->method('isAvailable')->willReturnCallback(fn (): bool => $this->libreOffice);
		$libreOffice->method('convertOffice')->willReturnCallback(function (string $bytes, string $fromExtension, string $toExtension): string {
			$this->calls[] = ['convertOffice', $fromExtension, $toExtension];
			if ($this->libreOffice === false) {
				throw new ConversionFailedException(message: LibreOfficeHeadlessBackend::UNAVAILABLE_REASON, attempts: [], code: 503);
			}

			return '<html><body>' . htmlspecialchars(OfficeFixtures::text($bytes)) . '</body></html>';
		});
		$libreOffice->method('convertTagged')->willReturnCallback(function (string $bytes, string $extension, bool $pdfa): string {
			$this->calls[] = ['convertTagged', $extension, 'pdf'];

			return '%PDF-1.7 ' . OfficeFixtures::text($bytes);
		});
		$pdf = $this->createMock(PdfService::class);
		$pdf->method('generatePdfFromHtml')->willReturnCallback(static fn (string $html): string => '%PDF-mpdf ' . strip_tags($html));
		$dataPath = new DataPath();

		return new OfficeTemplateRenderer(
			store: $this->store,
			filler: new OfficeTemplateFiller(dataPath: $dataPath),
			converter: new OfficeConverter(libreOffice: $libreOffice, phpWord: new PhpWordIo(), pdfService: $pdf),
			fragments: new FragmentResolver(repository: new TemplateObjectRepository(objectResolver: $resolver), dataPath: $dataPath)
		);
	}

	private function template(string $fixture, array $extra = []): array {
		$tags = [
			'beschikking-parkeervergunning.docx' => ['aanvrager.naam', 'aanvrager.adres', 'besluit.datum', 'fragment:ondertekening-burgemeester'],
			'brief-ontvangstbevestiging.docx' => ['naam_aanvrager', 'zaaknummer', 'aanvraagr.naam'],
			'factuur-regels.docx' => ['regel', 'regel.omschrijving', 'regel.bedrag', 'totaal', 'opmerking'],
		][$fixture];

		return $extra + [
			'name' => 'T', 'namespace' => 'filinq', 'templateType' => 'office',
			'sourceFileId' => $this->store->put(bytes: OfficeFixtures::bytes($fixture), extension: 'docx'),
			'mergeFields' => $tags,
		];
	}

	private const DATA = [
		'aanvrager' => ['naam' => 'A. de Vries & Zn <BV>', 'adres' => 'Dorpsstraat 1'],
		'besluit' => ['datum' => '2026-10-02'],
		'gemeente' => 'Demostad',
	];

	public function testOfficeTemplateGeneratesAPdfViaTheCascade(): void {
		$result = $this->renderer()->render(template: $this->template('beschikking-parkeervergunning.docx'), data: self::DATA, format: 'pdf');

		$this->assertStringStartsWith('%PDF-1.7', $result['content']);
		$this->assertSame(['convertTagged', 'docx', 'pdf'], $this->calls[0]);
		$this->assertStringContainsString('A. de Vries & Zn <BV>', $result['content']);
		$this->assertStringContainsString('2026-10-02', $result['content']);
		$this->assertSame([], $result['warnings']);
	}

	public function testDocxOutputIsTheFilledSourceWithEscapedValues(): void {
		$docx = $this->renderer()->render(template: $this->template('beschikking-parkeervergunning.docx'), data: self::DATA, format: 'docx')['content'];

		$text = OfficeFixtures::text($docx);
		$this->assertStringContainsString('Aan: A. de Vries & Zn <BV>', $text);
		$this->assertStringNotContainsString('${', $text);
		$this->assertSame([], $this->calls, 'docx needs no conversion');
		$xml = (new \ZipArchive());
		$this->assertNotFalse(simplexml_load_string($this->documentXml($docx)), 'the filled document.xml is still valid XML');
	}

	public function testMissingDataYieldsAWarningNotSilentLoss(): void {
		$data = self::DATA;
		unset($data['besluit']);
		$result = $this->renderer()->render(template: $this->template('beschikking-parkeervergunning.docx'), data: $data, format: 'docx');

		$this->assertStringContainsString('Datum besluit: ' . "\n", OfficeFixtures::text($result['content']) . "\n");
		$this->assertContains('No value for tag besluit.datum; it is left empty.', $result['warnings']);
	}

	public function testFragmentContentIsRenderedIntoAGeneratedDocument(): void {
		$text = OfficeFixtures::text($this->renderer()->render(template: $this->template('beschikking-parkeervergunning.docx'), data: self::DATA, format: 'docx')['content']);

		$this->assertStringContainsString('Hoogachtend,', $text);
		$this->assertStringContainsString('de burgemeester van Demostad,', $text, 'the fragment\'s own tag is filled from the same data');
	}

	public function testMissingFragmentIsVisibleAndWarned(): void {
		$this->objects->rows = [];
		$result = $this->renderer()->render(template: $this->template('beschikking-parkeervergunning.docx'), data: self::DATA, format: 'docx');

		$this->assertStringContainsString('[ontbrekende bouwsteen: ondertekening-burgemeester]', OfficeFixtures::text($result['content']));
		$this->assertStringContainsString('ondertekening-burgemeester', implode(' ', $result['warnings']));
	}

	public function testAMappedTagIsFilledFromItsProperty(): void {
		$template = $this->template('brief-ontvangstbevestiging.docx', ['fieldMap' => ['naam_aanvrager' => 'aanvrager.naam', 'aanvraagr.naam' => 'aanvrager.naam']]);
		$text = OfficeFixtures::text($this->renderer()->render(template: $template, data: self::DATA + ['zaaknummer' => 'Z-2026-1'], format: 'docx')['content']);

		$this->assertStringContainsString('Beste A. de Vries & Zn <BV>,', $text);
		$this->assertStringContainsString('Zaak Z-2026-1', $text);
	}

	public function testARepeatingBlockIsClonedPerRow(): void {
		$data = ['regel' => [['omschrijving' => 'Leges', 'bedrag' => '45,00'], ['omschrijving' => 'Porto', 'bedrag' => '1,20']], 'totaal' => '46,20', 'opmerking' => 'n.v.t.'];
		$text = OfficeFixtures::text($this->renderer()->render(template: $this->template('factuur-regels.docx'), data: $data, format: 'docx')['content']);

		$this->assertStringContainsString('Leges: 45,00', $text);
		$this->assertStringContainsString('Porto: 1,20', $text);
		$this->assertStringContainsString('Totaal 46,20', $text);
		$this->assertStringNotContainsString('${', $text);
	}

	public function testOfficeHtmlRequiresLibreOffice(): void {
		$this->libreOffice = false;
		try {
			$this->renderer()->render(template: $this->template('beschikking-parkeervergunning.docx'), data: self::DATA, format: 'html');
			$this->fail('html without LibreOffice');
		} catch (Exception $e) {
			$this->assertSame(503, $e->getCode());
			$this->assertSame(LibreOfficeHeadlessBackend::UNAVAILABLE_REASON, $e->getMessage());
		}
	}

	public function testOfficeHtmlComesFromTheFilledDocx(): void {
		$result = $this->renderer()->render(template: $this->template('beschikking-parkeervergunning.docx'), data: self::DATA, format: 'html');

		$this->assertSame(['convertOffice', 'docx', 'html'], $this->calls[0]);
		$this->assertStringContainsString('A. de Vries &amp; Zn', $result['content']);
		$this->assertSame($result['content'], $result['html']);
	}

	public function testAPdfStillComesOutWithoutLibreOffice(): void {
		$this->libreOffice = false;
		$result = $this->renderer()->render(template: $this->template('beschikking-parkeervergunning.docx'), data: self::DATA, format: 'pdf');

		$this->assertStringStartsWith('%PDF-mpdf', $result['content']);
		$this->assertStringContainsString('Dorpsstraat 1', $result['content']);
	}

	public function testOfficeTemplatePreviewShowsTheData(): void {
		$preview = $this->renderer()->preview(template: $this->template('beschikking-parkeervergunning.docx'), data: ['aanvrager' => ['naam' => 'A. de Vries']]);

		$this->assertStringContainsString('A. de Vries', $preview['html']);
	}

	public function testATwigTemplateGetsItsFragmentsAroundTheTwigRun(): void {
		$renderer = $this->renderer();
		$prepared = $renderer->prepareTwig(template: ['namespace' => 'filinq', 'content' => '<p>{{ x }}</p>${fragment:ondertekening-burgemeester}${fragment:bestaat-niet}'], data: self::DATA);

		$this->assertStringNotContainsString('${fragment:', $prepared['content']);
		$this->assertStringNotContainsString('Hoogachtend', $prepared['content'], 'Twig never sees fragment text');
		$html = $renderer->finishTwig(html: $prepared['content'], tokens: $prepared['tokens']);
		$this->assertStringContainsString("Hoogachtend,<br />\nde burgemeester van Demostad,", $html);
		$this->assertStringContainsString('[ontbrekende bouwsteen: bestaat-niet]', $html);
		$this->assertStringContainsString('bestaat-niet', implode(' ', $prepared['warnings']));
	}

	private function documentXml(string $docx): string {
		$path = tempnam(sys_get_temp_dir(), 'dx_');
		file_put_contents($path, $docx);
		$zip = new \ZipArchive();
		$zip->open($path);
		$xml = (string) $zip->getFromName('word/document.xml');
		$zip->close();
		unlink($path);

		return $xml;
	}
}
