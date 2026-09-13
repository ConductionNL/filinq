<?php

/**
 * Unit tests for DossierObjectReader
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DossierObjectReader;
use PHPUnit\Framework\TestCase;

/**
 * OpenRegister hands back objects of several shapes depending on how they were
 * loaded, and every caller that guessed wrong got an empty payload rather than
 * an error. The cases below are the shapes that actually arrive, plus the one
 * that arrives when the read failed.
 *
 * NOTHING HERE THROWS. A dossier the reader cannot make sense of reads as an
 * empty payload with a default status, because the surface has to render
 * something and a blank row a reader can see beats a 500 they cannot.
 *
 * phpcs:disable CustomSniffs.Functions.NamedParameters
 *
 * @covers \OCA\Filinq\Service\DossierObjectReader
 */
class DossierObjectReaderTest extends TestCase {

	/**
	 * The subject.
	 *
	 * @var DossierObjectReader
	 */
	private DossierObjectReader $reader;

	/**
	 * Build the subject.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->reader = new DossierObjectReader();

	}//end setUp()

	/**
	 * The ordinary shape: an entity that serialises itself.
	 *
	 * @return void
	 */
	public function testAJsonSerialisableObjectAnswersItsPayload(): void {
		$object = new class {

			/**
			 * @return array<string, mixed> The payload.
			 */
			public function jsonSerialize(): array {
				return ['name' => 'Woo 2026-001'];
			}
		};

		$this->assertSame(['name' => 'Woo 2026-001'], $this->reader->payloadOf($object));

	}//end testAJsonSerialisableObjectAnswersItsPayload()

	/**
	 * The second shape: `getObject()`, which a cached read hands back.
	 *
	 * The fallback matters because the object declares BOTH methods and
	 * `jsonSerialize()` answers with something that is not an array. Reading
	 * only the first method would return `(array)$object` here, which is the
	 * object's private properties rather than its payload.
	 *
	 * @return void
	 */
	public function testAnObjectWhoseSerialisationIsNotAnArrayFallsThroughToGetObject(): void {
		$object = new class {

			/**
			 * @return string Deliberately not an array.
			 */
			public function jsonSerialize(): string {
				return 'not an array';
			}

			/**
			 * @return array<string, mixed> The payload.
			 */
			public function getObject(): array {
				return ['name' => 'from getObject'];
			}
		};

		$this->assertSame(['name' => 'from getObject'], $this->reader->payloadOf($object));

	}//end testAnObjectWhoseSerialisationIsNotAnArrayFallsThroughToGetObject()

	/**
	 * Neither method: the object's own properties, rather than nothing.
	 *
	 * @return void
	 */
	public function testAPlainObjectReadsAsItsProperties(): void {
		$object = (object)['name' => 'plain', 'status' => 'open'];

		$this->assertSame(['name' => 'plain', 'status' => 'open'], $this->reader->payloadOf($object));

	}//end testAPlainObjectReadsAsItsProperties()

	/**
	 * `getObject()` answering with something that is not an array falls
	 * through too, rather than being handed on as a payload.
	 *
	 * @return void
	 */
	public function testANonArrayGetObjectFallsThroughAsWell(): void {
		$object = new class {

			/**
			 * @return null Deliberately not an array.
			 */
			public function getObject() {
				return null;
			}
		};

		$this->assertSame([], $this->reader->payloadOf($object));

	}//end testANonArrayGetObjectFallsThroughAsWell()

	/**
	 * The uuid is read from `@self` first, then from the payload's own keys.
	 *
	 * @return void
	 */
	public function testTheUuidPrefersTheSelfBlock(): void {
		$object = (object)[];

		$this->assertSame(
			'from-self',
			$this->reader->uuidOf($object, ['@self' => ['id' => 'from-self'], 'id' => 'from-id'])
		);
		$this->assertSame('from-id', $this->reader->uuidOf($object, ['id' => 'from-id']));
		$this->assertSame('from-uuid', $this->reader->uuidOf($object, ['uuid' => 'from-uuid']));

	}//end testTheUuidPrefersTheSelfBlock()

	/**
	 * A payload carrying no id at all asks the OBJECT, which is the shape a
	 * freshly saved entity comes back in.
	 *
	 * @return void
	 */
	public function testAPayloadWithoutAnIdAsksTheObject(): void {
		$object = new class {

			/**
			 * @return string The uuid.
			 */
			public function getUuid(): string {
				return 'from-the-entity';
			}
		};

		$this->assertSame('from-the-entity', $this->reader->uuidOf($object, []));

	}//end testAPayloadWithoutAnIdAsksTheObject()

	/**
	 * Nothing anywhere is '' rather than an error: the caller decides.
	 *
	 * @return void
	 */
	public function testAnObjectWithNoIdAnywhereAnswersEmpty(): void {
		$this->assertSame('', $this->reader->uuidOf((object)[], []));

	}//end testAnObjectWithNoIdAnywhereAnswersEmpty()

	/**
	 * An absent or blank status reads as the initial state.
	 *
	 * `status` was added optional and existing objects were deliberately not
	 * migrated, so absence is a value with a meaning rather than missing data.
	 *
	 * @return void
	 */
	public function testAnAbsentOrBlankStatusReadsAsTheInitialState(): void {
		$this->assertSame(DossierObjectReader::DEFAULT_STATUS, $this->reader->statusOf([]));
		$this->assertSame(DossierObjectReader::DEFAULT_STATUS, $this->reader->statusOf(['status' => '   ']));
		$this->assertSame('closed', $this->reader->statusOf(['status' => 'closed']));

	}//end testAnAbsentOrBlankStatusReadsAsTheInitialState()

	/**
	 * References come back as strings, whatever they were stored as, and a
	 * `documents` that is not a list reads as none rather than crashing the
	 * index it is rendered into.
	 *
	 * @return void
	 */
	public function testDocumentReferencesAreNormalisedToStrings(): void {
		$this->assertSame(['41', '42'], $this->reader->documentRefs(['documents' => [41, '42']]));
		$this->assertSame([], $this->reader->documentRefs([]));
		$this->assertSame([], $this->reader->documentRefs(['documents' => 'not a list']));

	}//end testDocumentReferencesAreNormalisedToStrings()

	/**
	 * The register and schema have ONE definition, and it is this one. Three
	 * classes read them; a second literal is a second thing to forget when the
	 * register moves, which is what the fleet rename cost elsewhere.
	 *
	 * @return void
	 */
	public function testTheRegisterAndSchemaAreDeclaredHere(): void {
		$this->assertSame('filinq', DossierObjectReader::REGISTER);
		$this->assertSame('dossier', DossierObjectReader::SCHEMA);

	}//end testTheRegisterAndSchemaAreDeclaredHere()

	/**
	 * findAll() takes a CONFIG ARRAY, not `register:`/`schema:` named
	 * arguments. Those exist on `find()` and `saveObject()` but not here, and
	 * calling it the other way throws "Unknown named parameter $register" at
	 * runtime, which every guarded caller then swallows.
	 *
	 * @return void
	 */
	public function testFindAllPassesAConfigArrayNotNamedArguments(): void {
		$objectService = new class {

			/**
			 * @var array<string, mixed>
			 */
			public array $seen = [];

			/**
			 * @param array<string, mixed> $config The find config.
			 *
			 * @return array<int, object> Nothing; the CONFIG is the subject.
			 */
			public function findAll(array $config = []): array {
				$this->seen = $config;

				return [];
			}
		};

		$this->reader->findAllOf($objectService, 'base');

		$this->assertSame(
			['filters' => ['register' => 'filinq', 'schema' => 'base']],
			$objectService->seen
		);

	}//end testFindAllPassesAConfigArrayNotNamedArguments()

	/**
	 * The fallback helper, which decides what an empty label falls back to.
	 *
	 * @return void
	 */
	public function testTheFirstNonEmptyValueWins(): void {
		$this->assertSame('given', $this->reader->firstNonEmpty('given', 'fallback'));
		$this->assertSame('fallback', $this->reader->firstNonEmpty('', 'fallback'));

	}//end testTheFirstNonEmptyValueWins()

}//end class
