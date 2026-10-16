<?php

/**
 * The schedule fields a periodic run writes validate against the shipped
 * periodicDocument schema (periodic-documents-on-a-schedule D3).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use DateTimeImmutable;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentService;
use OCA\Filinq\Service\PageLayoutService;
use OCA\Filinq\Service\PeriodicDocumentService;
use OCA\Filinq\Service\SavedViewReader;
use OCA\OpenRegister\Service\ObjectService;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * `periodicDocument` is hardValidation: a field the schema does not declare
 * makes OpenRegister refuse the whole write, and the error would be lost with it.
 */
class PeriodicDocumentScheduleSchemaTest extends TestCase {

	/**
	 * The shipped periodicDocument schema.
	 *
	 * @return object
	 */
	private function schema(): object {
		$descriptor = json_decode((string)file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json'));
		$this->assertIsObject($descriptor);
		return $descriptor->components->schemas->periodicDocument;
	}

	/**
	 * Run the real service once (good or failing) and capture the schedule it writes.
	 *
	 * @param array<int, mixed>|null $records What the view returns.
	 *
	 * @return array<string, mixed> The written schedule fields.
	 */
	private function writtenSchedule(?array $records): array {
		$written = [];
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willReturn([[
			'uuid' => 'schedule-1', 'name' => 'Besluitenlijst', 'viewSlug' => 'besluiten', 'templateId' => 't-1',
			'cadence' => 'weekly', 'active' => true, '@self' => ['id' => 'schedule-1', 'owner' => 'griffier'],
		]]);
		$objectService->method('saveObject')->willReturnCallback(
			static function (...$arguments) use (&$written): array {
				$written = $arguments[0];
				return $written;
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);
		$views = $this->createMock(SavedViewReader::class);
		$views->method('records')->willReturn($records);
		$layouts = $this->createMock(PageLayoutService::class);
		$layouts->method('stamp')->willReturnCallback(static fn (array $document): array => $document);
		$documents = $this->createMock(DocumentService::class);
		$documents->method('generateDocument')->willReturn(['metadata' => ['uuid' => 'doc-1'], 'output' => ['fileId' => 5]]);

		(new PeriodicDocumentService($resolver, $views, $layouts, new NullLogger(), $documents))
			->runDue(now: new DateTimeImmutable('2026-09-28T10:00:00+00:00'));

		return $written;
	}

	/**
	 * Assert every written field is declared and validates.
	 *
	 * @param array<string, mixed> $fields The written fields.
	 *
	 * @return void
	 */
	private function assertValidates(array $fields): void {
		$properties = $this->schema()->properties;
		$validator = new Validator();
		$this->assertNotSame([], $fields);
		foreach ($fields as $name => $value) {
			$this->assertObjectHasProperty($name, $properties, 'The run writes "' . $name . '", which the schema does not declare.');
			$property = clone $properties->{$name};
			unset($property->required);
			$this->assertTrue(
				$validator->validate(json_decode(json_encode($value)), json_encode($property))->isValid(),
				'The written "' . $name . '" does not validate.'
			);
		}
	}

	public function testAGoodRunWritesOnlyDeclaredValidFields(): void {
		$fields = $this->writtenSchedule(records: [['id' => 'a']]);

		$this->assertSame('doc-1', $fields['lastRunDocument']);
		$this->assertValidates($fields);
	}

	public function testAFailedRunWritesOnlyDeclaredValidFields(): void {
		$fields = $this->writtenSchedule(records: null);

		$this->assertStringContainsString('no longer exists', $fields['lastRunError']);
		$this->assertValidates($fields);
	}

	public function testTheVersionsMoveSoTheFieldsReachExistingInstalls(): void {
		$descriptor = json_decode((string)file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json'));
		$this->assertTrue(version_compare((string)$descriptor->info->version, '8.21.0', '>='));
		$this->assertTrue(version_compare((string)$this->schema()->version, '1.1.0', '>='));
	}
}
