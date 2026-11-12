<?php

/**
 * How AnonymizationPersistenceService reads saved objects and source nodes.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymizationPersistenceService;
use OCA\Filinq\Service\ConsentCrudService;
use OCA\Filinq\Service\ConsentService;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionMethod;

/**
 * Pins the id, payload and source-metadata readers on every shape they accept.
 *
 * @covers \OCA\Filinq\Service\AnonymizationPersistenceService
 * @uses \OCA\Filinq\Service\SavedObjectReader
 */
class AnonymizationPersistenceReadersTest extends TestCase {

	/**
	 * Call one of the service's private readers.
	 *
	 * @param string             $method    The reader.
	 * @param array<string, mixed> $arguments Its named arguments.
	 *
	 * @return mixed
	 */
	private function read(string $method, array $arguments): mixed {
		$service = new AnonymizationPersistenceService(
			logger: new NullLogger(),
			locator: $this->createMock(OpenRegisterServiceLocator::class),
			consentCrud: $this->createMock(ConsentCrudService::class),
			consentService: $this->createMock(ConsentService::class),
		);

		return (new ReflectionMethod($service, $method))->invokeArgs($service, $arguments);

	}//end read()

	/**
	 * The saved id comes from the uuid, then the id, then the serialised or array `@self` block, then `id`.
	 *
	 * @return void
	 */
	public function testTheSavedObjectIdIsFoundOnEveryShape(): void {
		$both = new class {
			public function getUuid(): string { return 'uuid-1'; }
			public function getId(): int { return 7; }
		};
		$emptyUuid = new class {
			public function getUuid(): string { return ''; }
			public function getId(): int { return 7; }
			public function jsonSerialize(): array { return ['id' => 'json-id']; }
		};
		$serialised = new class {
			public function getId(): int { return 0; }
			public function jsonSerialize(): array { return ['@self' => ['uuid' => 'self-uuid'], 'id' => 'outer']; }
		};
		$serialisedScalar = new class {
			public function jsonSerialize(): string { return 'x'; }
		};

		$this->assertSame('uuid-1', $this->read('extractSavedObjectId', ['saved' => $both]));
		$this->assertSame('7', $this->read('extractSavedObjectId', ['saved' => $emptyUuid]));
		$this->assertSame('self-uuid', $this->read('extractSavedObjectId', ['saved' => $serialised]));
		$this->assertNull($this->read('extractSavedObjectId', ['saved' => $serialisedScalar]));
		$this->assertNull($this->read('extractSavedObjectId', ['saved' => new \stdClass()]));
		$this->assertSame('a', $this->read('extractSavedObjectId', ['saved' => ['@self' => ['id' => 'a', 'uuid' => 'b'], 'id' => 'c']]));
		$this->assertNull($this->read('extractSavedObjectId', ['saved' => ['@self' => ['id' => '', 'uuid' => 'b']]]));
		$this->assertSame('c', $this->read('extractSavedObjectId', ['saved' => ['id' => 'c']]));
		$this->assertSame('c', $this->read('extractSavedObjectId', ['saved' => ['@self' => ['id' => null], 'id' => 'c']]));
		$this->assertNull($this->read('extractSavedObjectId', ['saved' => ['id' => '0']]));
		$this->assertNull($this->read('extractSavedObjectId', ['saved' => 'uuid']));

	}//end testTheSavedObjectIdIsFoundOnEveryShape()

	/**
	 * The payload comes from getObject (with the uuid restored as `@self`), else from jsonSerialize, else empty.
	 *
	 * @return void
	 */
	public function testTheObjectPayloadIsReadOnEveryShape(): void {
		$withUuid = new class {
			public function getObject(): array { return ['name' => 'x']; }
			public function getUuid(): string { return 'u-1'; }
		};
		$withSelf = new class {
			public function getObject(): array { return ['@self' => ['id' => 'keep']]; }
			public function getUuid(): string { return 'u-1'; }
		};
		$nullUuid = new class {
			public function getObject(): array { return ['name' => 'y']; }
			public function getUuid(): ?string { return null; }
		};
		$noUuid = new class {
			public function getObject(): array { return ['name' => 'z']; }
		};
		$fallsBack = new class {
			public function getObject(): ?array { return null; }
			public function jsonSerialize(): array { return ['from' => 'json']; }
		};
		$nothing = new class {
			public function jsonSerialize(): string { return 'x'; }
		};

		$this->assertSame(['name' => 'x', '@self' => ['id' => 'u-1']], $this->read('extractObjectPayload', ['candidate' => $withUuid]));
		$this->assertSame(['@self' => ['id' => 'keep']], $this->read('extractObjectPayload', ['candidate' => $withSelf]));
		$this->assertSame(['name' => 'y'], $this->read('extractObjectPayload', ['candidate' => $nullUuid]));
		$this->assertSame(['name' => 'z'], $this->read('extractObjectPayload', ['candidate' => $noUuid]));
		$this->assertSame(['from' => 'json'], $this->read('extractObjectPayload', ['candidate' => $fallsBack]));
		$this->assertSame([], $this->read('extractObjectPayload', ['candidate' => $nothing]));
		$this->assertSame([], $this->read('extractObjectPayload', ['candidate' => new \stdClass()]));

	}//end testTheObjectPayloadIsReadOnEveryShape()

	/**
	 * Name, path and owner are applied only where the node offers them.
	 *
	 * @return void
	 */
	public function testSourceNodeMetadataIsAppliedWhereOffered(): void {
		$owner = new class {
			public function getUID(): string { return 'anna'; }
		};
		$full = new class ($owner) {
			public function __construct(private object $owner) {}
			public function getName(): string { return 'a.docx'; }
			public function getPath(): string { return '/anna/files/a.docx'; }
			public function getOwner(): object { return $this->owner; }
		};
		$ownerless = new class {
			public function getName(): string { return 'b.docx'; }
			public function getOwner(): ?object { return null; }
		};
		$opaqueOwner = new class {
			public function getPath(): string { return '/p'; }
			public function getOwner(): object { return new \stdClass(); }
		};

		$this->assertSame(['k' => 1], $this->read('applySourceNodeMetadata', ['object' => ['k' => 1], 'sourceNode' => null]));
		$this->assertSame(['k' => 1], $this->read('applySourceNodeMetadata', ['object' => ['k' => 1], 'sourceNode' => 'node']));
		$this->assertSame(
			['k' => 1, 'sourceFileName' => 'a.docx', 'sourceFilePath' => '/anna/files/a.docx', 'anonymizedBy' => 'anna'],
			$this->read('applySourceNodeMetadata', ['object' => ['k' => 1], 'sourceNode' => $full])
		);
		$this->assertSame(['sourceFileName' => 'b.docx'], $this->read('applySourceNodeMetadata', ['object' => [], 'sourceNode' => $ownerless]));
		$this->assertSame(['sourceFilePath' => '/p'], $this->read('applySourceNodeMetadata', ['object' => [], 'sourceNode' => $opaqueOwner]));
		$this->assertSame([], $this->read('applySourceNodeMetadata', ['object' => [], 'sourceNode' => new \stdClass()]));

	}//end testSourceNodeMetadataIsAppliedWhereOffered()
}//end class
