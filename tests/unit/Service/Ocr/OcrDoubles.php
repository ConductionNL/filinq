<?php

/**
 * Doubles for the OCR surface: an in-memory ocrResult store behind the real
 * repository, and an OcrService whose engine calls are stubbed while its
 * settings reads stay real.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Ocr
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Ocr;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Ocr\OcrResultRepository;
use OCA\Filinq\Service\Ocr\OcrRunService;
use OCA\Filinq\Service\OcrService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\IAppConfig;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * Builders shared by the OCR tests.
 */
trait OcrDoubles {

	/**
	 * Rows by uuid.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $ocrRows = [];

	/**
	 * Every payload written, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected array $ocrWritten = [];

	/**
	 * The real repository over an in-memory ObjectService.
	 *
	 * @return OcrResultRepository The repository.
	 */
	protected function ocrResultRepository(): OcrResultRepository {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object = [], string $register = '', string $schema = '', ?string $uuid = null) {
				$this->ocrWritten[] = $object;
				$uuid = $uuid ?? ('ocr-' . (count($this->ocrRows) + 1));
				$this->ocrRows[$uuid] = array_merge($object, ['uuid' => $uuid]);

				return $this->ocrRows[$uuid];
			}
		);
		$objects->method('searchObjectsBySlug')->willReturnCallback(
			fn (string $registerSlug, string $schemaSlug, array $filters = []) => array_values(
				array_filter(
					$this->ocrRows,
					static fn (array $row): bool => ($row['fileId'] ?? null) === ($filters['fileId'] ?? null)
				)
			)
		);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($objects);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		return new OcrResultRepository(new DocumentObjectServiceResolver($container, $apps));

	}//end ocrResultRepository()

	/**
	 * An OcrService with real settings reads over the given app config, and a
	 * stubbed engine.
	 *
	 * @param array<string, string> $config App config values by key.
	 * @param bool $tesseract Whether Tesseract is installed.
	 * @param string $text What the engine recovers.
	 * @param float $confidence The engine's confidence.
	 *
	 * @return OcrService The service.
	 */
	protected function ocrService(array $config = [], bool $tesseract = true, string $text = 'Jan Jansen, BSN 111222333', float $confidence = 91.44): OcrService {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => $config[$key] ?? $default
		);

		$service = $this->getMockBuilder(OcrService::class)
			->setConstructorArgs(
				[
					new NullLogger(),
					$appConfig,
					$this->createMock(\OCP\Files\IRootFolder::class),
					$this->createMock(\OCP\IUserSession::class),
				]
			)
			->onlyMethods(['isTesseractAvailable', 'getTesseractVersion', 'extractTextFromImage', 'extractTextFromPdf'])
			->getMock();
		$service->method('isTesseractAvailable')->willReturn($tesseract);
		$service->method('getTesseractVersion')->willReturn('tesseract 5.3.0');
		$service->method('extractTextFromImage')->willReturn(['text' => $text, 'confidence' => $confidence]);
		$service->method('extractTextFromPdf')->willReturn(['text' => $text, 'confidence' => $confidence]);

		return $service;

	}//end ocrService()

	/**
	 * The run service over the given engine.
	 *
	 * @param OcrService $ocr The engine.
	 *
	 * @return OcrRunService The service.
	 */
	protected function ocrRunService(OcrService $ocr): OcrRunService {
		return $this->ocrRunServiceOver(ocr: $ocr, repository: $this->ocrResultRepository());

	}//end ocrRunService()

	/**
	 * The run service over the given engine and repository.
	 *
	 * @param OcrService $ocr The engine.
	 * @param OcrResultRepository $repository The rows.
	 *
	 * @return OcrRunService The service.
	 */
	protected function ocrRunServiceOver(OcrService $ocr, OcrResultRepository $repository): OcrRunService {
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getDateTime')->willReturn(new \DateTime('2026-09-29T10:00:00+00:00'));

		return new OcrRunService($ocr, $repository, $time, new NullLogger());

	}//end ocrRunServiceOver()

	/**
	 * A file node.
	 *
	 * @param int $id The file id.
	 * @param string $mimeType The MIME type.
	 *
	 * @return File The node.
	 */
	protected function ocrFile(int $id = 812004, string $mimeType = 'application/pdf'): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);
		$file->method('getMimeType')->willReturn($mimeType);
		$file->method('getName')->willReturn('scan-' . $id . '.pdf');
		$file->method('getContent')->willReturn('%PDF-1.4 scanned');
		$file->method('isReadable')->willReturn(true);

		return $file;

	}//end ocrFile()
}//end trait
