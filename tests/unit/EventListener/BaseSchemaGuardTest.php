<?php

/**
 * Unit tests for BaseRefusalGroundGuard
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
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

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\BaseRefusalGroundGuard;
use OCA\Filinq\Service\Grounds\RefusalGroundsLegacyMap;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Event\ObjectCreatingEvent;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A new `base` object naming a Woo ground is stopped; a tenant's own basis,
 * another schema and other events pass.
 *
 * @covers \OCA\Filinq\EventListener\BaseRefusalGroundGuard
 * @uses   \OCA\Filinq\Service\Grounds\RefusalGroundsLegacyMap
 */
class BaseSchemaGuardTest extends TestCase {

	/**
	 * A pre-create event for an object of a schema with a slug.
	 *
	 * @param string $schema The schema slug.
	 * @param string $slug   The object's slug.
	 *
	 * @return ObjectCreatingEvent
	 */
	private static function creating(string $schema, string $slug): ObjectCreatingEvent {
		$object = new ObjectEntity();
		$object->setSchema($schema);
		$object->setObject(['slug' => $slug, 'name' => 'Grond']);

		return new ObjectCreatingEvent($object);

	}//end creating()

	/**
	 * The guard with the shipped legacy map and a logger that expects the given warning count.
	 *
	 * Schema id 42 is `base`, id 7 is `dossier`, id 500 fails to load; any
	 * other reference does not exist.
	 *
	 * @param int                          $warnings How often a refusal is logged.
	 * @param RefusalGroundsLegacyMap|null $legacy   The old slugs; null is the shipped map.
	 *
	 * @return BaseRefusalGroundGuard
	 */
	private function guard(int $warnings, ?RefusalGroundsLegacyMap $legacy = null): BaseRefusalGroundGuard {
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->exactly($warnings))->method('warning')->with('filinq.grounds.woo-base-refused');

		$mapper = new class {
			/**
			 * A schema stand-in by id.
			 *
			 * @param string $id The schema id.
			 *
			 * @return object
			 */
			public function find(string $id): object {
				$slugs = ['42' => 'base', '7' => 'dossier'];
				if ($id === '500') {
					throw new RuntimeException('Database unavailable');
				}

				if (isset($slugs[$id]) === false) {
					throw new DoesNotExistException('Schema not found');
				}

				return new class ($slugs[$id]) {
					/**
					 * @param string $slug The schema slug.
					 */
					public function __construct(private readonly string $slug) {
					}

					/**
					 * @return string
					 */
					public function getSlug(): string {
						return $this->slug;
					}
				};
			}
		};

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->with('OCA\OpenRegister\Db\SchemaMapper')->willReturn($mapper);

		return new BaseRefusalGroundGuard(($legacy ?? new RefusalGroundsLegacyMap()), $logger, $container);

	}//end guard()

	/**
	 * A new Woo ground on `base`, by code or by old slug, is refused.
	 *
	 * @return void
	 */
	public function testANewWooGroundIsRefused(): void {
		$guard = $this->guard(2);

		$byCode = self::creating('base', '5.1.2.e');
		$guard->handle($byCode);
		$this->assertTrue($byCode->isPropagationStopped());

		$bySlug = self::creating('Base', 'art-5-1-2-e');
		$guard->handle($bySlug);
		$this->assertTrue($bySlug->isPropagationStopped());

	}//end testANewWooGroundIsRefused()

	/**
	 * OpenRegister stores the numeric schema id on the entity: the guard
	 * resolves it to the slug and still refuses a Woo ground on `base`.
	 *
	 * @return void
	 */
	public function testANumericBaseSchemaIdIsResolved(): void {
		$guard = $this->guard(1);

		$onBase = self::creating('42', '5.1.2.e');
		$guard->handle($onBase);
		$this->assertTrue($onBase->isPropagationStopped());

		$onDossier = self::creating('7', '5.1.2.e');
		$guard->handle($onDossier);
		$this->assertFalse($onDossier->isPropagationStopped());

		$unknown = self::creating('999', '5.1.2.e');
		$guard->handle($unknown);
		$this->assertFalse($unknown->isPropagationStopped());

	}//end testANumericBaseSchemaIdIsResolved()

	/**
	 * A tenant's own basis, a Woo-like slug on another schema, and another event all pass.
	 *
	 * @return void
	 */
	public function testOtherWritesPass(): void {
		$guard = $this->guard(0);

		$own = self::creating('base', 'eigen-grond');
		$guard->handle($own);
		$this->assertFalse($own->isPropagationStopped());

		$other = self::creating('dossier', '5.1.2.e');
		$guard->handle($other);
		$this->assertFalse($other->isPropagationStopped());

		$event = new Event();
		$guard->handle($event);
		$this->assertFalse($event->isPropagationStopped());

	}//end testOtherWritesPass()

	/**
	 * A schema lookup that fails, rather than finding no schema, refuses a Woo
	 * ground instead of letting it through unchecked.
	 *
	 * @return void
	 */
	public function testAFailedSchemaLookupRefusesAWooGround(): void {
		$guard = $this->guard(warnings: 1);

		$event = self::creating(schema: '500', slug: '5.1.2.e');
		$guard->handle($event);
		$this->assertTrue(condition: $event->isPropagationStopped());

		$own = self::creating(schema: '500', slug: 'eigen-grond');
		$guard->handle($own);
		$this->assertFalse(condition: $own->isPropagationStopped());

	}//end testAFailedSchemaLookupRefusesAWooGround()

	/**
	 * When the old slugs cannot be read, a `base` write is refused and a write
	 * on another schema still passes.
	 *
	 * @return void
	 */
	public function testAnUnreadableLegacyMapRefusesBaseWrites(): void {
		$guard = $this->guard(warnings: 1, legacy: new RefusalGroundsLegacyMap(path: '/nonexistent/refusal-grounds-legacy-map.json'));

		$onBase = self::creating(schema: 'base', slug: 'art-5-1-2-e');
		$guard->handle($onBase);
		$this->assertTrue(condition: $onBase->isPropagationStopped());

		$onDossier = self::creating(schema: '7', slug: 'art-5-1-2-e');
		$guard->handle($onDossier);
		$this->assertFalse(condition: $onDossier->isPropagationStopped());

	}//end testAnUnreadableLegacyMapRefusesBaseWrites()
}//end class
