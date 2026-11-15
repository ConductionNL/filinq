<?php

/**
 * Unit tests for ContractRepository, the OpenRegister-backed contract store.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Contract
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Contract;

use OCA\Filinq\Service\Contract\ContractRepository;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * How a contract is read and written through OpenRegister.
 *
 * @covers \OCA\Filinq\Service\Contract\ContractRepository
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 */
class ContractRepositoryTest extends TestCase {

	/**
	 * The object service double.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * The repository over the object service double.
	 *
	 * @return ContractRepository
	 */
	private function repository(): ContractRepository {
		$this->objects = $this->createMock(originalClassName: ObjectService::class);

		$container = $this->createMock(originalClassName: ContainerInterface::class);
		$container->method('get')->willReturn($this->objects);
		$apps = $this->createMock(originalClassName: IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		return new ContractRepository(objectResolver: new DocumentObjectServiceResolver(container: $container, appManager: $apps));
	}//end repository()

	/**
	 * A contract is read from Filinq's register and contract schema, and its
	 * stored fields come back with the uuid and without the metadata.
	 *
	 * @return void
	 */
	public function testFindReadsTheContractsStoredFields(): void {
		$repository = $this->repository();
		$this->objects->expects($this->once())
			->method('find')
			->with('c-1', 'filinq', 'documentContract')
			->willReturn(['@self' => ['id' => 'c-1', 'schema' => 'documentContract'], 'title' => 'Lease', 'status' => 'draft']);

		$this->assertSame(expected: ['title' => 'Lease', 'status' => 'draft', 'uuid' => 'c-1'], actual: $repository->find(uuid: 'c-1'));
	}//end testFindReadsTheContractsStoredFields()

	/**
	 * A blank id asks nothing; a missing contract and a failed read both read
	 * as not found, so nobody learns a contract exists.
	 *
	 * @return void
	 */
	public function testFindAnswersNullForBlankMissingAndRefused(): void {
		$repository = $this->repository();
		$this->objects->expects($this->exactly(count: 2))
			->method('find')
			->willReturnOnConsecutiveCalls(null, $this->throwException(exception: new RuntimeException(message: 'not allowed')));

		$this->assertNull(actual: $repository->find(uuid: '  '));
		$this->assertNull(actual: $repository->find(uuid: 'missing'));
		$this->assertNull(actual: $repository->find(uuid: 'secret'));
	}//end testFindAnswersNullForBlankMissingAndRefused()

	/**
	 * A save sends the fields without uuid, metadata or id, under the given
	 * uuid, and returns the stored fields.
	 *
	 * @return void
	 */
	public function testSaveSendsCleanFieldsUnderTheUuid(): void {
		$repository = $this->repository();
		$this->objects->expects($this->once())
			->method('saveObject')
			->with(['title' => 'Lease'], 'filinq', 'documentContract', 'c-2')
			->willReturn(['object' => ['title' => 'Lease'], '@self' => ['id' => 'c-2']]);

		$this->assertSame(
			expected: ['title' => 'Lease', 'uuid' => 'c-2'],
			actual: $repository->save(contract: ['title' => 'Lease', 'uuid' => 'old', '@self' => ['id' => 'x'], 'id' => 9], uuid: 'c-2')
		);
	}//end testSaveSendsCleanFieldsUnderTheUuid()

	/**
	 * A new contract is saved without a uuid argument.
	 *
	 * @return void
	 */
	public function testANewContractIsSavedWithoutAUuid(): void {
		$repository = $this->repository();
		$this->objects->expects($this->exactly(count: 2))
			->method('saveObject')
			->with(['title' => 'New'], 'filinq', 'documentContract', $this->identicalTo(value: null))
			->willReturn(['title' => 'New', 'uuid' => 'gen-1']);

		$this->assertSame(expected: ['title' => 'New', 'uuid' => 'gen-1'], actual: $repository->save(contract: ['title' => 'New']));
		$this->assertSame(expected: ['title' => 'New', 'uuid' => 'gen-1'], actual: $repository->save(contract: ['title' => 'New'], uuid: ''));
	}//end testANewContractIsSavedWithoutAUuid()

	/**
	 * When the stored row names no uuid, the one it was saved under is kept.
	 *
	 * @return void
	 */
	public function testTheGivenUuidFillsAStoredRowWithoutOne(): void {
		$repository = $this->repository();
		$this->objects->method('saveObject')->willReturn(['title' => 'Lease']);

		$this->assertSame(expected: ['title' => 'Lease', 'uuid' => 'c-3'], actual: $repository->save(contract: ['title' => 'Lease'], uuid: 'c-3'));
	}//end testTheGivenUuidFillsAStoredRowWithoutOne()

	/**
	 * A failed save is a 422 that names the cause and keeps it.
	 *
	 * @return void
	 */
	public function testAFailedSaveIsA422(): void {
		$repository = $this->repository();
		$cause      = new RuntimeException(message: 'validation failed');
		$this->objects->method('saveObject')->willThrowException($cause);

		try {
			$repository->save(contract: ['title' => 'x']);
			$this->fail(message: 'a failed save must throw');
		} catch (RuntimeException $e) {
			$this->assertSame(expected: 'The contract could not be saved: validation failed', actual: $e->getMessage());
			$this->assertSame(expected: 422, actual: $e->getCode());
			$this->assertSame(expected: $cause, actual: $e->getPrevious());
		}
	}//end testAFailedSaveIsA422()
}//end class
