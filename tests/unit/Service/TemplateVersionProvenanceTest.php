<?php

/**
 * A generated document names the template version it came from.
 *
 * Runs the real TemplateService, TemplateVersionService, DocumentService,
 * render pipeline and audit logger over an in-memory OpenRegister, and checks
 * every generatedDocument entry against the register's own schema.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/template-version-provenance/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use DateTimeImmutable;
use Exception;
use FPDF;
use OCA\Filinq\Service\Charts\SvgRasterizer;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\DocumentService;
use OCA\Filinq\Service\DocumentStorageService;
use OCA\Filinq\Service\GeneratedDocumentLogger;
use OCA\Filinq\Service\OpenRegisterResolver;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\TemplateVersionService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\IUserSession;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Template version provenance over the real services.
 */
class TemplateVersionProvenanceTest extends TestCase {

	/** @var array<int, array<string, mixed>> The generatedDocument entries written. */
	private array $logged = [];

	/** @var array<int, string> The template content handed to the renderer. */
	private array $rendered = [];

	/** @var int How many files were stored. */
	private int $stored = 0;

	/** @var int How many writes reached the template schema. */
	private int $templateWrites = 0;

	/** @var array<string, mixed> The last object written to the template schema. */
	private array $templateWritten = [];

	/**
	 * The stored snapshots: each holds the content version N had before it was replaced.
	 *
	 * Version 1 became head when the template was created (1 January), version 2
	 * on 1 March, version 3 on 1 June, version 4 on 1 August. The head is version 4.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $snapshots = [];

	/**
	 * Set up the version chain.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$ended = [1 => '2026-03-01T00:00:00+00:00', 2 => '2026-06-01T00:00:00+00:00', 3 => '2026-08-01T00:00:00+00:00'];
		foreach ($ended as $number => $created) {
			$this->snapshots[] = [
				'id' => 'ver-' . $number,
				'templateId' => 'tmpl-1',
				'version' => $number,
				'content' => 'Versie ' . $number . ' {{ naam }}',
				'name' => 'Besluit v' . $number,
				'format' => 'A4',
				'orientation' => 'P',
				'editor' => 'admin',
				'@self' => ['id' => 'ver-' . $number, 'created' => $created],
			];
		}
	}

	/**
	 * The real services over an in-memory OpenRegister.
	 *
	 * @param bool $chainReadable False when the templateVersion schema is not configured.
	 *
	 * @return array{documents: DocumentService, templates: TemplateService, versions: TemplateVersionService}
	 */
	private function services(bool $chainReadable = true): array {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('find')->willReturnCallback(
			fn (string $id, ?string $register = null, ?string $schema = null): array => [
				'id' => 'tmpl-1',
				'name' => 'Besluit',
				'content' => 'Versie 4 {{ naam }}',
				'namespace' => 'besluiten',
				'@self' => ['id' => 'tmpl-1', 'version' => '0.0.4', 'created' => '2026-01-01T00:00:00+00:00'],
			]
		);
		$objects->method('buildSearchQuery')->willReturnCallback(static fn (array $requestParams): array => $requestParams);
		$objects->method('searchObjectsPaginated')->willReturnCallback(
			function (array $query): array {
				$hits = array_values(
					array_filter(
						$this->snapshots,
						static fn (array $row): bool => $row['templateId'] === $query['templateId']
							&& (isset($query['version']) === false || $row['version'] === (int) $query['version'])
					)
				);
				usort($hits, static fn (array $a, array $b): int => $b['version'] <=> $a['version']);
				return ['results' => array_slice($hits, (int) ($query['_offset'] ?? 0), (int) ($query['_limit'] ?? 20)), 'total' => count($hits)];
			}
		);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object, $register = null, $schema = null): array {
				if ($schema === 'generatedDocument') {
					$this->logged[] = $object;
				} else {
					$this->templateWrites++;
					$this->templateWritten = $object;
				}

				return $object;
			}
		);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($objects);
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn(['openregister']);

		$registers = $this->createMock(OpenRegisterResolver::class);
		$registers->method('getRegisterAndSchema')->willReturn(['register' => 'filinq', 'schema' => 'template']);
		if ($chainReadable === true) {
			$registers->method('getVersionRegisterAndSchema')->willReturn(['register' => 'filinq', 'schema' => 'templateVersion']);
		} else {
			$registers->method('getVersionRegisterAndSchema')->willThrowException(new RuntimeException('templateVersion schema is not configured'));
		}

		$versions = new TemplateVersionService($container, $appManager, $registers);
		$templates = new TemplateService($container, $appManager, $registers, $versions, $this->createMock(IUserSession::class), $this->createMock(IAppConfig::class));

		$resolver = $this->getMockBuilder(DataResolverService::class)->disableOriginalConstructor()->onlyMethods(['resolve'])->getMock();
		$resolver->method('resolve')->willReturn(['data' => ['naam' => 'Jansen'], 'warnings' => [], 'errors' => []]);
		$renderer = $this->createMock(TemplateRenderer::class);
		$renderer->method('renderTemplate')->willReturnCallback(
			function (string $content): string {
				$this->rendered[] = $content;
				return '<p>' . $content . '</p>';
			}
		);
		$renderer->method('getLastRenderWarnings')->willReturn([]);
		$pdf = $this->createMock(PdfService::class);
		$pdf->method('renderPdf')->willReturnCallback(static fn (): string => self::twoPagePdf());
		$rasterizer = $this->createMock(SvgRasterizer::class);
		$rasterizer->method('rasterizeInlineSvg')->willReturnCallback(static fn (string $html): array => ['html' => $html, 'warnings' => []]);
		$storage = $this->getMockBuilder(DocumentStorageService::class)->disableOriginalConstructor()->onlyMethods(['store'])->getMock();
		$storage->method('store')->willReturnCallback(
			function (string $userId, string $targetPath, string $filename, string $content): array {
				$this->stored++;
				return ['fileId' => 100 + $this->stored, 'path' => '/' . $userId . '/files/' . $filename, 'name' => $filename, 'size' => strlen($content)];
			}
		);

		$objectResolver = new DocumentObjectServiceResolver($container, $appManager);
		$pipeline = new DocumentRenderPipeline($renderer, $pdf, $objectResolver, new NullLogger(), $rasterizer);
		$logger = new GeneratedDocumentLogger($objectResolver, new NullLogger());
		$documents = new DocumentService($templates, $resolver, $pipeline, $storage, $logger, $container, $this->createMock(IJobList::class), new NullLogger());

		return ['documents' => $documents, 'templates' => $templates, 'versions' => $versions];
	}

	/**
	 * A real two-page PDF.
	 *
	 * @return string The bytes.
	 */
	private static function twoPagePdf(): string {
		$pdf = new FPDF();
		$pdf->AddPage();
		$pdf->AddPage();
		return $pdf->Output('S');
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

	/**
	 * REQ-DDTVP-001: the version a caller reads is the one the chain holds.
	 *
	 * @return void
	 */
	public function testTheTemplateCarriesTheVersionItIsOn(): void {
		$template = $this->services()['templates']->getTemplate('tmpl-1');

		$this->assertSame(4, $template['version']);
		$this->assertSame(['id' => 'tmpl-1', 'version' => '0.0.4', 'created' => '2026-01-01T00:00:00+00:00'], $template['@self']);
	}

	/**
	 * REQ-DDTVP-001: an unversioned template says so.
	 *
	 * @return void
	 */
	public function testAnUnversionedTemplateSaysSo(): void {
		$template = $this->services(chainReadable: false)['templates']->getTemplate('tmpl-1');

		$this->assertArrayNotHasKey('version', $template);
	}

	/**
	 * REQ-DDTVP-001: the lifted version is never written back onto the template.
	 *
	 * @return void
	 */
	public function testTheVersionIsNotStoredOnTheTemplate(): void {
		$this->services()['templates']->updateTemplateWithoutVersion('tmpl-1', ['content' => 'Nieuw', 'version' => 7]);

		$this->assertSame(1, $this->templateWrites);
		$this->assertSame('Nieuw', $this->templateWritten['content']);
		$this->assertArrayNotHasKey('version', $this->templateWritten);
	}

	/**
	 * REQ-DDTVP-002: the audit record and the result name the rendered version.
	 *
	 * @return void
	 */
	public function testTheRecordNamesTheRenderedVersion(): void {
		$result = $this->services()['documents']->generateDocument('tmpl-1', [], ['userId' => 'clerk']);

		$this->assertSame(4, $result['templateVersion']);
		$this->assertCount(1, $this->logged);
		$this->assertSame(4, $this->logged[0]['templateVersion']);
		$this->assertValidGeneratedDocument($this->logged[0]);
	}

	/**
	 * REQ-DDTVP-002: an unknown version is not reported as 1.
	 *
	 * @return void
	 */
	public function testAnUnknownVersionIsNotRecordedAsOne(): void {
		$result = $this->services(chainReadable: false)['documents']->generateDocument('tmpl-1', [], ['userId' => 'clerk']);

		$this->assertNull($result['templateVersion']);
		$this->assertArrayNotHasKey('templateVersion', $this->logged[0]);
		$this->assertValidGeneratedDocument($this->logged[0]);
	}

	/**
	 * REQ-DDTVP-003: a pinned version renders its stored content and leaves the head alone.
	 *
	 * @return void
	 */
	public function testAPinnedVersionRendersItsStoredContent(): void {
		$result = $this->services()['documents']->generateDocument('tmpl-1', [], ['userId' => 'clerk', 'templateVersion' => 2]);

		$this->assertSame(['Versie 2 {{ naam }}'], $this->rendered);
		$this->assertSame(2, $result['templateVersion']);
		$this->assertSame(2, $this->logged[0]['templateVersion']);
		$this->assertSame(0, $this->templateWrites);
		$this->assertValidGeneratedDocument($this->logged[0]);
	}

	/**
	 * REQ-DDTVP-003: without a pin the head renders, as before.
	 *
	 * @return void
	 */
	public function testWithoutAPinTheHeadRenders(): void {
		$this->services()['documents']->generateDocument('tmpl-1', [], ['userId' => 'clerk']);

		$this->assertSame(['Versie 4 {{ naam }}'], $this->rendered);
	}

	/**
	 * REQ-DDTVP-003: a version that does not exist fails and nothing is produced.
	 *
	 * @return void
	 */
	public function testAMissingVersionFailsRatherThanFallingBack(): void {
		try {
			$this->services()['documents']->generateDocument('tmpl-1', [], ['userId' => 'clerk', 'templateVersion' => 9, 'output' => ['mode' => 'files']]);
			$this->fail('A missing version must fail.');
		} catch (Exception $e) {
			$this->assertStringContainsString('tmpl-1', $e->getMessage());
			$this->assertStringContainsString('9', $e->getMessage());
			$this->assertSame(404, $e->getCode());
		}

		$this->assertSame([], $this->rendered);
		$this->assertSame(0, $this->stored);
		$this->assertSame([], $this->logged);
	}

	/**
	 * REQ-DDTVP-004: the version in force at a moment, from the chain's timestamps.
	 *
	 * @return void
	 */
	public function testTheVersionInForceOnADate(): void {
		$versions = $this->services()['versions'];

		$this->assertSame(2, $versions->versionInForceAt('tmpl-1', new DateTimeImmutable('2026-04-01T00:00:00+00:00')));
		$this->assertSame(2, $versions->versionInForceAt('tmpl-1', new DateTimeImmutable('2026-03-01T00:00:00+00:00')));
		$this->assertSame(1, $versions->versionInForceAt('tmpl-1', new DateTimeImmutable('2026-01-01T00:00:00+00:00')));
		$this->assertSame(4, $versions->versionInForceAt('tmpl-1', new DateTimeImmutable('2026-09-01T00:00:00+00:00')));
	}

	/**
	 * REQ-DDTVP-004: a moment before the template existed has no version in force.
	 *
	 * @return void
	 */
	public function testADateBeforeTheChainHasNoVersion(): void {
		$versions = $this->services()['versions'];

		$this->assertNull($versions->versionInForceAt('tmpl-1', new DateTimeImmutable('2025-12-31T23:59:59+00:00')));
	}

	/**
	 * REQ-DDTVP-005: a PDF result carries the checksum and the page count.
	 *
	 * @return void
	 */
	public function testAPdfResultReportsItsBytes(): void {
		$result = $this->services()['documents']->generateDocument('tmpl-1', [], ['userId' => 'clerk', 'format' => 'pdf']);

		$this->assertSame(hash('sha256', $result['content']), $result['sha256']);
		$this->assertSame(2, $result['pageCount']);
	}

	/**
	 * REQ-DDTVP-005: a format with no pages reports no page count.
	 *
	 * @return void
	 */
	public function testAnHtmlResultReportsNoPageCount(): void {
		$result = $this->services()['documents']->generateDocument('tmpl-1', [], ['userId' => 'clerk', 'format' => 'html']);

		$this->assertSame(hash('sha256', $result['content']), $result['sha256']);
		$this->assertArrayNotHasKey('pageCount', $result);
	}
}
