<?php

/**
 * The bridge panel's side of the case-system delivery: destinations listed and saved,
 * open deliveries listed, and a failed delivery retried.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://conduction.nl
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use InvalidArgumentException;
use OCA\Filinq\Service\CaseSystemDeliveryService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The bridge panel's calls on CaseSystemDeliveryService.
 *
 * @spec openspec/changes/archive/2026-11-25-generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
 */
#[CoversClass(CaseSystemDeliveryService::class)]
final class CaseSystemDeliveryAdminTest extends TestCase {

	/**
	 * Stored rows by schema.
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private array $stored = [];

	/**
	 * What was saved, in order.
	 *
	 * @var array<int, array{schema: string, object: array<string, mixed>, uuid: ?string}>
	 */
	private array $saved = [];

	/**
	 * The service over an object store holding the given rows.
	 *
	 * @param array<string, array<int, array<string, mixed>>> $stored Rows by schema.
	 *
	 * @return CaseSystemDeliveryService
	 */
	private function service(array $stored=[]): CaseSystemDeliveryService {
		$this->stored = $stored;
		$this->saved  = [];
		$objects      = $this->getMockBuilder(ObjectService::class)->onlyMethods(['findAll', 'find', 'saveObject'])->getMock();
		$objects->method('findAll')->willReturnCallback(
			function (array $config=[]): array {
				$filters = $config['filters'];
				$rows    = ($this->stored[$filters['schema']] ?? []);
				unset($filters['register'], $filters['schema']);

				return array_values(
					array_filter(
						$rows,
						static function (array $row) use ($filters): bool {
							foreach ($filters as $key => $value) {
								if (($row[$key] ?? null) !== $value) {
									return false;
								}
							}

							return true;
						}
					)
				);
			}
		);
		$objects->method('find')->willReturnCallback(
			function (string|int $id): ?array {
				foreach (($this->stored[CaseSystemDeliveryService::DELIVERY_SCHEMA] ?? []) as $row) {
					if (($row['id'] ?? null) === $id) {
						return $row;
					}
				}

				return null;
			}
		);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object=[], ObjectSaveOptions $options=new ObjectSaveOptions()): array {
				$this->saved[] = ['schema' => (string) $options->target->schema, 'object' => $object, 'uuid' => $options->uuid];
				return $object + ['id' => ($options->uuid ?? 'saved-' . count($this->saved))];
			}
		);

		$resolver = $this->createMock(originalClassName: DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		return new CaseSystemDeliveryService($resolver, null);

	}//end service()

	/**
	 * The panel lists every destination, and the waiting deliveries before the failed ones.
	 *
	 * @return void
	 */
	public function testThePanelListsDestinationsAndOpenDeliveries(): void {
		$service = $this->service(
			stored: [
				CaseSystemDeliveryService::DESTINATION_SCHEMA => [['id' => 'd1', 'templateNamespace' => 'hrmq']],
				CaseSystemDeliveryService::DELIVERY_SCHEMA    => [
					['id' => 'x1', 'deliveryStatus' => CaseSystemDeliveryService::FAILED],
					['id' => 'x2', 'deliveryStatus' => 'delivered'],
					['id' => 'x3', 'deliveryStatus' => CaseSystemDeliveryService::READY],
				],
			]
		);

		$this->assertSame(expected: ['d1'], actual: array_column($service->listDestinations(), 'id'));
		$this->assertSame(expected: ['x3', 'x1'], actual: array_column($service->openDeliveries(), 'id'));

	}//end testThePanelListsDestinationsAndOpenDeliveries()

	/**
	 * A destination is saved trimmed and inactive unless asked; an update keeps its id.
	 *
	 * @return void
	 */
	public function testADestinationIsSavedTrimmedAndAnUpdateKeepsItsId(): void {
		$service = $this->service();

		$created = $service->saveDestination(input: ['templateNamespace' => ' hrmq ', 'sourceId' => ' zgw-delft ', 'bronorganisatie' => '002220647']);
		$updated = $service->saveDestination(input: ['id' => 'd1', 'templateId' => 'template-1', 'sourceId' => 'zgw-delft', 'active' => true]);

		$this->assertSame(expected: 'hrmq', actual: $created['templateNamespace']);
		$this->assertSame(expected: 'zgw-delft', actual: $created['sourceId']);
		$this->assertFalse(condition: $created['active']);
		$this->assertNull(actual: $this->saved[0]['uuid']);
		$this->assertSame(expected: 'd1', actual: $this->saved[1]['uuid']);
		$this->assertTrue(condition: $updated['active']);
		$this->assertSame(
			expected: [CaseSystemDeliveryService::DESTINATION_SCHEMA, CaseSystemDeliveryService::DESTINATION_SCHEMA],
			actual: array_column($this->saved, 'schema')
		);

	}//end testADestinationIsSavedTrimmedAndAnUpdateKeepsItsId()

	/**
	 * A destination without a template or namespace, or without a source, is refused and nothing is saved.
	 *
	 * @return void
	 */
	public function testADestinationWithoutScopeOrSourceIsRefused(): void {
		$service = $this->service();

		foreach ([['sourceId' => 'zgw-delft'], ['templateNamespace' => 'hrmq']] as $input) {
			try {
				$service->saveDestination(input: $input);
				$this->fail(message: 'An incomplete destination must be refused.');
			} catch (InvalidArgumentException $e) {
				$this->assertNotSame(expected: '', actual: $e->getMessage());
			}
		}

		$this->assertSame(expected: [], actual: $this->saved);

	}//end testADestinationWithoutScopeOrSourceIsRefused()

	/**
	 * A failed delivery is put back in the queue on the same object, without its error.
	 *
	 * @return void
	 */
	public function testAFailedDeliveryIsRetriedOnTheSameObject(): void {
		$service = $this->service(
			stored: [
				CaseSystemDeliveryService::DELIVERY_SCHEMA => [
					['id' => 'x1', 'deliveryStatus' => CaseSystemDeliveryService::FAILED, 'errorMessage' => 'Open Zaak answered 500', 'documentId' => 'doc-1'],
				],
			]
		);

		$retried = $service->retry(id: 'x1');

		$this->assertSame(expected: CaseSystemDeliveryService::READY, actual: $retried['deliveryStatus']);
		$this->assertSame(expected: 'x1', actual: $this->saved[0]['uuid']);
		$this->assertArrayNotHasKey(key: 'errorMessage', array: $this->saved[0]['object']);
		$this->assertSame(expected: 'doc-1', actual: $this->saved[0]['object']['documentId']);

	}//end testAFailedDeliveryIsRetriedOnTheSameObject()

	/**
	 * An unknown delivery answers 404 and one that did not fail answers 409; nothing is saved.
	 *
	 * @return void
	 */
	public function testOnlyAKnownFailedDeliveryCanBeRetried(): void {
		$service = $this->service(
			stored: [
				CaseSystemDeliveryService::DELIVERY_SCHEMA => [['id' => 'x3', 'deliveryStatus' => CaseSystemDeliveryService::READY]],
			]
		);

		$codes = [];
		foreach (['', 'missing', 'x3'] as $id) {
			try {
				$service->retry(id: $id);
			} catch (RuntimeException $e) {
				$codes[] = $e->getCode();
			}
		}

		$this->assertSame(expected: [404, 404, 409], actual: $codes);
		$this->assertSame(expected: [], actual: $this->saved);

	}//end testOnlyAKnownFailedDeliveryCanBeRetried()
}//end class
