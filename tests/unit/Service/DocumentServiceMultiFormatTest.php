<?php

/**
 * One render, several formats, one audit entry that lists them all.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/multi-format-output/tasks.md#task-5.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\Charts\SvgRasterizer;
use OCA\Filinq\Service\Conversion\HtmlToOfficeConverter;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\DocumentService;
use OCA\Filinq\Service\DocumentStorageService;
use OCA\Filinq\Service\GeneratedDocumentLogger;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * DocumentService with the real pipeline, producer and audit logger.
 */
class DocumentServiceMultiFormatTest extends TestCase {

	/** @var array<int, array<string, mixed>> The generatedDocument entries written. */
	private array $logged = [];

	/** @var array<int, string> The file names filed. */
	private array $filed = [];

	/** @var int How often the template was rendered. */
	private int $renders = 0;

	/**
	 * The service; ODT conversion fails when told to.
	 *
	 * @param bool $odtFails Whether the ODT conversion throws.
	 *
	 * @return DocumentService
	 */
	private function service(bool $odtFails = false): DocumentService {
		$templates = $this->createMock(TemplateService::class);
		$templates->method('getTemplate')->willReturn(['id' => 'tmpl-1', 'name' => 'Besluit', 'content' => 'Besluit {{ naam }}', 'namespace' => 'besluiten', 'version' => 2]);

		$resolver = $this->getMockBuilder(DataResolverService::class)->disableOriginalConstructor()->onlyMethods(['resolve'])->getMock();
		$resolver->method('resolve')->willReturn(['data' => ['naam' => 'Jansen'], 'warnings' => [], 'errors' => []]);

		$renderer = $this->createMock(TemplateRenderer::class);
		$renderer->method('renderTemplate')->willReturnCallback(
			function (string $content): string {
				$this->renders++;
				return '<p>Besluit Jansen</p>';
			}
		);
		$renderer->method('getLastRenderWarnings')->willReturn([]);

		$pdf = $this->createMock(PdfService::class);
		$pdf->method('renderPdf')->willReturnCallback(static fn (string $templateContent): string => '%PDF of ' . $templateContent);
		$office = $this->createMock(HtmlToOfficeConverter::class);
		$office->method('isAvailable')->willReturn(true);
		$office->method('toDocx')->willReturnCallback(static fn (string $html): string => 'PK docx of ' . $html);
		$office->method('toOdt')->willReturnCallback(
			static fn (string $html): string => $odtFails === true ? throw new RuntimeException('soffice exited with code 81.') : 'PK odt of ' . $html
		);
		$rasterizer = $this->createMock(SvgRasterizer::class);
		$rasterizer->method('rasterizeInlineSvg')->willReturnCallback(static fn (string $html): array => ['html' => $html, 'warnings' => []]);

		$storage = $this->getMockBuilder(DocumentStorageService::class)->disableOriginalConstructor()->onlyMethods(['store'])->getMock();
		$storage->method('store')->willReturnCallback(
			function (string $userId, string $targetPath, string $filename, string $content): array {
				$this->filed[$filename] = $content;
				return ['fileId' => 100 + count($this->filed), 'path' => '/' . $userId . '/files/' . $targetPath . '/' . $filename, 'name' => $filename, 'size' => strlen($content)];
			}
		);

		$objects = $this->createMock(ObjectService::class);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object): array {
				$this->logged[] = $object;
				return $object;
			}
		);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($objects);
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn(['openregister']);
		$objectResolver = new DocumentObjectServiceResolver($container, $appManager);

		return new DocumentService(
			$templates,
			$resolver,
			new DocumentRenderPipeline($renderer, $pdf, $objectResolver, new NullLogger(), $rasterizer, null, $office),
			$storage,
			new GeneratedDocumentLogger($objectResolver, new NullLogger()),
			$container,
			$this->createMock(IJobList::class),
			new NullLogger()
		);
	}

	/**
	 * Validate a generatedDocument entry against the register's own schema.
	 *
	 * @param array $entry The entry.
	 *
	 * @return void
	 */
	private function assertValidGeneratedDocument(array $entry): void {
		$descriptor = json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json'), true);
		$schema = $descriptor['components']['schemas']['generatedDocument'];
		$properties = [];
		foreach ($schema['properties'] as $name => $property) {
			unset($property['required'], $property['visible'], $property['order'], $property['facetable']);
			$properties[$name] = $property;
		}

		// Null means "not set" to OpenRegister; the audit logger writes it for fields a run has no value for.
		$entry = array_filter($entry, static fn ($value): bool => $value !== null);
		$result = (new Validator())->validate(
			json_decode((string) json_encode($entry)),
			(string) json_encode(['type' => 'object', 'required' => $schema['required'], 'properties' => $properties, 'additionalProperties' => false])
		);
		$message = '';
		if ($result->isValid() === false) {
			$message = (string) json_encode((new ErrorFormatter())->format($result->error()));
		}

		$this->assertTrue($result->isValid(), $message);
	}

	public function testPdfAndDocxFromOneRender(): void {
		$result = $this->service()->generateDocument('tmpl-1', [], ['formats' => ['pdf', 'docx', 'pdf'], 'userId' => 'clerk', 'filename' => 'besluit']);

		$this->assertSame(1, $this->renders, 'The template is rendered once.');
		$this->assertSame(['pdf', 'docx'], array_column($result['outputs'], 'format'));
		$this->assertSame(['generated', 'generated'], array_column($result['outputs'], 'status'));
		$this->assertSame(['besluit.pdf', 'besluit.docx'], array_keys($this->filed));
		$this->assertSame('%PDF of <p>Besluit Jansen</p>', $this->filed['besluit.pdf']);
		$this->assertSame('PK docx of <p>Besluit Jansen</p>', $this->filed['besluit.docx'], 'Both files come from the same HTML.');
		$this->assertSame('/clerk/files/DocuDesk/besluiten/besluit.docx', $result['outputs'][1]['path']);
	}

	public function testPartialFormatFailure(): void {
		$result = $this->service(odtFails: true)->generateDocument('tmpl-1', [], ['formats' => ['pdf', 'odf'], 'userId' => 'clerk']);

		$this->assertSame('generated', $result['outputs'][0]['status']);
		$this->assertSame('failed', $result['outputs'][1]['status']);
		$this->assertSame('soffice exited with code 81.', $result['outputs'][1]['error']);
		$this->assertNull($result['outputs'][1]['fileId']);
		$this->assertSame(['document.pdf'], array_keys($this->filed));
	}

	public function testMultiFormatAuditOutputs(): void {
		$this->service(odtFails: true)->generateDocument('tmpl-1', [], ['formats' => ['odf', 'pdf'], 'userId' => 'clerk']);

		$this->assertCount(1, $this->logged, 'One entry per render, not per format.');
		$entry = $this->logged[0];
		$this->assertSame('odf', $entry['format'], 'The scalar format is the first one asked for.');
		$this->assertSame(
			[
				['format' => 'odf', 'status' => 'failed', 'error' => 'soffice exited with code 81.'],
				['format' => 'pdf', 'fileId' => 101, 'status' => 'generated'],
			],
			$entry['outputs']
		);
		$this->assertSame(101, $entry['fileId'], 'The first filed output stands for the document.');
		$this->assertValidGeneratedDocument($entry);
	}

	public function testASingleFormatRequestKeepsItsShape(): void {
		$result = $this->service()->generateDocument('tmpl-1', [], ['format' => 'docx']);

		$this->assertSame('PK docx of <p>Besluit Jansen</p>', $result['content']);
		$this->assertArrayNotHasKey('outputs', $result);
		$this->assertArrayNotHasKey('outputs', $this->logged[0]);
		$this->assertSame([], $this->filed, 'Mode return files nothing.');
		$this->assertValidGeneratedDocument($this->logged[0]);
	}

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function refusedRequests(): array {
		return [
			'format and formats' => [['format' => 'pdf', 'formats' => ['docx']]],
			'empty list' => [['formats' => []]],
			'not a list' => [['formats' => 'pdf']],
			'unknown format' => [['formats' => ['pdf', 'xlsx']]],
		];
	}

	/**
	 * @dataProvider refusedRequests
	 *
	 * @param array<string, mixed> $options The request options.
	 *
	 * @return void
	 */
	public function testAMalformedFormatsRequestIsA400AndNothingIsMade(array $options): void {
		try {
			$this->service()->generateDocument('tmpl-1', [], $options + ['userId' => 'clerk']);
			$this->fail('Refused request went through.');
		} catch (Exception $e) {
			$this->assertSame(400, $e->getCode());
		}

		$this->assertSame(0, $this->renders);
		$this->assertSame([], $this->filed);
	}
}//end class
