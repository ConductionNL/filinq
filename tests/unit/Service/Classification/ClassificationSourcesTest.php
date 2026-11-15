<?php

/**
 * Tests for ClassificationSources
 *
 * The read side of inbound classification: detected entities per file and the
 * dossier list. Both degrade to "nothing known" when OpenRegister cannot answer,
 * never to an exception.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Classification
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

namespace OCA\Filinq\Tests\Unit\Service\Classification;

use JsonSerializable;
use OCA\Filinq\Service\Classification\ClassificationSources;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FileEntityStatsService;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * ClassificationSources over the real stats service and object resolver; only
 * the container, the app manager and OpenRegister itself are doubles.
 *
 * @covers \OCA\Filinq\Service\Classification\ClassificationSources
 * @uses   \OCA\Filinq\Service\FileEntityStatsService
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 */
class ClassificationSourcesTest extends TestCase {

	/**
	 * The logger double.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * Set up the logger double.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * Build the subject over a container that serves the given services.
	 *
	 * @param array<int, string> $installed Installed app ids.
	 * @param array<string, object> $services Container contents by id.
	 *
	 * @return ClassificationSources
	 */
	private function sources(array $installed, array $services): ClassificationSources {
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn($installed);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static function (string $id) use ($services): object {
				if (isset($services[$id]) === false) {
					throw new RuntimeException('not registered: ' . $id);
				}

				return $services[$id];
			}
		);

		return new ClassificationSources(
			entityStats: new FileEntityStatsService(logger: $this->logger, container: $container, appManager: $appManager),
			objectResolver: new DocumentObjectServiceResolver(container: $container, appManager: $appManager),
			logger: $this->logger,
		);

	}//end sources()

	/**
	 * Detected entity rows for the file are returned as-is.
	 *
	 * @return void
	 */
	public function testEntitiesForReturnsTheDetectedRows(): void {
		$rows = [['entityType' => 'PERSON', 'value' => 'Jan']];
		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->expects($this->once())->method('findEntitiesForFile')->with(42)->willReturn($rows);

		$sources = $this->sources(['openregister'], ['OCA\OpenRegister\Db\EntityRelationMapper' => $mapper]);

		$this->assertSame($rows, $sources->entitiesFor(42));

	}//end testEntitiesForReturnsTheDetectedRows()

	/**
	 * Without OpenRegister there is no mapper, so nothing is known.
	 *
	 * @return void
	 */
	public function testEntitiesForIsNullWithoutOpenRegister(): void {
		$sources = $this->sources(['files'], []);

		$this->assertNull($sources->entitiesFor(42));

	}//end testEntitiesForIsNullWithoutOpenRegister()

	/**
	 * No rows means detection has not run yet: null, not an empty list.
	 *
	 * @return void
	 */
	public function testEntitiesForIsNullWhenNoRowsWereDetected(): void {
		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->method('findEntitiesForFile')->willReturn([]);

		$sources = $this->sources(['openregister'], ['OCA\OpenRegister\Db\EntityRelationMapper' => $mapper]);

		$this->assertNull($sources->entitiesFor(7));

	}//end testEntitiesForIsNullWhenNoRowsWereDetected()

	/**
	 * A failing mapper is logged with the file id and read as unknown.
	 *
	 * @return void
	 */
	public function testEntitiesForLogsAndReturnsNullWhenTheMapperFails(): void {
		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->method('findEntitiesForFile')->willThrowException(new RuntimeException('table missing'));
		$this->logger->expects($this->once())->method('warning')->with(
			'[Classification] detected entities cannot be read',
			['fileId' => 9, 'exception' => 'table missing']
		);

		$sources = $this->sources(['openregister'], ['OCA\OpenRegister\Db\EntityRelationMapper' => $mapper]);

		$this->assertNull($sources->entitiesFor(9));

	}//end testEntitiesForLogsAndReturnsNullWhenTheMapperFails()

	/**
	 * Dossiers are read by slug, outside RBAC and multitenancy, and reduced to
	 * uuid and name whatever shape the row has.
	 *
	 * @return void
	 */
	public function testDossiersAreReadBySlugAndNormalised(): void {
		$serialisable = new class implements JsonSerializable {
			/**
			 * Serialise like an ObjectEntity.
			 *
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return ['@self' => ['id' => 'uuid-entity'], 'name' => 'Entity dossier'];
			}//end jsonSerialize()
		};

		$objectService = $this->createMock(ObjectService::class);
		$objectService->expects($this->once())->method('searchObjectsBySlug')->with(
			'filinq',
			'dossier',
			[],
			false,
			false
		)->willReturn(
			[
				['object' => ['uuid' => 'uuid-object', 'name' => 'Wrapped dossier']],
				['uuid' => 'uuid-flat', 'name' => 'Flat dossier'],
				['id' => 'id-only'],
				$serialisable,
				'not a row',
			]
		);

		$sources = $this->sources(['openregister'], ['OCA\OpenRegister\Service\ObjectService' => $objectService]);

		$this->assertSame(
			[
				['uuid' => 'uuid-object', 'name' => 'Wrapped dossier'],
				['uuid' => 'uuid-flat', 'name' => 'Flat dossier'],
				['uuid' => 'id-only', 'name' => ''],
				['uuid' => 'uuid-entity', 'name' => 'Entity dossier'],
			],
			$sources->dossiers()
		);

	}//end testDossiersAreReadBySlugAndNormalised()

	/**
	 * When OpenRegister is unavailable the dossier list is empty and the
	 * failure is logged.
	 *
	 * @return void
	 */
	public function testDossiersAreEmptyAndLoggedWhenOpenRegisterIsUnavailable(): void {
		$this->logger->expects($this->once())->method('warning')->with(
			'[Classification] dossiers cannot be read',
			['exception' => 'OpenRegister service is not available.']
		);

		$sources = $this->sources(['files'], []);

		$this->assertSame([], $sources->dossiers());

	}//end testDossiersAreEmptyAndLoggedWhenOpenRegisterIsUnavailable()
}//end class
