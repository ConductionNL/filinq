<?php

/**
 * Unit tests for OcrResultRepository.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Ocr
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

namespace OCA\Filinq\Tests\Unit\Service\Ocr;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\Ocr\OcrResultRepository;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * One OCR result per file: found by file id, saved over the earlier one,
 * and a storage failure reported as such.
 *
 * @covers \OCA\Filinq\Service\Ocr\OcrResultRepository
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 */
class OcrResultRepositoryTest extends TestCase {

	/**
	 * @var ObjectService&MockObject
	 */
	private ObjectService $objects;

	/**
	 * Build the object service double.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->objects = $this->createMock(ObjectService::class);

	}//end setUp()

	/**
	 * The repository over the object service double.
	 *
	 * @return OcrResultRepository
	 */
	private function repository(): OcrResultRepository {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($this->objects);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		return new OcrResultRepository(objectResolver: new DocumentObjectServiceResolver($container, $apps));

	}//end repository()

	/**
	 * The row for the asked file is returned even when the search ignored
	 * its filter; without one, nothing is.
	 *
	 * @return void
	 */
	public function testOnlyTheAskedFileMatches(): void {
		$this->objects->expects($this->exactly(2))->method('searchObjectsBySlug')
			->with(IntakeRepository::REGISTER, OcrResultRepository::SCHEMA, $this->anything())
			->willReturn(
				[
					['uuid' => 'ocr-7', 'fileId' => 7, 'text' => 'other file'],
					['uuid' => 'ocr-9', 'fileId' => 9, 'text' => 'Jan Jansen'],
				]
			);
		$repository = $this->repository();

		$this->assertSame(['uuid' => 'ocr-9', 'fileId' => 9, 'text' => 'Jan Jansen'], $repository->findForFile(fileId: 9));
		$this->assertNull($repository->findForFile(fileId: 11));

	}//end testOnlyTheAskedFileMatches()

	/**
	 * A failed search is an error, not "no result".
	 *
	 * @return void
	 */
	public function testAFailedReadIsReportedNotTreatedAsAbsent(): void {
		$cause = new \Exception('database down');
		$this->objects->method('searchObjectsBySlug')->willThrowException($cause);

		try {
			$this->repository()->findForFile(fileId: 9);
			$this->fail('A failed read was treated as absent');
		} catch (RuntimeException $exception) {
			$this->assertSame('The OCR result could not be read: database down', $exception->getMessage());
			$this->assertSame($cause, $exception->getPrevious());
		}

	}//end testAFailedReadIsReportedNotTreatedAsAbsent()

	/**
	 * Saving for a file that already has a result overwrites that object,
	 * never the uuid the caller passed in.
	 *
	 * @return void
	 */
	public function testSavingOverwritesTheEarlierResultOfTheFile(): void {
		$this->objects->method('searchObjectsBySlug')->willReturn([['uuid' => 'ocr-9', 'fileId' => 9, 'text' => 'old']]);
		$this->objects->expects($this->once())->method('saveObject')
			->with(['fileId' => 9, 'text' => 'new'], IntakeRepository::REGISTER, OcrResultRepository::SCHEMA, 'ocr-9')
			->willReturn(['uuid' => 'ocr-9', 'fileId' => 9, 'text' => 'new']);

		$saved = $this->repository()->saveForFile(result: ['uuid' => 'forged', 'fileId' => 9, 'text' => 'new']);

		$this->assertSame(['uuid' => 'ocr-9', 'fileId' => 9, 'text' => 'new'], $saved);

	}//end testSavingOverwritesTheEarlierResultOfTheFile()

	/**
	 * A storage failure on save is reported with its cause.
	 *
	 * @return void
	 */
	public function testAFailedSaveIsReported(): void {
		$cause = new \Exception('locked');
		$this->objects->method('searchObjectsBySlug')->willReturn([]);
		$this->objects->method('saveObject')->willThrowException($cause);

		try {
			$this->repository()->saveForFile(result: ['fileId' => 9, 'text' => 'new']);
			$this->fail('A failed save was swallowed');
		} catch (RuntimeException $exception) {
			$this->assertSame('Could not store the OCR result: locked', $exception->getMessage());
			$this->assertSame($cause, $exception->getPrevious());
		}

	}//end testAFailedSaveIsReported()
}//end class
