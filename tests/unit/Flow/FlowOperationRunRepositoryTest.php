<?php

/**
 * The flowOperationRun store: server-side writes without RBAC, the in-flight
 * dedupe lookup and the produced-file lookup of the loop guard.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Flow
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Flow;

use OCA\Filinq\Flow\FlowOperationRunRepository;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Tests FlowOperationRunRepository.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Flow\FlowOperationRunRepository
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 */
class FlowOperationRunRepositoryTest extends TestCase {

	/**
	 * @var ObjectService&MockObject
	 */
	private ObjectService $objects;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->objects = $this->createMock(ObjectService::class);

	}//end setUp()

	/**
	 * The repository over the mocked object service.
	 *
	 * @param bool $installed Whether OpenRegister is installed.
	 *
	 * @return FlowOperationRunRepository The repository.
	 */
	private function repository(bool $installed=true): FlowOperationRunRepository {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($this->objects);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn($installed === true ? ['openregister'] : []);

		return new FlowOperationRunRepository(objectResolver: new DocumentObjectServiceResolver($container, $apps));

	}//end repository()

	/**
	 * A new run is created in the filinq register's run schema, as the server, without the caller's RBAC.
	 *
	 * @return void
	 */
	public function testANewRunIsCreatedWithoutRbac(): void {
		$this->objects->expects($this->once())->method('saveObject')
			->with(['operation' => 'ocr', 'status' => 'queued'], 'filinq', 'flowOperationRun', null, false, false)
			->willReturn(['id' => 'run-1', 'operation' => 'ocr', 'status' => 'queued']);

		$stored = $this->repository()->save(['operation' => 'ocr', 'status' => 'queued']);

		$this->assertSame('run-1', $stored['uuid']);
		$this->assertSame('queued', $stored['status']);

	}//end testANewRunIsCreatedWithoutRbac()

	/**
	 * A run with a uuid updates that object.
	 *
	 * @return void
	 */
	public function testAnExistingRunIsUpdatedInPlace(): void {
		$this->objects->expects($this->once())->method('saveObject')
			->with(['status' => 'running'], 'filinq', 'flowOperationRun', 'run-1', false, false)
			->willReturn(null);

		$stored = $this->repository()->save(['uuid' => 'run-1', 'status' => 'running']);

		$this->assertSame(['status' => 'running', 'uuid' => 'run-1'], $stored);

	}//end testAnExistingRunIsUpdatedInPlace()

	/**
	 * Only a queued or running run of the same operation on the same file is in flight.
	 *
	 * @return void
	 */
	public function testOnlyAQueuedOrRunningRunOfTheSamePairIsInFlight(): void {
		$this->objects->method('searchObjectsBySlug')->willReturnCallback(
			static fn (string $registerSlug, string $schemaSlug, array $filters): array => match ($filters['fileId']) {
				1 => [['id' => 'a', 'operation' => 'ocr', 'fileId' => 1, 'status' => 'succeeded']],
				2 => [['id' => 'b', 'operation' => 'ocr', 'fileId' => 2, 'status' => 'running']],
				3 => [['id' => 'c', 'operation' => 'pdfa', 'fileId' => 3, 'status' => 'queued'], ['id' => 'd', 'operation' => 'ocr', 'fileId' => 99, 'status' => 'queued']],
				default => [],
			}
		);

		$repository = $this->repository();
		$this->assertFalse($repository->hasRunInFlight('ocr', 1));
		$this->assertTrue($repository->hasRunInFlight('ocr', 2));
		$this->assertFalse($repository->hasRunInFlight('ocr', 3));
		$this->assertFalse($repository->hasRunInFlight('ocr', 4));

	}//end testOnlyAQueuedOrRunningRunOfTheSamePairIsInFlight()

	/**
	 * A file is a produced file only when a run records exactly that id.
	 *
	 * @return void
	 */
	public function testAProducedFileIsOneARunRecorded(): void {
		$this->objects->expects($this->exactly(2))->method('searchObjectsBySlug')
			->with('filinq', 'flowOperationRun', $this->anything(), false, false)
			->willReturnCallback(
				static fn (string $registerSlug, string $schemaSlug, array $filters): array => match ($filters['producedFileId']) {
					812100 => [['id' => 'r', 'producedFileId' => 812100]],
					default => [['id' => 's', 'producedFileId' => 1]],
				}
			);

		$repository = $this->repository();
		$this->assertTrue($repository->isProducedFile(812100));
		$this->assertFalse($repository->isProducedFile(812101));
		$this->assertFalse($repository->isProducedFile(0));

	}//end testAProducedFileIsOneARunRecorded()

	/**
	 * Without OpenRegister a write fails loudly.
	 *
	 * @return void
	 */
	public function testAWriteWithoutOpenRegisterFails(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Could not store the Flow operation run');

		$this->repository(installed: false)->save(['operation' => 'ocr']);

	}//end testAWriteWithoutOpenRegisterFails()

	/**
	 * A read failure is reported, not answered as "nothing found".
	 *
	 * @return void
	 */
	public function testAReadFailureIsReported(): void {
		$this->objects->method('searchObjectsBySlug')->willThrowException(new \Exception('db gone'));
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The Flow operation runs could not be read: db gone');

		$this->repository()->hasRunInFlight('ocr', 1);

	}//end testAReadFailureIsReported()
}//end class
