<?php

/**
 * Unit tests for the refusal grounds resolver.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\Grounds
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-one-resolver-reads-the-grounds-from-dossiq-or-from-a-read-only-snapshot-req-grd-001
 */

declare(strict_types=1);

namespace OCA\Dossiq\Woo {

	if (class_exists(WooRefusalGrounds::class) === false) {
		/**
		 * Double of dossiq's list class, with the real method names and return shape.
		 */
		class WooRefusalGrounds {

			public const LIST_VERSION = '2026-10-09';

			/** @var array<int, array<string, mixed>>|null */
			public static ?array $grounds = null;

			public static ?\Throwable $throws = null;

			/**
			 * Every ground, retired included.
			 *
			 * @return array<int, array<string, mixed>>
			 */
			public function listWithRetired(): array {
				if (self::$throws !== null) {
					throw self::$throws;
				}

				return (self::$grounds ?? []);
			}//end listWithRetired()
		}//end class
	}

	if (class_exists(WooRefusalGroundsUnavailable::class) === false) {
		/**
		 * Double of dossiq's unavailable exception.
		 */
		class WooRefusalGroundsUnavailable extends \RuntimeException {
		}//end class
	}
}

namespace OCA\Filinq\Tests\Unit\Service\Grounds {

	use OCA\Dossiq\Woo\WooRefusalGrounds;
	use OCA\Dossiq\Woo\WooRefusalGroundsUnavailable;
	use OCA\Filinq\Service\Grounds\RefusalGroundsResolver;
	use OCP\App\IAppManager;
	use PHPUnit\Framework\TestCase;
	use Psr\Container\ContainerInterface;
	use Psr\Log\NullLogger;
	use RuntimeException;

	/**
	 * Tests for RefusalGroundsResolver.
	 *
	 * @covers \OCA\Filinq\Service\Grounds\RefusalGroundsResolver
	 */
	final class RefusalGroundsResolverTest extends TestCase {

		protected function tearDown(): void {
			WooRefusalGrounds::$grounds = null;
			WooRefusalGrounds::$throws = null;
		}//end tearDown()

		/**
		 * The resolver with dossiq installed or not.
		 *
		 * @param bool        $dossiq   Whether dossiq is installed.
		 * @param string|null $snapshot The snapshot file, the vendored one when null.
		 *
		 * @return RefusalGroundsResolver
		 */
		private function resolver(bool $dossiq, ?string $snapshot = null): RefusalGroundsResolver {
			$apps = $this->createMock(IAppManager::class);
			$apps->method('isInstalled')->with('dossiq')->willReturn($dossiq);
			$container = $this->createMock(ContainerInterface::class);
			$container->method('get')->with(RefusalGroundsResolver::DOSSIQ_CLASS)->willReturn(new WooRefusalGrounds());

			return new RefusalGroundsResolver($container, $apps, new NullLogger(), $snapshot);
		}//end resolver()

		public function testWithDossiqTheListComesFromDossiq(): void {
			WooRefusalGrounds::$grounds = [
				['code' => '5.1.2.e', 'article' => '5.1', 'label' => 'Levenssfeer', 'status' => 'active'],
				['code' => '10.2.e', 'article' => '10.2', 'label' => 'Oud', 'status' => 'retired'],
			];

			$list = $this->resolver(dossiq: true)->list();

			self::assertSame(['dossiq', '2026-10-09'], [$list['source'], $list['version']]);
			self::assertSame(['5.1.2.e'], array_column($list['grounds'], 'code'));
			self::assertSame('retired', $this->resolver(dossiq: true)->byCode(' 10.2.e ')['status']);
			self::assertNull($this->resolver(dossiq: true)->byCode('5.9.9'));
		}//end testWithDossiqTheListComesFromDossiq()

		public function testWithoutDossiqTheSnapshotAnswers(): void {
			$list = $this->resolver(dossiq: false)->list();

			self::assertSame(['snapshot', '1.0.0'], [$list['source'], $list['version']]);
			self::assertSame('Eerbiediging van de persoonlijke levenssfeer', $this->resolver(dossiq: false)->byCode('5.1.2.e')['label']);
		}//end testWithoutDossiqTheSnapshotAnswers()

		public function testAnUnavailableDossiqFallsBackToTheSnapshot(): void {
			WooRefusalGrounds::$throws = new WooRefusalGroundsUnavailable('The Woo refusal grounds cannot be read.');
			self::assertSame('snapshot', $this->resolver(dossiq: true)->list()['source']);

			WooRefusalGrounds::$throws = null;
			WooRefusalGrounds::$grounds = [];
			self::assertSame('snapshot', $this->resolver(dossiq: true)->list()['source'], 'an empty answer is not a list');
		}//end testAnUnavailableDossiqFallsBackToTheSnapshot()

		public function testAnUnreadableSnapshotThrows(): void {
			$this->expectException(RuntimeException::class);
			$this->expectExceptionMessage('The Woo refusal grounds cannot be read: dossiq is not available and the snapshot cannot be read.');

			$this->resolver(dossiq: false, snapshot: __DIR__ . '/does-not-exist.json')->list();
		}//end testAnUnreadableSnapshotThrows()

		public function testTheSnapshotHasTheListShape(): void {
			$grounds = $this->resolver(dossiq: false)->listWithRetired()['grounds'];

			self::assertNotSame([], $grounds);
			foreach ($grounds as $ground) {
				self::assertSame([], array_values(array_diff(RefusalGroundsResolver::KEYS, array_keys($ground))), $ground['code'] . ' lacks a key');
			}
		}//end testTheSnapshotHasTheListShape()
	}//end class
}
