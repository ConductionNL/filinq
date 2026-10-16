<?php

/**
 * The format matrix and a forced generation give the same answer.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

require_once __DIR__ . '/Conversion/DiskLikeSofficeRunner.php';

use Exception;
use OCA\Filinq\Service\Charts\SvgRasterizer;
use OCA\Filinq\Service\Conversion\HtmlToOfficeConverter;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\FormatMatrixService;
use OCA\Filinq\Service\PdfConversionService;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Tests\Unit\Service\Conversion\DiskLikeSofficeRunner;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * Matrix over the real cascade and the real LibreOffice backend.
 */
class FormatMatrixServiceTest extends TestCase {

	private LibreOfficeHeadlessBackend $libreOffice;

	private DiskLikeSofficeRunner $runner;

	/**
	 * The matrix over a LibreOffice backend that is on or off.
	 *
	 * @param bool $enabled Whether LibreOffice is usable.
	 *
	 * @return FormatMatrixService
	 */
	private function matrix(bool $enabled): FormatMatrixService {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = '') => match ($key) {
				'filinq.conversion.libreoffice_binary_path' => '/bin/sh',
				'filinq.conversion.backends.libreoffice_enabled' => ($enabled === true ? 'true' : 'false'),
				default => $default,
			}
		);
		$this->runner = new DiskLikeSofficeRunner('PK');
		$this->libreOffice = new LibreOfficeHeadlessBackend(
			appConfig: $appConfig,
			lockingProvider: $this->createMock(ILockingProvider::class),
			logger: new NullLogger(),
			processRunner: $this->runner
		);

		return new FormatMatrixService(new PdfConversionService([$this->libreOffice], new NullLogger()));
	}

	/**
	 * The generation pipeline over the same backend.
	 *
	 * @return DocumentRenderPipeline
	 */
	private function pipeline(): DocumentRenderPipeline {
		$rasterizer = $this->createMock(SvgRasterizer::class);
		$rasterizer->method('rasterizeInlineSvg')->willReturnCallback(static fn (string $html): array => ['html' => $html, 'warnings' => []]);
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn([]);

		return new DocumentRenderPipeline(
			$this->createMock(TemplateRenderer::class),
			$this->createMock(PdfService::class),
			new DocumentObjectServiceResolver($this->createMock(ContainerInterface::class), $appManager),
			new NullLogger(),
			$rasterizer,
			null,
			new HtmlToOfficeConverter($this->libreOffice)
		);
	}

	public function testMatrixAndFailureShareReason(): void {
		$matrix = $this->matrix(enabled: false)->forInstance();

		$this->assertSame(['pdf', 'docx', 'odf', 'html'], array_keys($matrix));
		$this->assertTrue($matrix['pdf']['available']);
		$this->assertTrue($matrix['html']['available']);
		$this->assertFalse($matrix['docx']['available']);
		$this->assertFalse($matrix['odf']['available']);

		foreach (['docx', 'odf'] as $format) {
			try {
				$this->pipeline()->produceOutput(htmlContent: '<p>a</p>', format: $format, pdfOptions: []);
				$this->fail($format . ' was made without LibreOffice.');
			} catch (Exception $e) {
				$this->assertSame(503, $e->getCode());
				$this->assertSame($matrix[$format]['reason'], $e->getMessage(), 'Matrix and failure must say the same.');
			}
		}

		$this->assertSame([], $this->runner->runs, 'No soffice run, so no file of another format either.');
	}

	public function testEveryFormatIsOfferedWithLibreOffice(): void {
		$service = $this->matrix(enabled: true);

		foreach ($service->forInstance() as $format => $entry) {
			$this->assertSame(['available' => true], $entry, $format);
		}

		$this->assertSame($service->forInstance(), $service->forTemplate(template: ['id' => 't', 'content' => '<p>x</p>']));
		$this->assertSame('PK', $this->pipeline()->produceOutput(htmlContent: '<p>a</p>', format: 'docx', pdfOptions: []));
	}

	public function testCorrespondenceOffersItsOwnFormats(): void {
		$matrix = $this->matrix(enabled: false)->forInstance(flow: 'correspondence');

		$this->assertSame(['pdf', 'docx', 'html', 'email'], array_keys($matrix));
		$this->assertTrue($matrix['email']['available']);
		$this->assertSame(LibreOfficeHeadlessBackend::UNAVAILABLE_REASON, $matrix['docx']['reason']);
	}
}//end class
