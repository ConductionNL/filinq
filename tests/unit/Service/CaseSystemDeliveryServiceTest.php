<?php

/**
 * Unit tests for CaseSystemDeliveryService: which destination a document
 * goes to, the refusal of a second active destination, and one delivery per
 * document and destination.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-stored-document-gets-a-delivery-for-its-destination-req-gsc-002
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use InvalidArgumentException;
use OCA\Filinq\Service\CaseSystemDeliveryService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CaseSystemDeliveryService::class)]
class CaseSystemDeliveryServiceTest extends TestCase {

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
	 * @param array<string, array<int, array<string, mixed>>> $stored  Rows by schema.
	 * @param IUserManager|null                               $users   The user manager, if any.
	 * @param string|null                                     $failing A schema whose save throws.
	 *
	 * @return CaseSystemDeliveryService
	 */
	private function service(array $stored=[], ?IUserManager $users=null, ?string $failing=null): CaseSystemDeliveryService {
		$this->stored = $stored;
		$this->saved  = [];
		$objects = $this->getMockBuilder(ObjectService::class)->onlyMethods(['findAll', 'saveObject'])->getMock();
		$objects->method('findAll')->willReturnCallback(function (array $config=[], bool $_rbac=true): array {
			$filters = $config['filters'];
			$rows    = ($this->stored[$filters['schema']] ?? []);
			unset($filters['register'], $filters['schema']);

			return array_values(array_filter($rows, static function (array $row) use ($filters): bool {
				foreach ($filters as $key => $value) {
					if (($row[$key] ?? null) !== $value) {
						return false;
					}
				}

				return true;
			}));
		});
		$objects->method('saveObject')->willReturnCallback(function (array $object=[], ?\OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions $options = null) use ($failing): array {
			$register = (string) ($options?->target->register ?? '');
			$schema = (string) ($options?->target->schema ?? '');
			$uuid = $options?->uuid;
			if ($schema === $failing) {
				throw new Exception('object store unavailable');
			}

			$this->saved[] = ['schema' => $schema, 'object' => $object, 'uuid' => $uuid];
			return $object + ['id' => ($uuid ?? 'delivery-' . count($this->saved))];
		});

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		return new CaseSystemDeliveryService($resolver, $users);

	}//end service()

	/**
	 * A destination row.
	 *
	 * @param array<string, mixed> $extra The fields that differ.
	 *
	 * @return array<string, mixed>
	 */
	private static function destination(array $extra): array {
		return $extra + [
			'sourceId' => 'zgw-delft',
			'informatieobjecttype' => 'https://zaken.example.nl/catalogi/api/v1/informatieobjecttypen/arbeidsovereenkomst',
			'vertrouwelijkheidaanduiding' => 'vertrouwelijk',
			'bronorganisatie' => '002220647',
			'active' => true,
		];

	}//end destination()

	public function testATemplateDestinationWinsOverItsNamespace(): void {
		$service = $this->service([
			'caseSystemDestination' => [
				self::destination(['id' => 'ns', 'templateNamespace' => 'hrmq']),
				self::destination(['id' => 'tpl', 'templateId' => 'template-1', 'templateNamespace' => 'hrmq']),
			],
		]);

		$this->assertSame('tpl', $service->destinationFor(['id' => 'template-1', 'namespace' => 'hrmq'])['id']);
		$this->assertSame('ns', $service->destinationFor(['id' => 'template-2', 'namespace' => 'hrmq'])['id']);

	}//end testATemplateDestinationWinsOverItsNamespace()

	public function testARequestDestinationWinsAndAnInactiveOneIsIgnored(): void {
		$service = $this->service([
			'caseSystemDestination' => [self::destination(['id' => 'ns', 'templateNamespace' => 'hrmq', 'active' => false])],
		]);

		$this->assertNull($service->destinationFor(['id' => 'template-1', 'namespace' => 'hrmq']));
		$override = $service->destinationFor(['id' => 'template-1', 'namespace' => 'hrmq'], ['sourceId' => 'zgw-other', 'zaakUrl' => 'https://zaken.example.nl/zaken/api/v1/zaken/1']);
		$this->assertSame('zgw-other', $override['sourceId']);
		$this->assertSame('', $override['id']);

	}//end testARequestDestinationWinsAndAnInactiveOneIsIgnored()

	public function testASecondActiveDestinationForTheSameNamespaceIsRefused(): void {
		$service = $this->service([
			'caseSystemDestination' => [self::destination(['id' => 'ns', 'templateNamespace' => 'hrmq'])],
		]);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('An active destination for this template already exists.');
		$service->assertSingleActive(self::destination(['templateNamespace' => 'hrmq']));

	}//end testASecondActiveDestinationForTheSameNamespaceIsRefused()

	public function testADifferentScopeAnInactiveOneOrTheSameRowIsAllowed(): void {
		$service = $this->service([
			'caseSystemDestination' => [
				self::destination(['id' => 'ns', 'templateNamespace' => 'hrmq']),
				self::destination(['id' => 'tpl', 'templateId' => 'template-1']),
			],
		]);

		$service->assertSingleActive(self::destination(['templateNamespace' => 'finance']));
		$service->assertSingleActive(self::destination(['templateNamespace' => 'hrmq', 'active' => false]));
		$service->assertSingleActive(self::destination(['id' => 'ns', 'templateNamespace' => 'hrmq']));
		$service->assertSingleActive(self::destination(['templateId' => 'template-2', 'templateNamespace' => 'hrmq']));
		$this->addToAssertionCount(4);

		$this->expectExceptionMessage('An active destination for this template already exists.');
		$service->assertSingleActive(self::destination(['templateId' => 'template-1']));

	}//end testADifferentScopeAnInactiveOneOrTheSameRowIsAllowed()

	public function testAStoredDocumentGetsOneWaitingDeliveryWithItsMetadata(): void {
		$service  = $this->service();
		$delivery = $service->deliver(
			['id' => 'doc-1', 'fileId' => 4711, 'filePath' => '/HR/Arbeidsovereenkomst Jansen.pdf'],
			self::destination(['id' => 'ns', 'templateNamespace' => 'hrmq']),
			['author' => 'Anna de Vries', 'generatedBy' => 'anna', 'createdAt' => '2026-11-13', 'mimeType' => 'application/pdf']
		);

		$this->assertSame('delivery-1', $delivery['id']);
		$this->assertSame('caseSystemDelivery', $this->saved[0]['schema']);
		$this->assertSame(
			[
				'generatedDocumentId' => 'doc-1',
				'destinationId' => 'ns',
				'sourceId' => 'zgw-delft',
				'fileId' => 4711,
				'metadata' => [
					'titel' => 'Arbeidsovereenkomst Jansen',
					'bestandsnaam' => 'Arbeidsovereenkomst Jansen.pdf',
					'formaat' => 'application/pdf',
					'taal' => 'dut',
					'creatiedatum' => '2026-11-13',
					'auteur' => 'Anna de Vries',
					'informatieobjecttype' => 'https://zaken.example.nl/catalogi/api/v1/informatieobjecttypen/arbeidsovereenkomst',
					'vertrouwelijkheidaanduiding' => 'vertrouwelijk',
					'bronorganisatie' => '002220647',
				],
				'deliveryStatus' => 'ready_for_writeback',
				'generatedBy' => 'anna',
			],
			$this->saved[0]['object']
		);

	}//end testAStoredDocumentGetsOneWaitingDeliveryWithItsMetadata()

	public function testAskingAgainAnswersTheExistingDeliveryAndWritesNothing(): void {
		$existing = ['id' => 'delivery-9', 'generatedDocumentId' => 'doc-1', 'destinationId' => 'ns', 'deliveryStatus' => 'written_back'];
		$service  = $this->service(['caseSystemDelivery' => [$existing]]);

		$delivery = $service->deliver(['id' => 'doc-1', 'filePath' => '/x.pdf'], self::destination(['id' => 'ns']), []);

		$this->assertSame($existing, $delivery);
		$this->assertSame([], array_column($this->saved, 'object'));

	}//end testAskingAgainAnswersTheExistingDeliveryAndWritesNothing()

	public function testAnotherDestinationGetsItsOwnDeliveryAndACaseUrlIsCarried(): void {
		$service = $this->service(['caseSystemDelivery' => [['id' => 'delivery-9', 'generatedDocumentId' => 'doc-1', 'destinationId' => 'ns']]]);

		$service->deliver(['id' => 'doc-1', 'filePath' => '/x.pdf'], self::destination(['id' => '', 'zaakUrl' => 'https://zaken.example.nl/zaken/api/v1/zaken/1']), []);

		$this->assertCount(1, $this->saved);
		$this->assertSame('https://zaken.example.nl/zaken/api/v1/zaken/1', $this->saved[0]['object']['zaakUrl']);
		$this->assertSame('', $this->saved[0]['object']['destinationId']);

	}//end testAnotherDestinationGetsItsOwnDeliveryAndACaseUrlIsCarried()

	public function testACaseSystemRequestThatFilesNothingIsRefusedWith400(): void {
		CaseSystemDeliveryService::assertStoredMode(['output' => ['mode' => 'both', 'caseSystem' => ['sourceId' => 'x']]], 'both');
		CaseSystemDeliveryService::assertStoredMode(['output' => ['mode' => 'return']], 'return');
		CaseSystemDeliveryService::assertStoredMode(['output' => ['caseSystem' => []]], 'return');
		$this->addToAssertionCount(3);

		try {
			CaseSystemDeliveryService::assertStoredMode(['output' => ['caseSystem' => ['sourceId' => 'x']]], 'return');
			$this->fail('A case-system request in mode return was not refused.');
		} catch (Exception $e) {
			$this->assertSame(400, $e->getCode());
			$this->assertSame('A document sent to the case system must be stored first. Use output mode files or both.', $e->getMessage());
		}

	}//end testACaseSystemRequestThatFilesNothingIsRefusedWith400()

	public function testAStoredGenerationIsDeliveredAndLinkedFromItsDocument(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getDisplayName')->willReturn('Anna de Vries');
		$users = $this->createMock(IUserManager::class);
		$users->expects($this->once())->method('get')->with('anna')->willReturn($user);

		$service = $this->service(['caseSystemDestination' => [self::destination(['id' => 'ns', 'templateNamespace' => 'hrmq'])]], $users);
		$outcome = $service->recordGeneration(
			['id' => 'template-1', 'namespace' => 'hrmq'],
			['@self' => ['id' => 'doc-1'], 'templateId' => 'template-1', 'status' => 'generated'],
			['stored' => ['fileId' => 4711, 'path' => '/HR/Contract.pdf'], 'options' => ['userId' => 'anna'], 'format' => 'pdf']
		);

		$this->assertSame([], $outcome['warnings']);
		$this->assertSame('delivery-1', $outcome['delivery']['id']);
		$this->assertCount(2, $this->saved);
		$delivery = $this->saved[0]['object'];
		$this->assertSame(['doc-1', 4711, 'ready_for_writeback', 'anna'], [$delivery['generatedDocumentId'], $delivery['fileId'], $delivery['deliveryStatus'], $delivery['generatedBy']]);
		$this->assertSame(['Contract', 'Contract.pdf', 'application/pdf', 'Anna de Vries'], [
			$delivery['metadata']['titel'],
			$delivery['metadata']['bestandsnaam'],
			$delivery['metadata']['formaat'],
			$delivery['metadata']['auteur'],
		]);
		$this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $delivery['metadata']['creatiedatum']);

		$this->assertSame('generatedDocument', $this->saved[1]['schema']);
		$this->assertSame('doc-1', $this->saved[1]['uuid']);
		$this->assertSame(['templateId' => 'template-1', 'status' => 'generated', 'caseSystemDeliveryId' => 'delivery-1'], $this->saved[1]['object']);
		$this->assertSame('delivery-1', $outcome['document']['caseSystemDeliveryId']);

	}//end testAStoredGenerationIsDeliveredAndLinkedFromItsDocument()

	public function testNoDestinationMeansNoDeliveryAndNoWarning(): void {
		$service = $this->service(['caseSystemDestination' => [self::destination(['id' => 'ns', 'templateNamespace' => 'finance'])]]);
		$document = ['id' => 'doc-1'];

		$outcome = $service->recordGeneration(['id' => 'template-1', 'namespace' => 'hrmq'], $document, ['stored' => ['fileId' => 1], 'options' => []]);

		$this->assertSame(['document' => $document, 'delivery' => null, 'warnings' => []], $outcome);
		$this->assertSame([], $this->saved);

	}//end testNoDestinationMeansNoDeliveryAndNoWarning()

	public function testAFailedStoreOrAMissingRecordIsAWarningNotADelivery(): void {
		$service = $this->service(['caseSystemDestination' => [self::destination(['id' => 'ns', 'templateNamespace' => 'hrmq'])]]);
		$template = ['id' => 'template-1', 'namespace' => 'hrmq'];

		$unstored = $service->recordGeneration($template, ['id' => 'doc-1'], ['stored' => ['fileId' => null], 'options' => []]);
		$this->assertSame(['Not sent to the case system: the document was not stored.'], $unstored['warnings']);

		$unrecorded = $service->recordGeneration($template, [], ['stored' => ['fileId' => 7], 'options' => []]);
		$this->assertSame(['Not sent to the case system: the generated document could not be recorded.'], $unrecorded['warnings']);

		$this->assertNull($unrecorded['delivery']);
		$this->assertSame([], $this->saved);

	}//end testAFailedStoreOrAMissingRecordIsAWarningNotADelivery()

	public function testAFailingDeliveryWriteBecomesAWarningAndLeavesTheDocumentUnlinked(): void {
		$service  = $this->service(['caseSystemDestination' => [self::destination(['id' => 'ns', 'templateNamespace' => 'hrmq'])]], null, 'caseSystemDelivery');
		$document = ['id' => 'doc-1'];

		$outcome = $service->recordGeneration(
			['id' => 'template-1', 'namespace' => 'hrmq'],
			$document,
			['stored' => ['fileId' => 7, 'path' => '/a.odt'], 'options' => ['userId' => 'anna'], 'format' => 'odf']
		);

		$this->assertSame(['Not sent to the case system: object store unavailable'], $outcome['warnings']);
		$this->assertNull($outcome['delivery']);
		$this->assertSame($document, $outcome['document']);
		$this->assertSame([], $this->saved);

	}//end testAFailingDeliveryWriteBecomesAWarningAndLeavesTheDocumentUnlinked()

	public function testARequestDestinationIsUsedAndAnAlreadyLinkedDocumentIsNotRewritten(): void {
		$existing = ['id' => 'delivery-9', 'generatedDocumentId' => 'doc-1', 'destinationId' => ''];
		$service  = $this->service(['caseSystemDelivery' => [$existing]]);
		$document = ['id' => 'doc-1', 'caseSystemDeliveryId' => 'delivery-9'];

		$outcome = $service->recordGeneration(
			['id' => 'template-1', 'namespace' => 'hrmq'],
			$document,
			['stored' => ['fileId' => 7], 'options' => ['userId' => 'anna', 'output' => ['caseSystem' => ['sourceId' => 'zgw-other']]], 'format' => 'pdf']
		);

		$this->assertSame($existing, $outcome['delivery']);
		$this->assertSame($document, $outcome['document']);
		$this->assertSame([], $this->saved);

	}//end testARequestDestinationIsUsedAndAnAlreadyLinkedDocumentIsNotRewritten()
}//end class
