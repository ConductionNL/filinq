<?php

/**
 * Unit tests for how DocumentStorageService::store() reports a failure
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\DocumentStorageService;
use OCA\Filinq\Service\FinalDocumentService;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Throwable;
use TypeError;

/**
 * Pins store()'s failure answer: an exception or an engine error is logged
 * and wrapped as one 507 storage failure; a failure that already is one is
 * rethrown unchanged and not logged again.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DocumentStorageFailureCharacterisationTest extends TestCase {

	/**
	 * A filesystem exception is logged and wrapped.
	 *
	 * @return void
	 */
	public function testExceptionIsLoggedAndWrapped(): void {
		$this->assertWrapped(new NotPermittedException('quota exceeded'));

	}//end testExceptionIsLoggedAndWrapped()

	/**
	 * An engine error is logged and wrapped the same way.
	 *
	 * @return void
	 */
	public function testEngineErrorIsLoggedAndWrapped(): void {
		$this->assertWrapped(new TypeError('quota exceeded'));

	}//end testEngineErrorIsLoggedAndWrapped()

	/**
	 * An already-coded storage failure passes through untouched.
	 *
	 * @return void
	 */
	public function testCodedFailureIsRethrownUnchanged(): void {
		$coded = new Exception('path segment is not a folder', 507);
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->never())->method('error');

		try {
			$this->serviceFailingWith($coded, $logger)->store(userId: 'alice', targetPath: 'Filinq', filename: 'x.pdf', content: 'x');
			$this->fail('store() must throw');
		} catch (Exception $e) {
			$this->assertSame($coded, $e);
		}

	}//end testCodedFailureIsRethrownUnchanged()

	/**
	 * Store with newFile() failing with this throwable and assert the wrapped failure.
	 *
	 * @param Throwable $failure The failure newFile() throws.
	 *
	 * @return void
	 */
	private function assertWrapped(Throwable $failure): void {
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('error')->with(
			message: 'Failed to store generated document: quota exceeded',
			context: ['userId' => 'alice', 'targetPath' => 'Filinq', 'exception' => $failure]
		);

		try {
			$this->serviceFailingWith($failure, $logger)->store(userId: 'alice', targetPath: 'Filinq', filename: 'x.pdf', content: 'x');
			$this->fail('store() must throw');
		} catch (Exception $e) {
			$this->assertSame(Exception::class, $e::class);
			$this->assertSame('Failed to store generated document in Files: quota exceeded', $e->getMessage());
			$this->assertSame(507, $e->getCode());
			$this->assertSame($failure, $e->getPrevious());
		}

	}//end assertWrapped()

	/**
	 * The service over a Files tree whose target folder fails to create the file.
	 *
	 * @param Throwable       $failure The failure newFile() throws.
	 * @param LoggerInterface $logger  The logger.
	 *
	 * @return DocumentStorageService
	 */
	private function serviceFailingWith(Throwable $failure, LoggerInterface $logger): DocumentStorageService {
		$target = $this->createMock(Folder::class);
		$target->method('getNonExistingName')->willReturn('x.pdf');
		$target->method('newFile')->willThrowException($failure);
		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('nodeExists')->willReturn(true);
		$userFolder->method('get')->willReturn($target);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($userFolder);

		return new DocumentStorageService($root, $logger, $this->createMock(FinalDocumentService::class));

	}//end serviceFailingWith()

}//end class
