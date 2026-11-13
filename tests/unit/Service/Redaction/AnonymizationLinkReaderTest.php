<?php

/**
 * Unit tests for AnonymizationLinkReader.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Redaction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Redaction;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\Redaction\AnonymizationLinkReader;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * No id means no lookup; a failed lookup means "no link", logged.
 *
 * @covers \OCA\Filinq\Service\Redaction\AnonymizationLinkReader
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 */
class AnonymizationLinkReaderTest extends TestCase {

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
	 * @return AnonymizationLinkReader
	 */
	private function reader(): AnonymizationLinkReader {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($this->objects);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		return new AnonymizationLinkReader(objectResolver: new DocumentObjectServiceResolver($container, $apps), logger: $this->logger);

	}//end reader()

	/**
	 * A missing file or link id is answered without asking the register.
	 *
	 * @return void
	 */
	public function testNoIdMeansNoLookup(): void {
		$this->objects->expects($this->never())->method('searchObjectsBySlug');
		$this->objects->expects($this->never())->method('find');
		$reader = $this->reader();

		$this->assertNull($reader->forSource(sourceFileId: 0));
		$this->assertNull($reader->forAnonymized(anonymizedFileId: -1));
		$this->assertNull($reader->storedObject(linkId: ''));
		$this->assertNull($reader->byId(linkId: ''));

	}//end testNoIdMeansNoLookup()

	/**
	 * A failed search for a source's link counts as not ready, and is logged.
	 *
	 * @return void
	 */
	public function testAFailedSourceLookupIsLoggedAsNotReady(): void {
		$this->objects->method('searchObjectsBySlug')
			->with(IntakeRepository::REGISTER, AnonymizationLinkReader::SCHEMA, ['sourceFileId' => 812200])
			->willThrowException(new RuntimeException('register offline'));
		$this->logger->expects($this->once())->method('warning')->with(
			'[AnonymizationLinkReader] could not read the link, so the record counts as not ready',
			$this->callback(static fn (array $context): bool => $context['sourceFileId'] === 812200 && $context['error'] === 'register offline')
		);

		$this->assertNull($this->reader()->forSource(sourceFileId: 812200));

	}//end testAFailedSourceLookupIsLoggedAsNotReady()

	/**
	 * A failed search for a redacted copy's link is logged and answers null.
	 *
	 * @return void
	 */
	public function testAFailedRedactedCopyLookupIsLogged(): void {
		$this->objects->method('searchObjectsBySlug')->willThrowException(new RuntimeException('register offline'));
		$this->logger->expects($this->once())->method('warning')->with(
			'[AnonymizationLinkReader] could not read the link of a redacted copy',
			['anonymizedFileId' => 812201, 'error' => 'register offline']
		);

		$this->assertNull($this->reader()->forAnonymized(anonymizedFileId: 812201));

	}//end testAFailedRedactedCopyLookupIsLogged()

	/**
	 * An id the register cannot find yields null from both id lookups, with
	 * an informational log line each.
	 *
	 * @return void
	 */
	public function testAnUnknownLinkIdIsNull(): void {
		$this->objects->method('find')->willThrowException(new RuntimeException('not found'));
		$messages = [];
		$this->logger->expects($this->exactly(2))->method('info')->willReturnCallback(
			static function (string $message, array $context) use (&$messages): void {
				$messages[] = [$message, $context];
			}
		);
		$reader = $this->reader();

		$this->assertNull($reader->storedObject(linkId: 'link-9'));
		$this->assertNull($reader->byId(linkId: 'link-9'));
		$this->assertSame(
			[
				['[AnonymizationLinkReader] no stored link under this id', ['linkId' => 'link-9', 'error' => 'not found']],
				['[AnonymizationLinkReader] no readable link under this id', ['linkId' => 'link-9', 'error' => 'not found']],
			],
			$messages
		);

	}//end testAnUnknownLinkIdIsNull()

	/**
	 * A stored object that is not an entity is not handed out.
	 *
	 * @return void
	 */
	public function testANonEntityIsNotAStoredObject(): void {
		$this->objects->method('find')->willReturn(['uuid' => 'link-1']);

		$this->assertNull($this->reader()->storedObject(linkId: 'link-1'));

	}//end testANonEntityIsNotAStoredObject()

	/**
	 * A link read by id carries that id when the row has no uuid of its own.
	 *
	 * @return void
	 */
	public function testALinkReadByIdCarriesTheId(): void {
		$this->objects->method('find')->willReturn(['sourceFileId' => 812200, 'anonymizedFileId' => 812201]);

		$link = $this->reader()->byId(linkId: 'link-1');

		$this->assertSame('link-1', $link['uuid']);
		$this->assertSame(812201, $link['anonymizedFileId']);

	}//end testALinkReadByIdCarriesTheId()
}//end class
