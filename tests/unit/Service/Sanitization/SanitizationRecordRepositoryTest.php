<?php

/**
 * Unit tests for SanitizationRecordRepository.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Sanitization
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

namespace OCA\Filinq\Tests\Unit\Service\Sanitization;

use JsonSerializable;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Sanitization\SanitizationRecordRepository;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Sanitization records are written as system rows and found by file.
 *
 * @covers \OCA\Filinq\Service\Sanitization\SanitizationRecordRepository
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 */
class SanitizationRecordRepositoryTest extends TestCase {

	/**
	 * The object service double.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * The repository over the object service double.
	 *
	 * @return SanitizationRecordRepository
	 */
	private function repository(): SanitizationRecordRepository {
		$this->objects = $this->createMock(originalClassName: ObjectService::class);

		$container = $this->createMock(originalClassName: ContainerInterface::class);
		$container->method('get')->willReturn($this->objects);
		$apps = $this->createMock(originalClassName: IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		return new SanitizationRecordRepository(objectResolver: new DocumentObjectServiceResolver(container: $container, appManager: $apps));
	}//end repository()

	/**
	 * A record is saved in Filinq's register, bypassing RBAC and unowned, and
	 * comes back as its fields with the uuid from its metadata.
	 *
	 * @return void
	 */
	public function testARecordIsSavedAsAnUnownedSystemRow(): void {
		$repository = $this->repository();
		$this->objects->expects($this->once())
			->method('saveObject')
			->with(['fileId' => 7, 'sanitizedFileId' => 8], 'filinq', 'sanitizationRecord', null, false, true, true)
			->willReturn(['object' => ['fileId' => 7, 'sanitizedFileId' => 8], '@self' => ['id' => 'r-1']]);

		$this->assertSame(
			expected: ['fileId' => 7, 'sanitizedFileId' => 8, 'uuid' => 'r-1'],
			actual: $repository->save(record: ['fileId' => 7, 'sanitizedFileId' => 8])
		);
	}//end testARecordIsSavedAsAnUnownedSystemRow()

	/**
	 * Records are found by the sanitized copy or by the source file, through
	 * the slug search, as a list of their fields.
	 *
	 * @return void
	 */
	public function testRecordsAreFoundBySanitizedOrSourceFile(): void {
		$repository = $this->repository();
		$entity     = new class implements JsonSerializable {
			/**
			 * The serialised entity.
			 *
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return ['uuid' => 'r-3', 'fileId' => 7, '@self' => ['id' => 'ignored']];
			}
		};
		$this->objects->expects($this->exactly(count: 2))
			->method('searchObjectsBySlug')
			->willReturnCallback(
				static fn (string $registerSlug, string $schemaSlug, array $filters): array => match ($filters) {
					['sanitizedFileId' => 8] => [5 => ['fileId' => 7, '@self' => ['id' => 'r-2']]],
					['fileId' => 7] => [$entity, 'not a row'],
				}
			);

		$this->assertSame(expected: [['fileId' => 7, 'uuid' => 'r-2']], actual: $repository->forSanitizedFile(fileId: 8));
		$this->assertSame(expected: [['uuid' => 'r-3', 'fileId' => 7], []], actual: $repository->forSourceFile(fileId: 7));
	}//end testRecordsAreFoundBySanitizedOrSourceFile()

	/**
	 * A search that answers with a count instead of rows finds nothing.
	 *
	 * @return void
	 */
	public function testACountAnswerFindsNothing(): void {
		$repository = $this->repository();
		$this->objects->method('searchObjectsBySlug')->willReturn(0);

		$this->assertSame(expected: [], actual: $repository->forSourceFile(fileId: 7));
	}//end testACountAnswerFindsNothing()
}//end class
