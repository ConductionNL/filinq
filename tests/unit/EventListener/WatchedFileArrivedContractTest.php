<?php

/**
 * Contract test: integriq's WatchedFileArrivedEvent has the surface filinq reads.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/scan-intake-from-a-watched-folder/specs/scan-intake/spec.md#requirement-filinq-takes-a-scanned-batch-that-integriqs-watched-folder-hands-over-req-scw-001
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\WatchedFileArrivedListener;
use OCA\Integriq\Event\WatchedFileArrivedEvent;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Asserts, by reflection, the literals of integriq's REQ-SFTP-005. The class
 * is integriq's own file when the sibling app sits next to filinq (the unit
 * bootstrap prefers it), so a rename in integriq fails here. integriq's
 * WatchedFileArrivedEventTest::testThePublicSurfaceIsTheContract asserts the
 * same literals on its side.
 *
 * @coversNothing
 */
class WatchedFileArrivedContractTest extends TestCase {

	public function testTheEventMatchesIntegriqsSpec(): void {
		self::assertSame(WatchedFileArrivedEvent::class, WatchedFileArrivedListener::ARRIVED_EVENT);

		$class = new ReflectionClass(WatchedFileArrivedEvent::class);

		$constructor = $class->getConstructor();
		self::assertNotNull($constructor);
		self::assertSame(
			[
				'synchronizationId' => 'string',
				'sourceId' => 'string',
				'intakeApp' => 'string',
				'fileId' => 'int',
				'path' => 'string',
				'ownerUid' => 'string',
			],
			$this->parameters(method: $constructor)
		);

		foreach (
			[
				'getSynchronizationId' => 'string',
				'getSourceId' => 'string',
				'getIntakeApp' => 'string',
				'getFileId' => 'int',
				'getPath' => 'string',
				'getOwnerUid' => 'string',
				'getResult' => 'array',
			] as $getter => $type
		) {
			$method = $class->getMethod($getter);
			self::assertSame([], $this->parameters(method: $method), $getter);
			self::assertSame($type, $this->typeName(type: $method->getReturnType()), $getter);
		}

		$accept = $class->getMethod('accept');
		self::assertSame(['appId' => 'string', 'reference' => 'string'], $this->parameters(method: $accept));
		self::assertSame('bool', $this->typeName(type: $accept->getReturnType()));

		$event = new WatchedFileArrivedEvent('sync', 'source', 'filinq', 7, 'Scans/a.pdf', 'alice');
		self::assertSame(['accepted', 'intakeApp', 'reference'], array_keys($event->getResult()));
	}//end testTheEventMatchesIntegriqsSpec()

	/**
	 * A method's parameters as name => type.
	 *
	 * @param \ReflectionMethod $method The method.
	 *
	 * @return array<string, string|null>
	 */
	private function parameters(\ReflectionMethod $method): array {
		$parameters = [];
		foreach ($method->getParameters() as $parameter) {
			$parameters[$parameter->getName()] = $this->typeName(type: $parameter->getType());
		}

		return $parameters;
	}//end parameters()

	/**
	 * The name of a single type, or null for none or a union.
	 *
	 * @param \ReflectionType|null $type The type.
	 *
	 * @return string|null
	 */
	private function typeName(?\ReflectionType $type): ?string {
		if (($type instanceof ReflectionNamedType) === false) {
			return null;
		}

		return $type->getName();
	}//end typeName()
}//end class
