<?php

/**
 * Unit tests for DossierSummaryDataService built from injected DossierSummaryParts.
 *
 * Asserts that every facade method delegates to the injected repository,
 * label resolver, ranker or collector with its arguments unchanged, and the
 * three outcomes of the dossier read check.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\BaseLabelResolver;
use OCA\Filinq\Service\DossierEntityCollector;
use OCA\Filinq\Service\DossierObjectRepository;
use OCA\Filinq\Service\DossierPlaceholderRanker;
use OCA\Filinq\Service\DossierSummaryDataService;
use OCA\Filinq\Service\Support\DossierSummaryParts;
use OCP\App\IAppManager;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The summary data facade over injected parts.
 */
#[CoversClass(DossierSummaryDataService::class)]
#[CoversClass(DossierSummaryParts::class)]
final class DossierSummaryDataServicePartsTest extends TestCase {

	/**
	 * @var DossierObjectRepository&MockObject
	 */
	private DossierObjectRepository&MockObject $repository;

	/**
	 * @var BaseLabelResolver&MockObject
	 */
	private BaseLabelResolver&MockObject $labels;

	/**
	 * @var DossierPlaceholderRanker&MockObject
	 */
	private DossierPlaceholderRanker&MockObject $ranker;

	/**
	 * @var DossierEntityCollector&MockObject
	 */
	private DossierEntityCollector&MockObject $collector;

	/**
	 * @var ContainerInterface&MockObject
	 */
	private ContainerInterface&MockObject $container;

	/**
	 * The service under test.
	 *
	 * @var DossierSummaryDataService
	 */
	private DossierSummaryDataService $service;

	/**
	 * Build the service from mocked parts.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->repository = $this->createMock(DossierObjectRepository::class);
		$this->labels     = $this->createMock(BaseLabelResolver::class);
		$this->ranker     = $this->createMock(DossierPlaceholderRanker::class);
		$this->collector  = $this->createMock(DossierEntityCollector::class);
		$this->container  = $this->createMock(ContainerInterface::class);

		$this->service = new DossierSummaryDataService(
			rootFolder: $this->createMock(IRootFolder::class),
			userSession: $this->createMock(IUserSession::class),
			appManager: $this->createMock(IAppManager::class),
			container: $this->container,
			logger: $this->createMock(LoggerInterface::class),
			parts: new DossierSummaryParts(
				repository: $this->repository,
				labelResolver: $this->labels,
				ranker: $this->ranker,
				collector: $this->collector,
			),
		);
	}//end setUp()

	/**
	 * loadDossier reads the context and resolves its folder reference through the injected repository.
	 *
	 * @return void
	 */
	public function testLoadDossierUsesTheInjectedRepository(): void {
		$folder  = $this->createMock(Folder::class);
		$context = ['uuid' => 'd-1', 'folderRef' => 42, 'title' => 'Woo 2026-1'];
		$this->repository->expects($this->once())->method('loadDossierContext')->with('d-1')->willReturn($context);
		$this->repository->expects($this->once())->method('resolveDossierFolder')->with(42)->willReturn($folder);
		$this->container->expects($this->never())->method('get');

		$this->assertSame(['context' => $context, 'folder' => $folder], $this->service->loadDossier('d-1'));
	}//end testLoadDossierUsesTheInjectedRepository()

	/**
	 * A context without a folder reference resolves a null reference.
	 *
	 * @return void
	 */
	public function testLoadDossierPassesAMissingFolderRefAsNull(): void {
		$folder = $this->createMock(Folder::class);
		$this->repository->method('loadDossierContext')->willReturn(['uuid' => 'd-2']);
		$this->repository->expects($this->once())->method('resolveDossierFolder')->with(null)->willReturn($folder);

		$this->assertSame($folder, $this->service->loadDossier('d-2')['folder']);
	}//end testLoadDossierPassesAMissingFolderRefAsNull()

	/**
	 * The ranker, collector and label resolver methods pass their arguments and answers through unchanged.
	 *
	 * @return void
	 */
	public function testFacadeMethodsDelegateToTheInjectedParts(): void {
		$folder = $this->createMock(Folder::class);
		$map    = ['[NAAM-1]' => 1];
		$this->ranker->expects($this->once())->method('rank')->with($folder)->willReturn($map);
		$this->ranker->expects($this->once())->method('sortKey')->with('[NAAM-2]')->willReturn(['NAAM', 2]);
		$this->collector->expects($this->once())->method('walkDossierFiles')->with($folder, $map)->willReturn([['fileId' => 7]]);
		$this->collector->expects($this->once())->method('loadAnonymisedEntitiesForFile')->with(7, $map)->willReturn([['type' => 'NAAM']]);
		$this->labels->expects($this->once())->method('resolve')->with(['b-1'])->willReturn(['b-1' => 'Art. 5.1 lid 2 sub e']);
		$this->labels->expects($this->once())->method('groupByName')->with([['base' => 'b-1']])->willReturn(['Art. 5.1 lid 2 sub e' => 1]);
		$this->repository->expects($this->once())->method('updateDossierConfiguration')->with('d-1', 99);

		$this->assertSame($map, $this->service->placeholderRanking($folder));
		$this->assertSame(['NAAM', 2], $this->service->placeholderSortKey('[NAAM-2]'));
		$this->assertSame([['fileId' => 7]], $this->service->walkDossierFiles($folder, $map));
		$this->assertSame([['type' => 'NAAM']], $this->service->loadAnonymisedEntitiesForFile(7, $map));
		$this->assertSame(['b-1' => 'Art. 5.1 lid 2 sub e'], $this->service->resolveBaseLabels(['b-1']));
		$this->assertSame(['Art. 5.1 lid 2 sub e' => 1], $this->service->groupBasesByName([['base' => 'b-1']]));
		$this->service->updateDossierConfiguration('d-1', 99);
	}//end testFacadeMethodsDelegateToTheInjectedParts()

	/**
	 * Without OpenRegister's object service the dossier is refused with 403.
	 *
	 * @return void
	 */
	public function testAssertDossierReadableRefusesWithoutObjectService(): void {
		$this->repository->method('objectService')->willReturn(null);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionCode(403);
		$this->expectExceptionMessage('Access denied: OpenRegister ObjectService unavailable.');

		$this->service->assertDossierReadable('d-1');
	}//end testAssertDossierReadableRefusesWithoutObjectService()

	/**
	 * A dossier the object service does not return is refused with 403, naming the id.
	 *
	 * @return void
	 */
	public function testAssertDossierReadableRefusesAnUnreadableDossier(): void {
		$objects = new class {
			/** @var array<int, array{string, string, string}> */
			public array $finds = [];

			/**
			 * @param string $id       The id.
			 * @param string $register The register.
			 * @param string $schema   The schema.
			 *
			 * @return object|null
			 */
			public function find(string $id, string $register, string $schema): ?object {
				$this->finds[] = [$id, $register, $schema];
				return null;
			}
		};
		$this->repository->method('objectService')->willReturn($objects);

		try {
			$this->service->assertDossierReadable('d-9');
			$this->fail('RuntimeException expected');
		} catch (RuntimeException $e) {
			$this->assertSame(403, $e->getCode());
			$this->assertSame('Access denied or dossier not found: d-9', $e->getMessage());
		}

		$this->assertSame([['d-9', 'filinq', 'dossier']], $objects->finds);
	}//end testAssertDossierReadableRefusesAnUnreadableDossier()

	/**
	 * A dossier the object service returns passes the check.
	 *
	 * @return void
	 */
	public function testAssertDossierReadableAcceptsAReadableDossier(): void {
		$objects = new class {
			/**
			 * @param string $id       The id.
			 * @param string $register The register.
			 * @param string $schema   The schema.
			 *
			 * @return object
			 */
			public function find(string $id, string $register, string $schema): object {
				return new \stdClass();
			}
		};
		$this->repository->method('objectService')->willReturn($objects);

		$this->service->assertDossierReadable('d-1');
		$this->addToAssertionCount(1);
	}//end testAssertDossierReadableAcceptsAReadableDossier()
}//end class
