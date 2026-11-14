<?php

/**
 * Unit tests for SealLookup.
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
 * @spec openspec/changes/document-sanitization/tasks.md#3-5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Sanitization;

use JsonSerializable;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Sanitization\SealLookup;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A file is sealed when an active waarmerk names it; a revoked seal, a seal
 * of another file and an unreadable seal list all answer not sealed.
 *
 * @covers \OCA\Filinq\Service\Sanitization\SealLookup
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 */
class SealLookupTest extends TestCase {

	/**
	 * The object service double.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * The logger double.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * The lookup over the object service double.
	 *
	 * @return SealLookup
	 */
	private function lookup(): SealLookup {
		$this->objects = $this->createMock(originalClassName: ObjectService::class);
		$this->logger  = $this->createMock(originalClassName: LoggerInterface::class);

		$container = $this->createMock(originalClassName: ContainerInterface::class);
		$container->method('get')->willReturn($this->objects);
		$apps = $this->createMock(originalClassName: IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		return new SealLookup(
			objectResolver: new DocumentObjectServiceResolver(container: $container, appManager: $apps),
			logger: $this->logger
		);
	}//end lookup()

	/**
	 * An active waarmerk on the file is found through the slug search on its file id.
	 *
	 * @return void
	 */
	public function testAnActiveSealOnTheFileIsFound(): void {
		$lookup = $this->lookup();
		$this->objects->expects($this->once())
			->method('searchObjectsBySlug')
			->with('filinq', 'waarmerk', ['documentFileId' => 41])
			->willReturn([['object' => ['documentFileId' => 41, 'status' => 'active']]]);

		$this->assertTrue($lookup->hasActiveSeal(fileId: 41));
	}//end testAnActiveSealOnTheFileIsFound()

	/**
	 * An entity row is read through its serialisation.
	 *
	 * @return void
	 */
	public function testAnEntityRowIsRead(): void {
		$lookup = $this->lookup();
		$entity = new class implements JsonSerializable {
			/**
			 * The serialised entity.
			 *
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return ['documentFileId' => 41, 'status' => 'active', '@self' => ['id' => 'w-1']];
			}
		};
		$this->objects->method('searchObjectsBySlug')->willReturn([$entity]);

		$this->assertTrue($lookup->hasActiveSeal(fileId: 41));
	}//end testAnEntityRowIsRead()

	/**
	 * A revoked seal, or a seal of another file the search let through, is no seal.
	 *
	 * @return void
	 */
	public function testARevokedSealOrAnotherFilesSealIsNoSeal(): void {
		$lookup = $this->lookup();
		$this->objects->method('searchObjectsBySlug')->willReturn(
			[
				['documentFileId' => 41, 'status' => 'revoked'],
				['documentFileId' => 42, 'status' => 'active'],
				'not a row',
			]
		);

		$this->assertFalse($lookup->hasActiveSeal(fileId: 41));
	}//end testARevokedSealOrAnotherFilesSealIsNoSeal()

	/**
	 * A generated artifact (file id 0) is never looked up.
	 *
	 * @return void
	 */
	public function testNoFileIdIsNeverLookedUp(): void {
		$lookup = $this->lookup();
		$this->objects->expects($this->never())->method('searchObjectsBySlug');

		$this->assertFalse($lookup->hasActiveSeal(fileId: 0));
	}//end testNoFileIdIsNeverLookedUp()

	/**
	 * An unreadable seal list answers not sealed and is logged; a count answer finds nothing.
	 *
	 * @return void
	 */
	public function testAnUnreadableSealListAnswersNotSealed(): void {
		$lookup = $this->lookup();
		$this->objects->method('searchObjectsBySlug')->willReturnOnConsecutiveCalls(
			$this->throwException(new RuntimeException('register down')),
			0
		);
		$this->logger->expects($this->once())->method('warning')->with('Seals of a file could not be read', ['fileId' => 41, 'exception' => 'register down']);

		$this->assertFalse($lookup->hasActiveSeal(fileId: 41));
		$this->assertFalse($lookup->hasActiveSeal(fileId: 41));
	}//end testAnUnreadableSealListAnswersNotSealed()
}//end class
