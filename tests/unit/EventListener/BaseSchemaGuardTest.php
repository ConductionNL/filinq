<?php

/**
 * Unit tests for BaseWooGroundGuard
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

use OCA\Filinq\EventListener\BaseWooGroundGuard;
use OCA\Filinq\Service\Grounds\WooGroundsLegacyMap;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Event\ObjectCreatingEvent;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * A new `base` object naming a Woo ground is stopped; a tenant's own basis,
 * another schema and other events pass.
 *
 * @covers \OCA\Filinq\EventListener\BaseWooGroundGuard
 * @uses   \OCA\Filinq\Service\Grounds\WooGroundsLegacyMap
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
	 * @param int $warnings How often a refusal is logged.
	 *
	 * @return BaseWooGroundGuard
	 */
	private function guard(int $warnings): BaseWooGroundGuard {
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->exactly($warnings))->method('warning')->with('filinq.grounds.woo-base-refused');

		return new BaseWooGroundGuard(new WooGroundsLegacyMap(), $logger);

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
}//end class
