<?php

/**
 * Unit tests for RepointWooGrounds
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Repair
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Repair;

use OCA\Filinq\Repair\RepointWooGrounds;
use OCA\Filinq\Service\DossierObjectReader;
use OCA\Filinq\Service\DossierObjectRepository;
use OCA\Filinq\Service\Grounds\WooGroundsLegacyMap;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Stored Woo slugs are re-pointed to dossiq's codes one to one, an unmapped
 * slug is reported and kept, and a second run changes nothing.
 *
 * @covers \OCA\Filinq\Repair\RepointWooGrounds
 * @uses   \OCA\Filinq\Service\DossierObjectReader
 * @uses   \OCA\Filinq\Service\Grounds\WooGroundsLegacyMap
 */
class RepointWooGroundsTest extends TestCase {

	/**
	 * The upgrade output lines, as [level, message].
	 *
	 * @var list<array{0: string, 1: string}>
	 */
	private array $lines = [];

	/**
	 * The stored app config, key => value.
	 *
	 * @var array<string, string>
	 */
	private array $config = [];

	/**
	 * A fake object service over objects per schema, recording every save.
	 *
	 * @param array<string, list<array<string, mixed>>> $objects Objects per schema.
	 *
	 * @return object
	 */
	private static function objectService(array $objects): object {
		return new class($objects) {
			/**
			 * Saves as [schema, uuid, object].
			 *
			 * @var list<array{0: string, 1: string, 2: array<string, mixed>}>
			 */
			public array $saved = [];

			/**
			 * Constructor.
			 *
			 * @param array<string, list<array<string, mixed>>> $objects Objects per schema.
			 */
			public function __construct(public array $objects) {
			}

			/**
			 * The objects of the filtered schema.
			 *
			 * @param array<string, mixed> $config The query.
			 *
			 * @return list<array<string, mixed>>
			 */
			public function findAll(array $config): array {
				return ($this->objects[$config['filters']['schema']] ?? []);
			}

			/**
			 * Store an object back under its uuid.
			 *
			 * @param array<string, mixed> $object         The object.
			 * @param string               $register       The register.
			 * @param string               $schema         The schema.
			 * @param string               $uuid           The uuid.
			 * @param bool                 $_rbac          RBAC.
			 * @param bool                 $_multitenancy  Multitenancy.
			 *
			 * @return array<string, mixed>
			 */
			public function saveObject(array $object, string $register, string $schema, string $uuid, bool $_rbac, bool $_multitenancy): array {
				$this->saved[] = [$schema, $uuid, $object];
				foreach ($this->objects[$schema] as $i => $stored) {
					if (($stored['@self']['uuid'] ?? '') === $uuid) {
						$this->objects[$schema][$i] = ['@self' => $stored['@self']] + $object;
					}
				}

				return $object;
			}
		};

	}//end objectService()

	/**
	 * The repair over a fake object service (or none) and a legacy map.
	 *
	 * @param object|null              $objectService The object service, null without OpenRegister.
	 * @param WooGroundsLegacyMap|null $legacy        The map; the shipped one when null.
	 *
	 * @return RepointWooGrounds
	 */
	private function repair(?object $objectService, ?WooGroundsLegacyMap $legacy = null): RepointWooGrounds {
		$repository = $this->createMock(DossierObjectRepository::class);
		$repository->method('objectService')->willReturn($objectService);

		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(fn (string $app, string $key, string $default = ''): string => ($this->config[$key] ?? $default));
		$appConfig->method('setValueString')->willReturnCallback(
			function (string $app, string $key, string $value): bool {
				$this->config[$key] = $value;
				return true;
			}
		);

		return new RepointWooGrounds($repository, new DossierObjectReader(), ($legacy ?? new WooGroundsLegacyMap()), $appConfig, $this->createMock(LoggerInterface::class));

	}//end repair()

	/**
	 * An output that records its lines.
	 *
	 * @return IOutput
	 */
	private function upgradeOutput(): IOutput {
		$output = $this->createMock(IOutput::class);
		$output->method('info')->willReturnCallback(function (string $message): void {
			$this->lines[] = ['info', $message];
		});
		$output->method('warning')->willReturnCallback(function (string $message): void {
			$this->lines[] = ['warning', $message];
		});

		return $output;

	}//end upgradeOutput()

	/**
	 * A map file with one unmapped slug, beside the shipped one-to-one slugs.
	 *
	 * @return WooGroundsLegacyMap
	 */
	private static function mapWithAnUnmappedSlug(): WooGroundsLegacyMap {
		$path = tempnam(sys_get_temp_dir(), 'woo-map');
		file_put_contents($path, json_encode(['map' => ['art-5-1-2-e' => '5.1.2.e', 'art-5-1-5' => 'unmapped']]));

		return new WooGroundsLegacyMap($path);

	}//end mapWithAnUnmappedSlug()

	/**
	 * A dossier citing an old slug holds the code afterwards; other grounds stay.
	 *
	 * @return void
	 */
	public function testAOneToOneSlugIsRewritten(): void {
		$objects = self::objectService(['dossier' => [['@self' => ['uuid' => 'd-1'], 'title' => 'Dossier', 'bases' => ['art-5-1-2-e', 'eigen-grond', '5.1.4']]]]);

		$this->repair($objects)->run($this->upgradeOutput());

		$this->assertSame([['dossier', 'd-1', ['bases' => ['5.1.2.e', 'eigen-grond', '5.1.4'], 'title' => 'Dossier']]], $objects->saved);
		$this->assertContains(['info', 'RepointWooGrounds: re-pointed 1 references, left 0.'], $this->lines);

	}//end testAOneToOneSlugIsRewritten()

	/**
	 * An unmapped slug is kept and the report names the object and the slug.
	 *
	 * @return void
	 */
	public function testAnUnmappedSlugIsReported(): void {
		$objects = self::objectService(['publicationProhibition' => [['@self' => ['uuid' => 'p-1'], 'bases' => ['art-5-1-5']]]]);

		$this->repair($objects, self::mapWithAnUnmappedSlug())->run($this->upgradeOutput());

		$this->assertSame([], $objects->saved);
		$this->assertContains(['warning', 'RepointWooGrounds: publicationProhibition p-1 (art-5-1-5) has no one-to-one dossiq code and was left as it is.'], $this->lines);

	}//end testAnUnmappedSlugIsReported()

	/**
	 * A second run writes nothing.
	 *
	 * @return void
	 */
	public function testTheRepairIsIdempotent(): void {
		$objects = self::objectService(['publicationConsent' => [['@self' => ['uuid' => 'c-1'], 'bases' => ['art-5-1-1-a']]]]);
		$this->config['filinq.grondslagen.entity_type_bases'] = json_encode(['PERSON' => ['art-5-1-2-e'], 'BSN' => ['5.1.1.e']]);

		$repair = $this->repair($objects);
		$repair->run($this->upgradeOutput());
		$this->assertCount(1, $objects->saved);
		$this->assertSame(['PERSON' => ['5.1.2.e'], 'BSN' => ['5.1.1.e']], json_decode($this->config['filinq.grondslagen.entity_type_bases'], true));

		$repair->run($this->upgradeOutput());
		$this->assertCount(1, $objects->saved);
		$this->assertSame('RepointWooGrounds: re-pointed 0 references, left 0.', end($this->lines)[1]);

	}//end testTheRepairIsIdempotent()

	/**
	 * Without OpenRegister the entity-type mapping is still re-pointed.
	 *
	 * @return void
	 */
	public function testWithoutOpenRegisterOnlyTheMappingIsRepointed(): void {
		$this->config['filinq.grondslagen.entity_type_bases'] = json_encode(['PERSON' => ['art-5-1-2-e']]);

		$this->repair(null)->run($this->upgradeOutput());

		$this->assertSame(['PERSON' => ['5.1.2.e']], json_decode($this->config['filinq.grondslagen.entity_type_bases'], true));
		$this->assertContains(['info', 'RepointWooGrounds: OpenRegister is not available, no objects were re-pointed.'], $this->lines);

	}//end testWithoutOpenRegisterOnlyTheMappingIsRepointed()

	/**
	 * An unreadable map changes nothing at all.
	 *
	 * @return void
	 */
	public function testAnUnreadableMapChangesNothing(): void {
		$objects = self::objectService(['dossier' => [['@self' => ['uuid' => 'd-1'], 'bases' => ['art-5-1-2-e']]]]);
		$legacy = $this->createMock(WooGroundsLegacyMap::class);
		$legacy->method('all')->willThrowException(new RuntimeException('gone'));

		$this->repair($objects, $legacy)->run($this->upgradeOutput());

		$this->assertSame([], $objects->saved);
		$this->assertSame([['warning', 'RepointWooGrounds: the legacy map cannot be read, nothing was changed: gone']], $this->lines);

	}//end testAnUnreadableMapChangesNothing()
}//end class
