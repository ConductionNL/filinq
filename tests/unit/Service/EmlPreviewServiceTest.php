<?php

/**
 * Unit tests for EmlPreviewService
 *
 * Covers: rendering the original EML with an EMPTY entity set (no redaction)
 * and delegating to EmlPdfAssemblyService; and the failure guards (OpenRegister
 * missing, resolved node not a File, anonymise-EML API absent).
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\EmlPdfAssemblyService;
use OCA\DocuDesk\Service\EmlPreviewService;
use OCP\App\IAppManager;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * OR FileService double exposing getFileById() + anonymizeEmlStructured().
 */
class FakePreviewOrFileService {
	/** @var mixed */
	public mixed $node = null;

	/** @var mixed */
	public mixed $structure = null;

	/** @var array|null */
	public ?array $lastEntities = null;

	/**
	 * @param int $fileId File id.
	 *
	 * @return mixed
	 */
	public function getFileById(int $fileId): mixed {
		return $this->node;
	}

	/**
	 * @param mixed $node File node.
	 * @param array $entities Entities.
	 * @param string $scope Scope.
	 * @param mixed $dossierKey Dossier key.
	 *
	 * @return mixed
	 */
	public function anonymizeEmlStructured(mixed $node, array $entities, string $scope = 'document', mixed $dossierKey = null): mixed {
		$this->lastEntities = $entities;
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
	 * The preview renders via the assembly service and passes an EMPTY entity
	 * set to OR so nothing is redacted (faithful original preview).
	 *
	 * @return void
	 */
	public function testRendersOriginalWithEmptyEntities(): void {
		$node = $this->createMock(File::class);
		$node->method('getName')->willReturn('message.eml');

		$or = new FakePreviewOrFileService();
		$or->node = $node;
		$or->structure = (object)['headers' => [], 'body' => (object)['plain' => null, 'html' => null], 'attachments' => [], 'inlineImages' => []];

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($or);

		$assembly = $this->createMock(EmlPdfAssemblyService::class);
		$assembly->expects($this->once())
			->method('assemble')
			->with($or->structure, 'message.eml')
			->willReturn('%PDF-1.4 preview');

		$result = $this->service($container, $this->appManagerWith(['openregister']), $assembly)
			->renderOriginalPreview(fileId: 99);

		$this->assertSame('%PDF-1.4 preview', $result);
		$this->assertSame([], $or->lastEntities, 'preview must pass an empty entity set (no redaction)');
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
	 * OR lacks the anonymise-EML API → RuntimeException, assembly never invoked.
	 *
	 * @return void
	 */
	public function testThrowsWhenAnonymiseApiAbsent(): void {
		$node = $this->createMock(File::class);
		$or = new class($node) {
			/**
			 * @param mixed $node Node.
			 */
			public function __construct(
				private mixed $node,
			) {
			}

			/**
			 * @param int $fileId File id.
			 *
			 * @return mixed
			 */
			public function getFileById(int $fileId): mixed {
				return $this->node;
			}
		};

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($or);

		$assembly = $this->createMock(EmlPdfAssemblyService::class);
		$assembly->expects($this->never())->method('assemble');

		$this->expectException(RuntimeException::class);
		$this->service($container, $this->appManagerWith(['openregister']), $assembly)->renderOriginalPreview(fileId: 3);
	}
}
