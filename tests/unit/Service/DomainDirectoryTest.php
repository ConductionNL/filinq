<?php

/**
 * Where the domains come from, and the two answers that must not collapse.
 *
 * 🔴 "NO DOMAINS" AND "NOBODY CONFIGURED A DIRECTORY" LOOK IDENTICAL TO A CALLER
 * THAT ONLY COUNTS ROWS, and they mean opposite things: the first says there is
 * nothing to reconcile, the second says every domain there is went unreconciled
 * and nobody was told. The same holds for a register that could not be read.
 * Every test here asserts the `configured` flag, not just the row count.
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
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DomainDirectory;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * `DomainDirectory`.
 *
 * @covers \OCA\Filinq\Service\DomainDirectory
 */
class DomainDirectoryTest extends TestCase {

	/**
	 * An app config answering with the given values.
	 *
	 * @param string $register The domain register.
	 * @param string $schema   The domain schema.
	 * @param string $owner    The folder owner.
	 *
	 * @return IAppConfig The double.
	 */
	private function config(string $register, string $schema, string $owner): IAppConfig {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static function (string $app, string $key, string $default = '') use ($register, $schema, $owner): string {
				return match ($key) {
					DomainDirectory::REGISTER_KEY => $register,
					DomainDirectory::SCHEMA_KEY => $schema,
					DomainDirectory::OWNER_KEY => $owner,
					default => $default,
				};
			}
		);

		return $config;
	}//end config()

	/**
	 * A resolver handing back an object service that answers as told.
	 *
	 * The resolver double uses `onlyMethods` so it cannot grow a `resolve()`
	 * the real class lacks, which is how a directory can pass its tests while
	 * calling a method nothing implements.
	 *
	 * @param array<int, mixed>|RuntimeException $results What searchObjects answers, or throws.
	 *
	 * @return DocumentObjectServiceResolver The double.
	 */
	private function resolver(array|RuntimeException $results): DocumentObjectServiceResolver {
		$objectService = $this->getMockBuilder(ObjectService::class)
			->disableOriginalConstructor()
			->onlyMethods(['searchObjects'])
			->getMock();

		if ($results instanceof RuntimeException) {
			$objectService->method('searchObjects')->willThrowException($results);
		} else {
			$objectService->method('searchObjects')->willReturn($results);
		}

		$resolver = $this->getMockBuilder(DocumentObjectServiceResolver::class)
			->disableOriginalConstructor()
			->onlyMethods(['resolve'])
			->getMock();
		$resolver->method('resolve')->willReturn($objectService);

		return $resolver;
	}//end resolver()

	/**
	 * A configured directory reads its domains.
	 *
	 * @return void
	 */
	public function testReadsTheDomains(): void {
		$directory = new DomainDirectory(
			$this->resolver(
				[
					['id' => 'zaak-1', 'object' => ['groups' => ['juristen']]],
					['uuid' => 'zaak-2', 'object' => ['groups' => []]],
				]
			),
			$this->config('zaken', 'caseDomain', 'archief'),
			new NullLogger()
		);

		$result = $directory->all();

		self::assertTrue($result['configured']);
		self::assertCount(2, $result['domains']);
		self::assertSame('zaak-1', $result['domains'][0]['id']);
		self::assertSame(['juristen'], $result['domains'][0]['groups']);
		self::assertSame('zaak-2', $result['domains'][1]['id']);
	}//end testReadsTheDomains()

	/**
	 * An unconfigured register says so, rather than reading as no domains.
	 *
	 * @return void
	 */
	public function testAnUnconfiguredRegisterIsNotAnEmptyDirectory(): void {
		$directory = new DomainDirectory(
			$this->resolver([]),
			$this->config('', '', 'archief'),
			new NullLogger()
		);

		$result = $directory->all();

		self::assertFalse($result['configured']);
		self::assertStringContainsString('No domain register', $result['reason']);
	}//end testAnUnconfiguredRegisterIsNotAnEmptyDirectory()

	/**
	 * A missing owner is its own refusal: there is no storage to reconcile in.
	 *
	 * @return void
	 */
	public function testAMissingOwnerIsReported(): void {
		$directory = new DomainDirectory(
			$this->resolver([]),
			$this->config('zaken', 'caseDomain', ''),
			new NullLogger()
		);

		$result = $directory->all();

		self::assertFalse($result['configured']);
		self::assertStringContainsString('owner', $result['reason']);
	}//end testAMissingOwnerIsReported()

	/**
	 * A register that could not be read is a skip with the error, not a clean run.
	 *
	 * @return void
	 */
	public function testAFailedReadIsNotAnEmptyDirectory(): void {
		$directory = new DomainDirectory(
			$this->resolver(new RuntimeException('register unavailable')),
			$this->config('zaken', 'caseDomain', 'archief'),
			new NullLogger()
		);

		$result = $directory->all();

		self::assertFalse($result['configured']);
		self::assertStringContainsString('register unavailable', $result['reason']);
		self::assertSame([], $result['domains']);
	}//end testAFailedReadIsNotAnEmptyDirectory()

	/**
	 * A row with no id is left out: a folder path built from an empty id is the
	 * app's root, and reconciling that would touch every domain at once.
	 *
	 * @return void
	 */
	public function testARowWithNoIdIsLeftOut(): void {
		$directory = new DomainDirectory(
			$this->resolver([['object' => ['groups' => ['juristen']]], ['id' => 'zaak-3']]),
			$this->config('zaken', 'caseDomain', 'archief'),
			new NullLogger()
		);

		$result = $directory->all();

		self::assertCount(1, $result['domains']);
		self::assertSame('zaak-3', $result['domains'][0]['id']);
	}//end testARowWithNoIdIsLeftOut()
}//end class
