<?php

/**
 * Unit tests for MdtoTransferService and MdtoRecordReader against a fake
 * OpenRegister: the gate in front of overbrenging, the degradation to manual
 * delivery, the transfer through OpenRegister's e-depot stack with a fake
 * transport, and the sidecar attachment.
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
 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-e-depot-handoff-rides-openregisters-transfer-stack-req-ddtmm-006
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\MdtoMappingService;
use OCA\Filinq\Service\MdtoRecordReader;
use OCA\Filinq\Service\MdtoRestrictionProposer;
use OCA\Filinq\Service\MdtoRestrictionSources;
use OCA\Filinq\Service\MdtoSidecarBuilder;
use OCA\Filinq\Service\MdtoSidecarInput;
use OCA\Filinq\Service\MdtoXmlWriter;
use OCA\Filinq\Service\MdtoTransferService;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCP\Files\File;
use OCP\IAppConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass(MdtoTransferService::class)]
#[CoversClass(MdtoRecordReader::class)]
#[UsesClass(MdtoMappingService::class)]
#[UsesClass(MdtoRestrictionProposer::class)]
#[UsesClass(MdtoRestrictionSources::class)]
#[UsesClass(MdtoSidecarBuilder::class)]
#[UsesClass(MdtoSidecarInput::class)]
#[UsesClass(MdtoXmlWriter::class)]
class MdtoTransferServiceTest extends TestCase {

	private const UUID = '6f1c2d3e-4b5a-4c6d-8e7f-0a1b2c3d4e5f';

	/**
	 * The fake OpenRegister: objects by uuid, rows by schema, files, and what was called.
	 *
	 * @var object
	 */
	private object $openRegister;

	/**
	 * OpenRegister's app settings.
	 *
	 * @var array<string, string>
	 */
	private array $settings = [];

	/**
	 * The logger.
	 *
	 * @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject
	 */
	private LoggerInterface $logger;

	protected function setUp(): void {
		$this->settings     = ['organisation_identifier' => '00000001002220647000', 'organisation_name' => 'Gemeente Demostad', 'edepot_transport' => 'rest_api'];
		$this->openRegister = new class {
			/** @var array<string, object> */
			public array $objects = [];
			/** @var array<string, array<int, array<string, mixed>>> */
			public array $rows = [];
			/** @var array<int, mixed> */
			public array $files = [];
			/** @var array<string, mixed> */
			public array $transportConfig = ['endpointUrl' => 'https://edepot.example.nl/api'];
			public string $outcome = 'completed';
			public bool $attachFails = false;
			/** @var array<int, array<string, mixed>> */
			public array $calls = [];

			public function find(string $id = ''): ?object {
				return ($this->objects[$id] ?? null);
			}

			public function searchObjects(array $query = []): array {
				$schema = (string) $query['@self']['schema'];
				unset($query['@self']);
				return array_values(
					array_filter(
						($this->rows[$schema] ?? []),
						static fn (array $row): bool => array_intersect_assoc(array_map('strval', $query), array_map('strval', array_intersect_key($row, $query))) === array_map('strval', $query)
					)
				);
			}

			public function getFiles(object|string $object): array {
				return $this->files;
			}

			public function addFile(string $objectEntity, string $fileName, string $content): object {
				if ($this->attachFails === true) {
					throw new RuntimeException('disk full');
				}
				$this->calls[] = ['addFile' => [$objectEntity, $fileName, $content]];
				return new \stdClass();
			}

			public function createTransferList(array $objects): array {
				$this->calls[] = ['createTransferList' => $objects];
				return ['uuid' => 'tl-1', 'status' => 'in_review', 'objectReferences' => [['uuid' => $objects[0]->getUuid()]]];
			}

			public function executeTransfer(array $transferList, object $transport): array {
				$this->calls[] = ['executeTransfer' => [$transferList, $transport]];
				return array_merge($transferList, ['status' => $this->outcome]);
			}

			public function getTransportConfig(): array {
				return $this->transportConfig;
			}
		};
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->openRegister->objects[self::UUID] = self::entity(
			uuid: self::UUID,
			data: ['title' => 'Besluit subsidietoekenning cultuur'],
			retention: ['archiefnominatie' => 'bewaren', 'classificatie' => '1.1', 'bewaartermijn' => 'P20Y']
		);
		$this->openRegister->rows['mdtoSupplement'] = [
			['objectRef' => self::UUID, 'objectType' => 'document', 'aggregatieniveau' => 'archiefstuk', 'dekkingInTijdBegin' => '2024-11-03', 'proposals' => []],
		];
		$this->openRegister->files = [self::file(id: 7, name: 'besluit.pdf', content: 'pdf-bytes'), self::file(id: 8, name: self::UUID . '.mdto.xml', content: '<old/>')];

	}//end setUp()

	/**
	 * An ObjectEntity stand-in.
	 *
	 * @param string               $uuid      The UUID.
	 * @param array<string, mixed> $data      The object data.
	 * @param array<string, mixed> $retention The retention block.
	 * @param string               $schema    The schema id the object is in.
	 *
	 * @return object
	 */
	private static function entity(string $uuid, array $data, array $retention=[], string $schema=''): object {
		return new class($uuid, $data, $retention, $schema) {
			public function __construct(private string $uuid, private array $data, private array $retention, private string $schema) {
			}

			public function getSchema(): string {
				return $this->schema;
			}

			public function getSlug(): string {
				return (string) ($this->data['slug'] ?? '');
			}

			public function getUuid(): string {
				return $this->uuid;
			}

			public function getObject(): array {
				return $this->data;
			}

			public function getRetention(): array {
				return $this->retention;
			}
		};

	}//end entity()

	/**
	 * A Nextcloud file.
	 *
	 * @param int    $id      The file id.
	 * @param string $name    The name.
	 * @param string $content The bytes.
	 *
	 * @return File
	 */
	private function file(int $id, string $name, string $content): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);
		$file->method('getName')->willReturn($name);
		$file->method('getMimetype')->willReturn('application/pdf');
		$file->method('getSize')->willReturn(strlen($content));
		$file->method('getContent')->willReturn($content);
		$file->method('getMTime')->willReturn(1731578400);

		return $file;

	}//end file()

	/**
	 * The service wired to the fake OpenRegister.
	 *
	 * @return MdtoTransferService
	 */
	private function service(): MdtoTransferService {
		$fake    = $this->openRegister;
		$locator = $this->createMock(OpenRegisterServiceLocator::class);
		$locator->method('get')->willReturnCallback(
			static fn (string $className): object => match (str_contains($className, 'Transport\\')) {
				true => (object) ['transport' => $className],
				false => $fake,
			}
		);
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default=''): string => ($this->settings[$key] ?? $default)
		);

		return new MdtoTransferService(
			reader: new MdtoRecordReader(locator: $locator, appConfig: $config),
			mapping: new MdtoMappingService(),
			builder: new MdtoSidecarBuilder(),
			locator: $locator,
			appConfig: $config,
			logger: $this->logger
		);

	}//end service()

	/**
	 * The names of the fake OpenRegister calls, in order.
	 *
	 * @return array<int, string>
	 */
	private function callNames(): array {
		return array_map(static fn (array $call): string => (string) array_key_first($call), $this->openRegister->calls);

	}//end callNames()

	public function testAnIncompleteRecordIsBlockedAndNothingIsSent(): void {
		$this->openRegister->rows['mdtoSupplement'][0]['proposals'] = ['aggregatieniveau'];

		$this->assertSame(['status' => 'blocked', 'missing' => ['aggregatieniveau']], $this->service()->transfer(self::UUID));
		$this->assertSame([], $this->openRegister->calls);

	}//end testAnIncompleteRecordIsBlockedAndNothingIsSent()

	public function testWithoutAConfiguredTransportTheActionDegradesToManualDelivery(): void {
		$this->openRegister->transportConfig = ['endpointUrl' => ''];

		$this->assertSame(['status' => 'manual', 'reason' => 'no-transport'], $this->service()->transfer(self::UUID));
		$this->assertSame([], $this->openRegister->calls);
		$this->assertFalse($this->service()->status(self::UUID)['transportConfigured']);

	}//end testWithoutAConfiguredTransportTheActionDegradesToManualDelivery()

	public function testAnUnknownTransportSettingCountsAsNoTransport(): void {
		$this->settings['edepot_transport'] = 'carrier-pigeon';

		$this->assertSame('manual', $this->service()->transfer(self::UUID)['status']);

	}//end testAnUnknownTransportSettingCountsAsNoTransport()

	public function testAConfirmedTransferRunsThroughOpenRegisterAndAttachesTheSidecar(): void {
		$result = $this->service()->transfer(self::UUID);

		$this->assertSame(['status' => 'transferred', 'transferList' => 'tl-1'], $result);
		$this->assertSame(['createTransferList', 'executeTransfer', 'addFile'], $this->callNames());
		$this->assertSame(self::UUID, $this->openRegister->calls[0]['createTransferList'][0]->getUuid());
		$this->assertSame(['transport' => 'OCA\OpenRegister\Service\Edepot\Transport\RestApiTransport'], (array) $this->openRegister->calls[1]['executeTransfer'][1]);

		[$owner, $name, $content] = $this->openRegister->calls[2]['addFile'];
		$this->assertSame(self::UUID, $owner);
		$this->assertSame(self::UUID . '.mdto.xml', $name);
		$this->assertStringContainsString('<mdto:checksumWaarde>' . hash('sha256', 'pdf-bytes') . '</mdto:checksumWaarde>', $content);
		$this->assertStringNotContainsString('.mdto.xml</mdto:naam>', $content, 'an earlier sidecar is no representation');

	}//end testAConfirmedTransferRunsThroughOpenRegisterAndAttachesTheSidecar()

	public function testTheConfiguredSftpTransportIsTheOneUsed(): void {
		$this->settings['edepot_transport']  = 'sftp';
		$this->openRegister->transportConfig = ['host' => 'sftp.edepot.example.nl'];

		$this->service()->transfer(self::UUID);

		$this->assertSame('OCA\OpenRegister\Service\Edepot\Transport\SftpTransport', ((array) $this->openRegister->calls[1]['executeTransfer'][1])['transport']);

	}//end testTheConfiguredSftpTransportIsTheOneUsed()

	public function testAnUnconfirmedTransferIsReportedAndNothingIsAttached(): void {
		$this->openRegister->outcome = 'failed';
		$this->logger->expects($this->once())->method('warning');

		$this->assertSame(['status' => 'failed', 'transferStatus' => 'failed'], $this->service()->transfer(self::UUID));
		$this->assertNotContains('addFile', $this->callNames());

	}//end testAnUnconfirmedTransferIsReportedAndNothingIsAttached()

	public function testAFailedAttachmentIsLoggedAndTheTransferStands(): void {
		$this->openRegister->attachFails = true;
		$this->logger->expects($this->once())->method('error');

		$this->assertSame('transferred', $this->service()->transfer(self::UUID)['status']);

	}//end testAFailedAttachmentIsLoggedAndTheTransferStands()

	public function testAnUnreadableRecordIsANotFound(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionCode(404);

		$this->service()->transfer('00000000-0000-0000-0000-00000000dead');

	}//end testAnUnreadableRecordIsANotFound()

	public function testTheExportIsTheSidecarAsXmlOrJson(): void {
		$xml  = $this->service()->export(self::UUID);
		$json = $this->service()->export(self::UUID, 'json');

		$this->assertSame([self::UUID . '.mdto.xml', 'application/xml'], [$xml['fileName'], $xml['mimeType']]);
		$this->assertStringStartsWith('<?xml', $xml['content']);
		$this->assertSame($xml['content'], $this->service()->export(self::UUID)['content'], 'deterministic');
		$this->assertSame('application/json', $json['mimeType']);
		$this->assertSame(self::UUID, json_decode($json['content'], true)['MDTO']['informatieobject']['identificatie']['identificatieKenmerk']);

	}//end testTheExportIsTheSidecarAsXmlOrJson()

	public function testTheStatusCarriesTheVerdictAndTheRestrictionProposals(): void {
		$this->openRegister->rows['publicationConsent'] = [
			['documentId' => '7', 'consentStatus' => 'objection_received', 'legalBasis' => 'AVG art. 6 lid 1 sub e', 'policyMatch' => 'p-1'],
		];
		$this->openRegister->objects['p-1'] = self::entity(uuid: 'p-1', data: ['matchRules' => [], 'active' => true, 'legalAuthority' => 'Rechtbank Den Haag']);
		$this->openRegister->rows['publicationConsent'][] = ['documentId' => self::UUID, 'consentStatus' => 'consent_given', 'policyMatch' => 'standing-1'];
		$this->openRegister->objects['standing-1'] = self::entity(uuid: 'standing-1', data: ['scope' => 'entity', 'matchRules' => []]);
		$this->openRegister->rows['anonymizationLink']      = [['sourceFileId' => 7, 'anonymizedFileName' => 'besluit-anoniem.pdf']];

		$status = $this->service()->status(self::UUID);

		$this->assertTrue($status['complete']);
		$this->assertTrue($status['transportConfigured']);
		$this->assertSame('bewaren', $status['retention']['archiefnominatie']);
		$this->assertSame(['consent', 'prohibition', 'anonymisation'], array_column($status['proposals']['beperkingGebruik']['value'], 'source'));
		$this->assertSame(['value' => 'archiefstuk', 'proposed' => true], $status['proposals']['aggregatieniveau']);

	}//end testTheStatusCarriesTheVerdictAndTheRestrictionProposals()

	public function testADossierSidecarAggregatesTheMembersItLists(): void {
		$dossier = 'd0551e00-0000-4000-8000-000000000001';
		$this->openRegister->objects[$dossier] = self::entity(
			uuid: $dossier,
			data: ['name' => 'Woo-verzoek 2024-017', 'documents' => ['a0000000-0000-4000-8000-000000000002', ['uuid' => 'a0000000-0000-4000-8000-000000000001'], '']],
			retention: ['archiefnominatie' => 'vernietigen', 'classificatie' => '6', 'bewaartermijn' => 'P5Y']
		);
		$this->openRegister->objects['a0000000-0000-4000-8000-000000000001'] = self::entity(uuid: 'a0000000-0000-4000-8000-000000000001', data: ['title' => 'Verzoek']);
		$this->openRegister->objects['a0000000-0000-4000-8000-000000000002'] = self::entity(uuid: 'a0000000-0000-4000-8000-000000000002', data: ['title' => 'Besluit']);
		$this->openRegister->rows['mdtoSupplement'][] = ['objectRef' => $dossier, 'objectType' => 'dossier', 'aggregatieniveau' => 'dossier'];
		$this->openRegister->files = [];

		$io = json_decode($this->service()->export($dossier, 'json')['content'], true)['MDTO']['informatieobject'];

		$this->assertSame('Woo-verzoek 2024-017', $io['naam']);
		$this->assertSame(['Verzoek', 'Besluit'], array_column($io['bevatOnderdeel'], 'verwijzingNaam'), 'members in uuid order');

	}//end testADossierSidecarAggregatesTheMembersItLists()
	/**
	 * A dossier with no supplement yet is read as a dossier from its schema:
	 * the panel proposes aggregation level `dossier` and the members are loaded.
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-filinq-context-prefills-the-supplement-as-confirmable-proposals-req-ddtmm-003
	 */
	public function testADossierWithoutASupplementIsProposedAsADossier(): void {
		$dossier = 'd0551e00-0000-4000-8000-000000000009';
		$this->openRegister->objects['43'] = self::entity(uuid: 'schema-43', data: ['slug' => 'dossier']);
		$this->openRegister->objects[$dossier] = self::entity(
			uuid: $dossier,
			data: ['name' => 'Woo-verzoek 2025-017', 'documents' => ['a0000000-0000-4000-8000-000000000001']],
			schema: '43'
		);
		$this->openRegister->objects['a0000000-0000-4000-8000-000000000001'] = self::entity(uuid: 'a0000000-0000-4000-8000-000000000001', data: ['title' => 'Verzoek']);
		$this->openRegister->files = [];

		$status = $this->service()->status($dossier);

		$this->assertSame(['value' => 'dossier', 'proposed' => true], $status['proposals']['aggregatieniveau']);

	}//end testADossierWithoutASupplementIsProposedAsADossier()

	/**
	 * A record in another schema, or whose schema cannot be resolved, stays a
	 * document when its supplement names no type.
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-filinq-context-prefills-the-supplement-as-confirmable-proposals-req-ddtmm-003
	 */
	public function testARecordInAnotherSchemaWithoutASupplementIsADocument(): void {
		$other   = 'b0000000-0000-4000-8000-000000000001';
		$unknown = 'b0000000-0000-4000-8000-000000000002';
		$this->openRegister->objects['12'] = self::entity(uuid: 'schema-12', data: ['slug' => 'document']);
		$this->openRegister->objects[$other]   = self::entity(uuid: $other, data: ['title' => 'Brief'], schema: '12');
		$this->openRegister->objects[$unknown] = self::entity(uuid: $unknown, data: ['title' => 'Notitie'], schema: '999');

		$this->assertSame('archiefstuk', $this->service()->status($other)['proposals']['aggregatieniveau']['value']);
		$this->assertSame('archiefstuk', $this->service()->status($unknown)['proposals']['aggregatieniveau']['value']);

	}//end testARecordInAnotherSchemaWithoutASupplementIsADocument()

	/**
	 * The supplement's own `objectType` wins over the schema.
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-filinq-context-prefills-the-supplement-as-confirmable-proposals-req-ddtmm-003
	 */
	public function testTheSupplementTypeWinsOverTheSchema(): void {
		$this->openRegister->objects['43'] = self::entity(uuid: 'schema-43', data: ['slug' => 'dossier']);
		$this->openRegister->objects[self::UUID] = self::entity(
			uuid: self::UUID,
			data: ['title' => 'Besluit subsidietoekenning cultuur'],
			retention: ['archiefnominatie' => 'bewaren', 'classificatie' => '1.1', 'bewaartermijn' => 'P20Y'],
			schema: '43'
		);

		$this->assertSame('archiefstuk', $this->service()->status(self::UUID)['proposals']['aggregatieniveau']['value']);

	}//end testTheSupplementTypeWinsOverTheSchema()
}//end class
