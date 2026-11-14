<?php

/**
 * Unit tests for CaseSystemDeliveryController: the retry of a failed
 * delivery, its admin gate, and the 404 and 409 answers.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\CaseSystemDeliveryController;
use OCA\Filinq\Service\CaseSystemDeliveryService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Settings\FilinqAdmin;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[CoversClass(CaseSystemDeliveryController::class)]
#[UsesClass(CaseSystemDeliveryService::class)]
class CaseSystemDeliveryControllerTest extends TestCase {

	/**
	 * What was saved, in order.
	 *
	 * @var array<int, array{object: array<string, mixed>, uuid: ?string, schema: string}>
	 */
	private array $saved = [];

	/**
	 * A controller over a store holding the given deliveries, for a caller.
	 *
	 * @param array<string, array<string, mixed>> $deliveries Deliveries by id.
	 * @param string|null                         $userId     The signed-in user, or null.
	 * @param bool                                $admin      Whether that user is an admin.
	 * @param array<int, array<string, mixed>>    $rows       Rows findAll answers, filtered by schema and status.
	 * @param array<string, mixed>                $params     The request parameters.
	 *
	 * @return CaseSystemDeliveryController
	 */
	private function controller(array $deliveries, ?string $userId='admin', bool $admin=true, array $rows=[], array $params=[]): CaseSystemDeliveryController {
		$this->saved = [];
		$objects     = $this->getMockBuilder(ObjectService::class)->onlyMethods(['find', 'findAll', 'saveObject'])->getMock();
		$objects->method('findAll')->willReturnCallback(static function (array $config=[]) use ($rows): array {
			$filters = $config['filters'];
			return array_values(array_filter($rows, static fn (array $row): bool => $row['_schema'] === $filters['schema'] && (isset($filters['deliveryStatus']) === false || $row['deliveryStatus'] === $filters['deliveryStatus'])));
		});
		$objects->method('find')->willReturnCallback(static fn (string $id='', string $register='', string $schema=''): ?array => ($deliveries[$id] ?? null));
		$objects->method('saveObject')->willReturnCallback(function (array $object=[], string $register='', string $schema='', ?string $uuid=null): array {
			$this->saved[] = ['object' => $object, 'uuid' => $uuid, 'schema' => $schema];
			return $object + ['id' => $uuid];
		});
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		$session = $this->createMock(IUserSession::class);
		$user    = null;
		if ($userId !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($userId);
		}

		$session->method('getUser')->willReturn($user);
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturn($admin);

		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn($params);

		return new CaseSystemDeliveryController(
			$request,
			new CaseSystemDeliveryService($resolver),
			$session,
			$groups
		);

	}//end controller()

	public function testEveryEndpointIsGatedByTheAdminSettingAttribute(): void {
		foreach (['index', 'saveDestination', 'retry'] as $method) {
			$attributes = (new ReflectionMethod(CaseSystemDeliveryController::class, $method))->getAttributes(AuthorizedAdminSetting::class);

			$this->assertCount(1, $attributes, $method);
			$this->assertSame([FilinqAdmin::class], $attributes[0]->getArguments(), $method);
		}

	}//end testEveryEndpointIsGatedByTheAdminSettingAttribute()

	public function testThePanelListsDestinationsAndOnlyWaitingAndFailedDeliveries(): void {
		$rows = [
			['_schema' => 'caseSystemDestination', 'id' => 'ns', 'templateNamespace' => 'hrmq', 'deliveryStatus' => null],
			['_schema' => 'caseSystemDelivery', 'id' => 'a', 'deliveryStatus' => 'writeback_failed'],
			['_schema' => 'caseSystemDelivery', 'id' => 'b', 'deliveryStatus' => 'written_back'],
			['_schema' => 'caseSystemDelivery', 'id' => 'c', 'deliveryStatus' => 'ready_for_writeback'],
		];

		$data = $this->controller([], 'admin', true, $rows)->index()->getData();

		$this->assertSame(['ns'], array_column($data['destinations'], 'id'));
		$this->assertSame(['c', 'a'], array_column($data['deliveries'], 'id'));

	}//end testThePanelListsDestinationsAndOnlyWaitingAndFailedDeliveries()

	public function testANewDestinationIsSavedWithOnlyItsOwnFields(): void {
		$params = ['templateNamespace' => ' hrmq ', 'sourceId' => 'zgw-delft', 'informatieobjecttype' => 'https://x/iot/1', 'active' => true, 'owner' => 'mallory', '_route' => 'x'];

		$response = $this->controller([], 'admin', true, [], $params)->saveDestination();

		$this->assertSame(200, $response->getStatus());
		$this->assertCount(1, $this->saved);
		$this->assertNull($this->saved[0]['uuid']);
		$this->assertSame('caseSystemDestination', $this->saved[0]['schema']);
		$this->assertSame(
			[
				'templateNamespace' => 'hrmq',
				'templateId' => '',
				'sourceId' => 'zgw-delft',
				'informatieobjecttype' => 'https://x/iot/1',
				'vertrouwelijkheidaanduiding' => '',
				'bronorganisatie' => '',
				'active' => true,
			],
			$this->saved[0]['object']
		);

	}//end testANewDestinationIsSavedWithOnlyItsOwnFields()

	public function testASecondActiveDestinationOrAnIncompleteOneAnswers400(): void {
		$rows = [['_schema' => 'caseSystemDestination', 'id' => 'ns', 'templateNamespace' => 'hrmq', 'active' => true, 'deliveryStatus' => null]];
		$cases = [
			'An active destination for this template already exists.' => ['templateNamespace' => 'hrmq', 'sourceId' => 's', 'active' => true],
			'A destination names a template or a template namespace.' => ['sourceId' => 's'],
			'A destination names a bridge source.' => ['templateId' => 'template-1'],
		];

		foreach ($cases as $message => $params) {
			$response = $this->controller([], 'admin', true, $rows, $params)->saveDestination();

			$this->assertSame(400, $response->getStatus(), $message);
			$this->assertSame(['error' => $message], $response->getData());
			$this->assertSame([], $this->saved);
		}

	}//end testASecondActiveDestinationOrAnIncompleteOneAnswers400()

	public function testUpdatingTheActiveDestinationItselfIsAllowed(): void {
		$rows = [['_schema' => 'caseSystemDestination', 'id' => 'ns', 'templateNamespace' => 'hrmq', 'active' => true, 'deliveryStatus' => null]];

		$response = $this->controller([], 'admin', true, $rows, ['id' => 'ns', 'templateNamespace' => 'hrmq', 'sourceId' => 's2', 'active' => true])->saveDestination();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame('ns', $this->saved[0]['uuid']);
		$this->assertSame('s2', $this->saved[0]['object']['sourceId']);

	}//end testUpdatingTheActiveDestinationItselfIsAllowed()

	public function testAFailedDeliveryIsWaitingAgainOnTheSameObject(): void {
		$failed = [
			'id' => 'delivery-1',
			'generatedDocumentId' => 'doc-1',
			'destinationId' => 'ns',
			'deliveryStatus' => 'writeback_failed',
			'errorMessage' => 'informatieobjecttype unknown',
			'@self' => ['id' => 'delivery-1'],
		];

		$response = $this->controller(['delivery-1' => $failed])->retry('delivery-1');

		$this->assertSame(200, $response->getStatus());
		$this->assertSame('ready_for_writeback', $response->getData()['deliveryStatus']);
		$this->assertCount(1, $this->saved);
		$this->assertSame('delivery-1', $this->saved[0]['uuid']);
		$this->assertSame('caseSystemDelivery', $this->saved[0]['schema']);
		$this->assertSame(
			['generatedDocumentId' => 'doc-1', 'destinationId' => 'ns', 'deliveryStatus' => 'ready_for_writeback'],
			$this->saved[0]['object']
		);

	}//end testAFailedDeliveryIsWaitingAgainOnTheSameObject()

	public function testADeliveryInAnyOtherStateAnswers409AndIsNotWritten(): void {
		foreach (['ready_for_writeback', 'written_back'] as $status) {
			$response = $this->controller(['d' => ['id' => 'd', 'deliveryStatus' => $status]])->retry('d');

			$this->assertSame(409, $response->getStatus(), $status);
			$this->assertSame(['error' => 'Only a failed delivery can be retried.'], $response->getData());
			$this->assertSame([], $this->saved);
		}

	}//end testADeliveryInAnyOtherStateAnswers409AndIsNotWritten()

	public function testAnUnknownDeliveryAnswers404(): void {
		$response = $this->controller([])->retry('missing');

		$this->assertSame(404, $response->getStatus());
		$this->assertSame(['error' => 'Delivery not found.'], $response->getData());

	}//end testAnUnknownDeliveryAnswers404()

	public function testAnonymousAndNonAdminCallersAreRefusedBeforeAnyRead(): void {
		$failed = ['d' => ['id' => 'd', 'deliveryStatus' => 'writeback_failed']];

		$this->assertSame(401, $this->controller($failed, null)->retry('d')->getStatus());
		$this->assertSame(403, $this->controller($failed, 'clerk', false)->retry('d')->getStatus());
		$this->assertSame(403, $this->controller($failed, 'clerk', false)->index()->getStatus());
		$this->assertSame(403, $this->controller($failed, 'clerk', false, [], ['templateId' => 't', 'sourceId' => 's'])->saveDestination()->getStatus());
		$this->assertSame(401, $this->controller($failed, null)->index()->getStatus());
		$this->assertSame([], $this->saved);

	}//end testAnonymousAndNonAdminCallersAreRefusedBeforeAnyRead()
}//end class
