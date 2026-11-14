<?php

/**
 * RefusalGroundsResolver: dossiq's list when it can answer, else the snapshot.
 *
 * dossiq's `WooRefusalGrounds` is a double named to the resolver; dossiq's
 * `WooRefusalGroundsUnavailable` is declared here when dossiq is not
 * installed, the way dossiq ships it (a RuntimeException).
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service\Grounds
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-one-resolver-reads-the-grounds-from-dossiq-or-from-a-read-only-snapshot-req-grd-001
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Dossiq\Woo {
	if (class_exists(WooRefusalGroundsUnavailable::class) === false) {
		/**
		 * dossiq's own exception, as it ships it.
		 */
		class WooRefusalGroundsUnavailable extends \RuntimeException {
		}
	}
}

namespace OCA\Filinq\Tests\Unit\Service\Grounds {

	use OCA\Dossiq\Woo\WooRefusalGroundsUnavailable;
	use OCA\Filinq\Service\Grounds\RefusalGroundsResolver;
	use OCP\App\IAppManager;
	use PHPUnit\Framework\TestCase;
	use Psr\Container\ContainerInterface;
	use Psr\Log\LoggerInterface;
	use RuntimeException;

	/**
	 * A stand-in for dossiq's WooRefusalGrounds.
	 */
	class FakeWooRefusalGrounds {

		public const LIST_VERSION = '2026-10-09';

		/**
		 * @param array<int, array<string, mixed>>|\Throwable $answer What list() answers or throws.
		 */
		public function __construct(private array|\Throwable $answer, private array $retired=[]) {
		}

		/**
		 * @return array<int, array<string, mixed>>
		 */
		public function listWithRetired(): array {
			return array_merge($this->list(), $this->retired);
		}

		/**
		 * @return array<int, array<string, mixed>>
		 */
		public function list(): array {
			if ($this->answer instanceof \Throwable) {
				throw $this->answer;
			}

			return $this->answer;
		}
	}

	/**
	 * @covers \OCA\Filinq\Service\Grounds\RefusalGroundsResolver
	 */
	class RefusalGroundsResolverTest extends TestCase {

		/**
		 * One dossiq ground.
		 */
		private const GROUND = ['id' => 'woo-ground-5-1-2-e', 'code' => '5.1.2.e', 'label' => 'Eerbiediging van de persoonlijke levenssfeer', 'status' => 'active'];

		/**
		 * The resolver over dossiq's state.
		 *
		 * @param bool                                              $enabled      Whether dossiq is installed and enabled.
		 * @param array<int, array<string, mixed>>|\Throwable|null  $dossiq       What dossiq's list answers; null when its class is absent.
		 * @param string                                            $snapshotPath The snapshot file.
		 * @param array<int, array<string, mixed>>                  $retired      The retired grounds dossiq adds to listWithRetired().
		 *
		 * @return RefusalGroundsResolver
		 */
		private function resolver(bool $enabled, array|\Throwable|null $dossiq, string $snapshotPath = RefusalGroundsResolver::SNAPSHOT, array $retired = []): RefusalGroundsResolver {
			$apps = $this->createMock(IAppManager::class);
			$apps->method('isEnabledForAnyone')->with('dossiq')->willReturn($enabled);

			$container = $this->createMock(ContainerInterface::class);
			$class     = 'OCA\\Dossiq\\Woo\\NoSuchGroundsClass';
			if ($dossiq !== null) {
				$class = FakeWooRefusalGrounds::class;
				$container->method('get')->with(FakeWooRefusalGrounds::class)->willReturn(new FakeWooRefusalGrounds($dossiq, $retired));
			} else {
				$container->expects($this->never())->method('get');
			}

			return new RefusalGroundsResolver($apps, $container, $this->createMock(LoggerInterface::class), $class, $snapshotPath);

		}//end resolver()

		public function testWithDossiqTheListComesFromDossiq(): void {
			$this->assertSame(
				['source' => 'dossiq', 'version' => '2026-10-09', 'grounds' => [self::GROUND]],
				$this->resolver(true, [self::GROUND])->list()
			);

		}//end testWithDossiqTheListComesFromDossiq()

		public function testWithoutDossiqTheSnapshotAnswers(): void {
			foreach ([[false, [self::GROUND]], [true, null]] as [$enabled, $dossiq]) {
				$answer = $this->resolver($enabled, $dossiq)->list();
				$this->assertSame('snapshot', $answer['source']);
				$this->assertSame('1.0.0', $answer['version']);
				$this->assertNotSame([], $answer['grounds']);
			}

		}//end testWithoutDossiqTheSnapshotAnswers()

		public function testAnUnavailableDossiqFallsBackToTheSnapshot(): void {
			$answer = $this->resolver(true, new WooRefusalGroundsUnavailable('register gone'))->list();

			$this->assertSame('snapshot', $answer['source']);

		}//end testAnUnavailableDossiqFallsBackToTheSnapshot()

		public function testAnyOtherDossiqFailureIsNotHidden(): void {
			$this->expectException(\LogicException::class);
			$this->resolver(true, new \LogicException('bug in dossiq'))->list();

		}//end testAnyOtherDossiqFailureIsNotHidden()

		public function testAnUnreadableSnapshotThrows(): void {
			$broken = tempnam(sys_get_temp_dir(), 'grounds');
			file_put_contents($broken, '{"version":"x"');
			try {
				foreach ([$broken, '/nonexistent/snapshot.json'] as $path) {
					try {
						$this->resolver(false, null, $path)->list();
						$this->fail('answered from ' . $path);
					} catch (RuntimeException $e) {
						$this->assertSame('The Woo refusal grounds snapshot cannot be read.', $e->getMessage());
					}
				}
			} finally {
				unlink($broken);
			}

		}//end testAnUnreadableSnapshotThrows()

		public function testASnapshotWithoutActiveGroundsThrowsRatherThanAnswerEmpty(): void {
			$empty = tempnam(sys_get_temp_dir(), 'grounds');
			file_put_contents($empty, json_encode(['version' => 'x', 'grounds' => [['code' => '5.1', 'status' => 'retired']]]));
			try {
				$this->expectExceptionMessage('The Woo refusal grounds snapshot holds no active grounds.');
				$this->resolver(false, null, $empty)->list();
			} finally {
				unlink($empty);
			}

		}//end testASnapshotWithoutActiveGroundsThrowsRatherThanAnswerEmpty()

		public function testTheSnapshotHasTheListShape(): void {
			$keys = ['id', 'code', 'article', 'paragraph', 'letter', 'label', 'description', 'parent', 'status', 'legalSource', 'kind', 'citable'];
			$grounds = $this->resolver(false, null)->list()['grounds'];

			foreach ($grounds as $ground) {
				$this->assertSame($keys, array_keys($ground), (string)$ground['code']);
				$this->assertSame('active', $ground['status']);
			}

			$this->assertContains('5.1.2.e', array_column($grounds, 'code'));

		}//end testTheSnapshotHasTheListShape()

		public function testListWithRetiredAndByCodeIncludeDossiqsRetiredGrounds(): void {
			$retired  = ['id' => 'woo-ground-10-2-e', 'code' => '10.2.e', 'label' => 'Oud', 'status' => 'retired'];
			$resolver = $this->resolver(true, [self::GROUND], RefusalGroundsResolver::SNAPSHOT, [$retired]);

			$this->assertSame([self::GROUND], $resolver->list()['grounds']);
			$this->assertSame(
				['source' => 'dossiq', 'version' => '2026-10-09', 'grounds' => [self::GROUND, $retired]],
				$resolver->listWithRetired()
			);
			$this->assertSame($retired, $resolver->byCode(' 10.2.e '));
			$this->assertSame(self::GROUND, $resolver->byCode('5.1.2.e'));
			$this->assertNull($resolver->byCode('5.9.9'));

		}//end testListWithRetiredAndByCodeIncludeDossiqsRetiredGrounds()

		public function testWithoutDossiqByCodeReadsTheSnapshotIncludingRetiredGrounds(): void {
			$file = tempnam(sys_get_temp_dir(), 'grounds');
			file_put_contents($file, json_encode(['version' => '9.9.9', 'grounds' => [self::GROUND, ['code' => '10.2.e', 'status' => 'retired']]]));
			try {
				$resolver = $this->resolver(false, null, $file);
				$this->assertSame([self::GROUND], $resolver->list()['grounds']);
				$all = ['source' => 'snapshot', 'version' => '9.9.9', 'grounds' => [self::GROUND, ['code' => '10.2.e', 'status' => 'retired']]];
				$this->assertSame($all, $resolver->listWithRetired());
				$this->assertSame('retired', $resolver->byCode('10.2.e')['status']);
				$this->assertSame('Eerbiediging van de persoonlijke levenssfeer', $resolver->byCode('5.1.2.e')['label']);
				$this->assertNull($resolver->byCode('5.9.9'));
			} finally {
				unlink($file);
			}

		}//end testWithoutDossiqByCodeReadsTheSnapshotIncludingRetiredGrounds()
	}//end class
}
