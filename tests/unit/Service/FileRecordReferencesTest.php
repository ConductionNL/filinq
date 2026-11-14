<?php

/**
 * Unit tests for FileRecordReferences.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/mcp-generation-tools/tasks.md#task-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FileRecordReferences;
use OCA\Filinq\Service\IntakeRepository;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * REQ-DDMGT-002: the status of a document names its validation verdict and
 * the generation and signing records about it, by id and status only.
 *
 * @covers \OCA\Filinq\Service\FileRecordReferences
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 */
class FileRecordReferencesTest extends TestCase {

	/**
	 * @var ObjectService&MockObject
	 */
	private ObjectService $objects;

	/**
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * Build the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->objects = $this->createMock(ObjectService::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The reader over the object service double.
	 *
	 * @return FileRecordReferences
	 */
	private function references(): FileRecordReferences {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($this->objects);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		return new FileRecordReferences(objectResolver: new DocumentObjectServiceResolver($container, $apps), logger: $this->logger);

	}//end references()

	/**
	 * The newest generation record's verdict, and every record of the file by id and status.
	 *
	 * @return void
	 */
	public function testTheVerdictAndTheReferencesOfTheFile(): void {
		$this->objects->expects($this->exactly(2))->method('searchObjectsBySlug')->willReturnCallback(
			function (string $registerSlug, string $schemaSlug, array $filters): array {
				$this->assertSame(IntakeRepository::REGISTER, $registerSlug);
				if ($schemaSlug === FileRecordReferences::GENERATED) {
					$this->assertSame(['fileId' => 77], $filters);
					return [
						['uuid' => 'gen-old', 'fileId' => 77, 'status' => 'completed', 'generatedAt' => '2026-10-01T09:00:00Z', 'validationStatus' => 'failed', 'filePath' => '/alice/files/brief.pdf'],
						['uuid' => 'gen-new', 'fileId' => 77, 'status' => 'completed', 'generatedAt' => '2026-11-02T09:00:00Z', 'validationStatus' => 'passed'],
						['uuid' => 'gen-other', 'fileId' => 78, 'status' => 'completed', 'validationStatus' => 'warnings'],
					];
				}

				$this->assertSame(FileRecordReferences::SIGNING, $schemaSlug);
				$this->assertSame(['documentFileId' => 77], $filters);
				return [
					['uuid' => 'sig-1', 'documentFileId' => '77', 'documentName' => 'brief-j-de-vries.pdf', 'status' => 'PENDING', 'signerIds' => ['bob']],
					['uuid' => 'sig-2', 'documentFileId' => '770', 'status' => 'COMPLETED'],
				];
			}
		);

		$found = $this->references()->forFile(fileId: 77);

		$this->assertSame(
			[
				'validationStatus' => 'passed',
				'generationRecords' => [['id' => 'gen-new', 'status' => 'completed'], ['id' => 'gen-old', 'status' => 'completed']],
				'signingRecords' => [['id' => 'sig-1', 'status' => 'PENDING']],
			],
			$found
		);
		$this->assertStringNotContainsString('de-vries', (string) json_encode($found));
		$this->assertStringNotContainsString('brief', (string) json_encode($found));
	}//end testTheVerdictAndTheReferencesOfTheFile()

	/**
	 * A file nothing refers to has no verdict and no references.
	 *
	 * @return void
	 */
	public function testAFileWithoutRecordsHasNoVerdict(): void {
		$this->objects->method('searchObjectsBySlug')->willReturn([['uuid' => 'gen-1', 'fileId' => 5, 'status' => null]]);

		$found = $this->references()->forFile(fileId: 5);

		$this->assertSame(['validationStatus' => null, 'generationRecords' => [['id' => 'gen-1', 'status' => null]], 'signingRecords' => []], $found);
	}//end testAFileWithoutRecordsHasNoVerdict()

	/**
	 * A store that cannot be read answers no records and logs why, without the error reaching the caller.
	 *
	 * @return void
	 */
	public function testAnUnreadableStoreAnswersNoRecords(): void {
		$this->objects->method('searchObjectsBySlug')->willThrowException(new RuntimeException('store down'));
		$this->logger->expects($this->exactly(2))->method('warning')->with(
			'The records of a file could not be read',
			$this->callback(static fn (array $context): bool => $context['fileId'] === 3 && $context['exception'] === 'store down')
		);

		$this->assertSame(
			['validationStatus' => null, 'generationRecords' => [], 'signingRecords' => []],
			$this->references()->forFile(fileId: 3)
		);
	}//end testAnUnreadableStoreAnswersNoRecords()
}//end class
