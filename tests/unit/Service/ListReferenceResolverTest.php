<?php

/**
 * Unit tests for ListReferenceResolver
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-generation-list-refs/tasks.md#task-2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use Exception;
use OCA\DocuDesk\Service\ListReferenceResolver;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Unit tests for ListReferenceResolver
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class ListReferenceResolverTest extends TestCase {

	/**
	 * The service under test
	 *
	 * @var ListReferenceResolver
	 */
	private ListReferenceResolver $service;

	/**
	 * Mock object service
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService $objectService;

	/**
	 * Set up test fixtures
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$container = $this->createMock(ContainerInterface::class);
		$appManager = $this->createMock(IAppManager::class);
		$this->objectService = $this->createMock(ObjectService::class);

		$appManager->method('getInstalledApps')
			->willReturn(['openregister']);

		$container->method('get')
			->with('OCA\OpenRegister\Service\ObjectService')
			->willReturn($this->objectService);

		$this->service = new ListReferenceResolver($container, $appManager);

	}//end setUp()

	/**
	 * Build a mock ObjectEntity that serializes to the given array.
	 *
	 * @param array $data The data the entity should serialize to
	 *
	 * @return ObjectEntity&MockObject
	 */
	private function makeEntity(array $data): ObjectEntity {
		$entity = $this->createMock(ObjectEntity::class);
		$entity->method('jsonSerialize')->willReturn($data);
		return $entity;
	}//end makeEntity()

	/**
	 * Test that a listRef resolves to an array under its default 'as' key
	 * (schema + '_list'), with the schema slug's hyphens sanitised to
	 * underscores so the key is a legal Twig identifier — the DBAL example
	 * from the proposal ('v-app-competitors') is exactly this case.
	 *
	 * @return void
	 */
	public function testResolveListRefDefaultAsKey(): void {
		$entities = [
			$this->makeEntity(['id' => '1', 'name' => 'Alpha']),
			$this->makeEntity(['id' => '2', 'name' => 'Beta']),
		];

		$this->objectService->expects($this->once())
			->method('setRegister')
			->with($this->equalTo('spectr-live'));
		$this->objectService->expects($this->once())
			->method('setSchema')
			->with($this->equalTo('v-app-competitors'));
		$this->objectService->expects($this->once())
			->method('searchObjectsPaginated')
			->with($this->callback(function (array $query): bool {
				return ($query['app_id'] ?? null) === 6
					&& ($query['_limit'] ?? null) === 50;
			}))
			->willReturn(['results' => $entities, 'total' => 2]);

		$result = $this->service->resolve(
			listRefs: [
				[
					'register' => 'spectr-live',
					'schema' => 'v-app-competitors',
					'filter' => ['app_id' => 6],
				],
			]
		);

		$this->assertEmpty($result['errors']);
		$this->assertArrayHasKey('v_app_competitors_list', $result['data']);
		$this->assertCount(2, $result['data']['v_app_competitors_list']);
		$this->assertEquals('Alpha', $result['data']['v_app_competitors_list'][0]['name']);

	}//end testResolveListRefDefaultAsKey()

	/**
	 * Test that an explicit 'as' key is honoured and the requested 'limit'
	 * is forwarded as '_limit'.
	 *
	 * @return void
	 */
	public function testResolveListRefExplicitAsKeyAndLimit(): void {
		$this->objectService->expects($this->once())
			->method('searchObjectsPaginated')
			->with($this->callback(function (array $query): bool {
				return ($query['_limit'] ?? null) === 5;
			}))
			->willReturn(['results' => [$this->makeEntity(['id' => '1'])], 'total' => 1]);

		$result = $this->service->resolve(
			listRefs: [
				[
					'register' => 'spectr-live',
					'schema' => 'v-app-competitors',
					'filter' => ['app_id' => 6],
					'limit' => 5,
					'as' => 'competitors',
				],
			]
		);

		$this->assertArrayHasKey('competitors', $result['data']);
		$this->assertCount(1, $result['data']['competitors']);

	}//end testResolveListRefExplicitAsKeyAndLimit()

	/**
	 * Test that 'order' is forwarded as '_order'.
	 *
	 * @return void
	 */
	public function testResolveListRefOrderForwarded(): void {
		$this->objectService->expects($this->once())
			->method('searchObjectsPaginated')
			->with($this->callback(function (array $query): bool {
				return ($query['_order'] ?? null) === ['name' => 'ASC'];
			}))
			->willReturn(['results' => [], 'total' => 0]);

		$result = $this->service->resolve(
			listRefs: [
				[
					'register' => 'spectr-live',
					'schema' => 'v-app-competitors',
					'order' => ['name' => 'ASC'],
				],
			]
		);

		$this->assertEmpty($result['errors']);

	}//end testResolveListRefOrderForwarded()

	/**
	 * Test that a default limit of 50 is applied when 'limit' is omitted.
	 *
	 * @return void
	 */
	public function testDefaultLimitAppliedWhenOmitted(): void {
		$this->objectService->expects($this->once())
			->method('searchObjectsPaginated')
			->with($this->callback(function (array $query): bool {
				return ($query['_limit'] ?? null) === 50;
			}))
			->willReturn(['results' => [], 'total' => 0]);

		$this->service->resolve(
			listRefs: [
				['register' => 'spectr-live', 'schema' => 'v-app-competitors'],
			]
		);

	}//end testDefaultLimitAppliedWhenOmitted()

	/**
	 * Test that more than MAX_LIST_REFS (10) listRefs is rejected with a 400.
	 *
	 * @return void
	 */
	public function testTooManyListRefsRejected(): void {
		$listRefs = [];
		for ($i = 0; $i < 11; $i++) {
			$listRefs[] = ['register' => 'r', 'schema' => 's' . $i];
		}

		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->resolve(listRefs: $listRefs);

	}//end testTooManyListRefsRejected()

	/**
	 * Test that a non-scalar filter value is rejected with a 400.
	 *
	 * @return void
	 */
	public function testNonScalarFilterValueRejected(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->resolve(
			listRefs: [
				[
					'register' => 'spectr-live',
					'schema' => 'v-app-competitors',
					'filter' => ['nested' => ['not' => 'scalar']],
				],
			]
		);

	}//end testNonScalarFilterValueRejected()

	/**
	 * Test that a limit above MAX_LIST_LIMIT (500) is rejected with a 400.
	 *
	 * @return void
	 */
	public function testLimitAboveHardCapRejected(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->resolve(
			listRefs: [
				['register' => 'spectr-live', 'schema' => 'v-app-competitors', 'limit' => 501],
			]
		);

	}//end testLimitAboveHardCapRejected()

	/**
	 * Test that a zero/negative limit is rejected with a 400.
	 *
	 * @return void
	 */
	public function testLimitBelowOneRejected(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->resolve(
			listRefs: [
				['register' => 'spectr-live', 'schema' => 'v-app-competitors', 'limit' => 0],
			]
		);

	}//end testLimitBelowOneRejected()

	/**
	 * Test that an 'as' key not matching the allowed pattern is rejected.
	 *
	 * @return void
	 */
	public function testInvalidAsKeyPatternRejected(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->resolve(
			listRefs: [
				[
					'register' => 'spectr-live',
					'schema' => 'v-app-competitors',
					'as' => '1-invalid key!',
				],
			]
		);

	}//end testInvalidAsKeyPatternRejected()

	/**
	 * Test that an 'as' key colliding with an existing dataRef key is
	 * rejected with a 400.
	 *
	 * @return void
	 */
	public function testAsKeyCollisionWithReservedKeyRejected(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->resolve(
			listRefs: [
				[
					'register' => 'spectr-live',
					'schema' => 'v-app-competitors',
					'as' => 'persoon',
				],
			],
			reservedKeys: ['persoon']
		);

	}//end testAsKeyCollisionWithReservedKeyRejected()

	/**
	 * Test that two listRefs resolving to the same default 'as' key collide.
	 *
	 * @return void
	 */
	public function testAsKeyCollisionBetweenTwoListRefsRejected(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->resolve(
			listRefs: [
				['register' => 'spectr-live', 'schema' => 'v-app-competitors'],
				['register' => 'spectr-live', 'schema' => 'v-app-competitors'],
			]
		);

	}//end testAsKeyCollisionBetweenTwoListRefsRejected()

	/**
	 * Test that a listRef missing 'register' or 'schema' is rejected.
	 *
	 * @return void
	 */
	public function testMissingRequiredFieldRejected(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->resolve(
			listRefs: [
				['register' => '', 'schema' => 'v-app-competitors'],
			]
		);

	}//end testMissingRequiredFieldRejected()

	/**
	 * Test that a guardrail violation on one listRef aborts the whole
	 * request — no OpenRegister search runs for any listRef.
	 *
	 * @return void
	 */
	public function testGuardrailViolationAbortsBeforeAnySearch(): void {
		$this->objectService->expects($this->never())
			->method('searchObjectsPaginated');

		try {
			$this->service->resolve(
				listRefs: [
					['register' => 'spectr-live', 'schema' => 'v-app-competitors'],
					['register' => 'spectr-live', 'schema' => ''],
				]
			);
			$this->fail('Expected an Exception to be thrown.');
		} catch (Exception $e) {
			$this->assertEquals(400, $e->getCode());
		}

	}//end testGuardrailViolationAbortsBeforeAnySearch()

	/**
	 * Test that an OpenRegister search failure for one listRef is collected
	 * as a soft error and does not abort other listRefs (mirrors dataRefs).
	 *
	 * @return void
	 */
	public function testSearchFailureIsSoftError(): void {
		$this->objectService->method('setSchema')
			->willReturnCallback(function (string $schema) {
				if ($schema === 'broken-schema') {
					throw new Exception('schema slug not found in caller organisation');
				}

				return null;
			});

		$this->objectService->method('searchObjectsPaginated')
			->willReturn(['results' => [$this->makeEntity(['id' => '1'])], 'total' => 1]);

		$result = $this->service->resolve(
			listRefs: [
				['register' => 'spectr-live', 'schema' => 'v-app-competitors'],
				['register' => 'spectr-live', 'schema' => 'broken-schema'],
			]
		);

		$this->assertArrayHasKey('v_app_competitors_list', $result['data']);
		$this->assertCount(1, $result['data']['v_app_competitors_list']);

		$this->assertArrayHasKey('broken_schema_list', $result['data']);
		$this->assertEmpty($result['data']['broken_schema_list']);

		$this->assertCount(1, $result['errors']);
		$this->assertEquals('broken_schema_list', $result['errors'][0]['as']);

	}//end testSearchFailureIsSoftError()

	/**
	 * Test that an empty listRefs array resolves to empty data/errors
	 * without touching OpenRegister.
	 *
	 * @return void
	 */
	public function testEmptyListRefsIsNoOp(): void {
		$this->objectService->expects($this->never())
			->method('searchObjectsPaginated');

		$result = $this->service->resolve(listRefs: []);

		$this->assertEmpty($result['data']);
		$this->assertEmpty($result['errors']);

	}//end testEmptyListRefsIsNoOp()
}//end class
