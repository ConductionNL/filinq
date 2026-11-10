<?php

/**
 * Unit tests for EmlPreviewService
 *
 * Covers: rendering the original EML with an EMPTY entity set (no redaction)
 * and delegating to EmlPdfAssemblyService; and the failure guards (OpenRegister
 * missing, resolved node not a File, anonymise-EML API absent).
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
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\EmlPdfAssemblyService;
use OCA\Filinq\Service\EmlPreviewService;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCA\OpenRegister\Files\Service\TextExtraction\EmlBody;
use OCA\OpenRegister\Files\Service\TextExtraction\EmlStructure;
use OCA\OpenRegister\Files\Service\TextExtractionService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * OR FileService double exposing getFileById().
 */
class FakePreviewOrFileService {
	/** @var mixed */
	public mixed $node = null;

	/**
	 * @param int $fileId File id.
	 *
	 * @return mixed
	 */
	public function getFileById(int $fileId): mixed {
		return $this->node;
	}
}

/**
 * OR TextExtractionService double: parseEmlStructured() returns a fixed structure.
 */
class FakePreviewTextExtraction extends TextExtractionService {
	/** @var EmlStructure|null */
	public ?EmlStructure $structure = null;

	/** @var mixed */
	public mixed $parsed = null;

	/**
	 * @param \OCP\Files\File $file The EML file.
	 *
	 * @return EmlStructure
	 */
	public function parseEmlStructured(\OCP\Files\File $file): EmlStructure {
		$this->parsed = $file;
		return $this->structure;
	}
}

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EmlPreviewServiceTest extends TestCase {

	/**
	 * Build a service with the given collaborators.
	 *
	 * @param ContainerInterface $container Container.
	 * @param IAppManager $appManager App manager.
	 * @param EmlPdfAssemblyService $assembly Assembly service.
	 *
	 * @return EmlPreviewService
	 */
	private function service(ContainerInterface $container, IAppManager $appManager, EmlPdfAssemblyService $assembly): EmlPreviewService {
		return new EmlPreviewService(
			appManager: $appManager,
			container: $container,
			emlAssembly: $assembly,
			logger: $this->createMock(LoggerInterface::class)
		);
	}

	/**
	 * An IAppManager reporting the given installed apps.
	 *
	 * @param array $apps Installed app ids.
	 *
	 * @return IAppManager
	 */
	private function appManagerWith(array $apps): IAppManager {
		$manager = $this->createMock(IAppManager::class);
		$manager->method('getInstalledApps')->willReturn($apps);
		return $manager;
	}

	/**
	 * A container serving the OR FileService and the TextExtractionService.
	 *
	 * @param FakePreviewOrFileService       $files  The file service.
	 * @param FakePreviewTextExtraction|null $parser The EML parser.
	 *
	 * @return ContainerInterface
	 */
	private function containerWith(FakePreviewOrFileService $files, ?FakePreviewTextExtraction $parser): ContainerInterface {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			fn (string $id) => match ($id) {
				'OCA\OpenRegister\Service\FileService' => $files,
				TextExtractionService::class => ($parser ?? throw new RuntimeException('no parser')),
				default => throw new RuntimeException('Unknown ' . $id),
			}
		);
		return $container;
	}

	/**
	 * The preview parses the original through OR's typed EML parser and
	 * renders that structure unredacted via the assembly service.
	 *
	 * @return void
	 */
	public function testRendersTheParsedOriginal(): void {
		$node = $this->createMock(File::class);
		$node->method('getName')->willReturn('message.eml');

		$or = new FakePreviewOrFileService();
		$or->node = $node;
		$parser = new FakePreviewTextExtraction();
		$parser->structure = new EmlStructure(headers: [], body: new EmlBody(plainText: 'Hallo', html: null), attachments: []);

		$assembly = $this->createMock(EmlPdfAssemblyService::class);
		$assembly->expects($this->once())
			->method('assemble')
			->with($parser->structure, 'message.eml')
			->willReturn('%PDF-1.4 preview');

		$result = $this->service($this->containerWith($or, $parser), $this->appManagerWith(['openregister']), $assembly)
			->renderOriginalPreview(fileId: 99);

		$this->assertSame('%PDF-1.4 preview', $result);
		$this->assertSame($node, $parser->parsed);
	}

	/**
	 * OpenRegister not installed → RuntimeException, assembly never invoked.
	 *
	 * @return void
	 */
	public function testThrowsWhenOpenRegisterMissing(): void {
		$container = $this->createMock(ContainerInterface::class);
		$assembly = $this->createMock(EmlPdfAssemblyService::class);
		$assembly->expects($this->never())->method('assemble');

		$this->expectException(RuntimeException::class);
		$this->service($container, $this->appManagerWith([]), $assembly)->renderOriginalPreview(fileId: 1);
	}

	/**
	 * Resolved node is not a File → RuntimeException, assembly never invoked.
	 *
	 * @return void
	 */
	public function testThrowsWhenNodeNotAFile(): void {
		$or = new FakePreviewOrFileService();
		$or->node = null;

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($or);

		$assembly = $this->createMock(EmlPdfAssemblyService::class);
		$assembly->expects($this->never())->method('assemble');

		$this->expectException(RuntimeException::class);
		$this->service($container, $this->appManagerWith(['openregister']), $assembly)->renderOriginalPreview(fileId: 2);
	}

	/**
	 * OR's EML parser cannot be resolved → RuntimeException, assembly never invoked.
	 *
	 * @return void
	 */
	public function testThrowsWhenTheParserIsUnavailable(): void {
		$or = new FakePreviewOrFileService();
		$or->node = $this->createMock(File::class);

		$assembly = $this->createMock(EmlPdfAssemblyService::class);
		$assembly->expects($this->never())->method('assemble');

		$this->expectException(RuntimeException::class);
		$this->service($this->containerWith($or, null), $this->appManagerWith(['openregister']), $assembly)->renderOriginalPreview(fileId: 3);
	}
}
