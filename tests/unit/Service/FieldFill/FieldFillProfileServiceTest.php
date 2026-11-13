<?php

/**
 * Unit tests for FieldFillProfileService
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\FieldFill
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\FieldFill;

use OCA\Filinq\Exception\FieldFillRefused;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FieldFill\FieldFillProfileService;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Storing and finding field fill profiles.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\FieldFill
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FieldFillProfileServiceTest extends TestCase {

	/** @var array<int, array<string, mixed>> saveObject calls. */
	private array $saved = [];

	/** @var array<int, array<string, mixed>> searchObjectsBySlug calls. */
	private array $searches = [];

	/**
	 * The service over a schema with a name, an amount and a BSN.
	 *
	 * @param mixed $rows     What a search answers, or a Throwable it throws.
	 * @param bool  $noSchema Whether the schema cannot be read.
	 *
	 * @return FieldFillProfileService
	 */
	private function service(mixed $rows = [], bool $noSchema = false): FieldFillProfileService {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('saveObject')->willReturnCallback(function (array $object = [], string $register = '', string $schema = '', ?string $uuid = null): array {
			$this->saved[] = ['object' => $object, 'register' => $register, 'schema' => $schema, 'uuid' => $uuid];
			return ['@self' => ['id' => ($uuid ?? 'p-1')], 'object' => $object];
		});
		$objects->method('searchObjectsBySlug')->willReturnCallback(function (string $registerSlug, string $schemaSlug, array $filters = []) use ($rows): array {
			$this->searches[] = [$registerSlug, $schemaSlug, $filters];
			if ($rows instanceof \Throwable) {
				throw $rows;
			}

			return $rows;
		});
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		$schema = new class {
			/**
			 * The properties.
			 *
			 * @return array<string, mixed>
			 */
			public function getProperties(): array {
				return ['name' => ['type' => 'string'], 'amount' => ['type' => 'number'], 'citizen' => ['type' => 'string', 'format' => 'BSN'], 'junk' => 'x'];
			}
		};
		$mapper = new class($schema, $noSchema) {
			/**
			 * Constructor.
			 *
			 * @param object $schema The schema.
			 * @param bool   $fail   Whether find throws.
			 */
			public function __construct(private object $schema, private bool $fail) {
			}

			/**
			 * Find.
			 *
			 * @param mixed $id The id.
			 *
			 * @return object
			 */
			public function find(mixed $id): object {
				if ($this->fail === true) {
					throw new RuntimeException('gone');
				}

				return $this->schema;
			}
		};
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($mapper);

		return new FieldFillProfileService(objectResolver: $resolver, container: $container, logger: $this->createMock(LoggerInterface::class));

	}//end service()

	/**
	 * Assert save() is refused with a 400 and a message.
	 *
	 * @param FieldFillProfileService $service The service.
	 * @param array<string, mixed>    $profile The profile.
	 * @param string                  $message The message.
	 *
	 * @return void
	 */
	private function assertSaveRefused(FieldFillProfileService $service, array $profile, string $message): void {
		try {
			$service->save(profile: $profile);
			$this->fail('Expected a refusal');
		} catch (FieldFillRefused $refused) {
			$this->assertSame(400, $refused->getCode());
			$this->assertSame($message, $refused->getMessage());
		}

	}//end assertSaveRefused()

	/**
	 * A profile naming a BSN field is refused and not stored.
	 *
	 * @return void
	 */
	public function testABsnFieldIsRefused(): void {
		$this->assertSaveRefused(
			$this->service(),
			['register' => 'r', 'schema' => 's', 'fields' => ['name', 'citizen']],
			'A BSN field cannot be filled by a model.'
		);
		$this->assertSame([], $this->saved);

	}//end testABsnFieldIsRefused()

	/**
	 * An incomplete profile, an unknown field and an unreadable schema are refused.
	 *
	 * @return void
	 */
	public function testIncompleteOrUnknownProfilesAreRefused(): void {
		$this->assertSaveRefused($this->service(), ['register' => 'r', 'schema' => 's', 'fields' => [' ', 3]], 'A profile names a register, a schema and at least one field.');
		$this->assertSaveRefused($this->service(), ['register' => 'r', 'schema' => 's', 'fields' => ['nope']], 'The schema has no field "nope".');
		$this->assertSaveRefused($this->service(noSchema: true), ['register' => 'r', 'schema' => 's', 'fields' => ['name']], 'The schema could not be read.');
		$this->assertSame([], $this->saved);

	}//end testIncompleteOrUnknownProfilesAreRefused()

	/**
	 * A good profile is stored cleaned, as filinq's when no app is named, and replaced by uuid.
	 *
	 * @return void
	 */
	public function testAProfileIsStoredCleaned(): void {
		$stored = $this->service()->save(profile: ['declaringApp' => ' ', 'register' => ' r ', 'schema' => 's', 'fields' => [' name', 'amount', 'name'], 'active' => true], uuid: 'p-9');

		$this->assertSame(
			['object' => ['declaringApp' => 'filinq', 'register' => 'r', 'schema' => 's', 'fields' => ['name', 'amount'], 'active' => true], 'register' => 'filinq', 'schema' => 'fieldFillProfile', 'uuid' => 'p-9'],
			$this->saved[0]
		);
		$this->assertSame('p-9', $stored['uuid']);
		$this->assertSame(['name', 'amount'], $stored['fields']);

	}//end testAProfileIsStoredCleaned()

	/**
	 * Only an active profile with fields applies; a failing read means none.
	 *
	 * @return void
	 */
	public function testActiveForPicksTheActiveProfile(): void {
		$rows = [
			['object' => ['register' => 'r', 'schema' => 's', 'fields' => ['name'], 'active' => false]],
			['register' => 'r', 'schema' => 's', 'fields' => [], 'active' => true],
			['@self' => ['id' => 'p-3'], 'register' => 'r', 'schema' => 's', 'fields' => ['amount'], 'active' => true],
		];

		$this->assertSame('p-3', $this->service(rows: $rows)->activeFor(register: 'r', schema: 's')['uuid']);
		$this->assertSame([['filinq', 'fieldFillProfile', ['register' => 'r', 'schema' => 's']]], $this->searches);
		$this->assertNull($this->service(rows: $rows)->activeFor(register: '', schema: 's'));
		$this->assertNull($this->service(rows: new RuntimeException('down'))->activeFor(register: 'r', schema: 's'));
		$this->assertCount(3, $this->service(rows: $rows)->all());

	}//end testActiveForPicksTheActiveProfile()

	/**
	 * Properties keep only array definitions; the BSN check ignores case.
	 *
	 * @return void
	 */
	public function testPropertiesAndBsn(): void {
		$service = $this->service();

		$this->assertSame(['name', 'amount', 'citizen'], array_keys($service->properties(schema: 's')));
		$this->assertTrue($service->isBsn(property: ['format' => ' Bsn ']));
		$this->assertFalse($service->isBsn(property: ['format' => 'date']));
		$this->assertSame([], $service->fieldNames(profile: ['fields' => 'name']));

	}//end testPropertiesAndBsn()
}//end class
