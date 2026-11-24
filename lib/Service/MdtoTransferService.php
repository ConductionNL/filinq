<?php

/**
 * MDTO Transfer Service
 *
 * The overbrenging handoff: completeness gate, sidecar, and delivery through
 * OpenRegister's e-depot transfer stack.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-e-depot-handoff-rides-openregisters-transfer-stack-req-ddtmm-006
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Design D3 + D5. `transfer()` runs the completeness gate first and stops,
 * naming the missing fields, while it says incomplete. With no e-depot
 * transport configured in OpenRegister it does not pretend: it answers
 * `manual` and the caller offers the sidecar for download. Otherwise it
 * puts the record on an OpenRegister transfer list and runs OpenRegister's
 * EdepotTransferService with the transport OpenRegister is configured for;
 * OpenRegister builds the SIP, delivers it and, on confirmed ingest, sets
 * `archiefstatus = overgebracht` itself. Filinq then attaches the sidecar to
 * the record for the audit trail. Filinq holds no transport or HTTP code
 * and never writes `retention`.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-e-depot-handoff-rides-openregisters-transfer-stack-req-ddtmm-006
 */
class MdtoTransferService {

	/**
	 * OpenRegister's e-depot services.
	 */
	private const TRANSFER_SERVICE = 'OCA\OpenRegister\Service\Edepot\EdepotTransferService';

	private const TRANSFER_LIST_SERVICE = 'OCA\OpenRegister\Service\Edepot\TransferListService';

	private const FILE_SERVICE = 'OCA\OpenRegister\Service\FileService';

	/**
	 * OpenRegister's `edepot_transport` setting => its transport class and the
	 * transport-config key that must be set for it to be usable.
	 */
	private const TRANSPORTS = [
		'rest_api' => ['OCA\OpenRegister\Service\Edepot\Transport\RestApiTransport', 'endpointUrl'],
		'sftp' => ['OCA\OpenRegister\Service\Edepot\Transport\SftpTransport', 'host'],
		'openconnector' => ['OCA\OpenRegister\Service\Edepot\Transport\OpenConnectorTransport', 'sourceId'],
	];

	/**
	 * The transfer-list status OpenRegister sets on a confirmed ingest.
	 */
	private const CONFIRMED = 'completed';

	/**
	 * Constructor.
	 *
	 * @param MdtoRecordReader           $reader    Reads record, supplement, files and members.
	 * @param MdtoMappingService         $mapping   The completeness gate.
	 * @param MdtoSidecarBuilder         $builder   Builds the sidecar.
	 * @param OpenRegisterServiceLocator $locator   Resolves OpenRegister's e-depot services.
	 * @param IAppConfig                 $appConfig Reads OpenRegister's transport choice.
	 * @param LoggerInterface            $logger    Logs transfer outcomes.
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-e-depot-handoff-rides-openregisters-transfer-stack-req-ddtmm-006
	 */
	public function __construct(
		private readonly MdtoRecordReader $reader,
		private readonly MdtoMappingService $mapping,
		private readonly MdtoSidecarBuilder $builder,
		private readonly OpenRegisterServiceLocator $locator,
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Where a record stands before overbrenging: the gate verdict, whether a
	 * transport is configured, and the prefill proposals for the MDTO panel.
	 *
	 * @param string $uuid The record UUID.
	 *
	 * @return array<string, mixed> complete, missing, transportConfigured, supplement, proposals.
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-completeness-validation-gates-overbrenging-req-ddtmm-004
	 */
	public function status(string $uuid): array {
		$input   = $this->input(uuid: $uuid);
		$verdict = $this->verdict(input: $input);

		return [
			'complete' => $verdict['complete'],
			'missing' => $verdict['missing'],
			'transportConfigured' => $this->transport() !== null,
			'retention' => $input->retention(),
			'supplement' => $input->supplement,
			'proposals' => $this->mapping->prefill(
				recordType: self::recordType(input: $input),
				record: $input->record,
				members: $input->members,
				sources: $this->reader->restrictionSources(uuid: $uuid, files: $input->files)
			),
		];

	}//end status()

	/**
	 * The sidecar of a record, for download or manual delivery.
	 *
	 * @param string $uuid   The record UUID.
	 * @param string $format `xml` or `json`.
	 *
	 * @return array{fileName: string, mimeType: string, content: string}
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	public function export(string $uuid, string $format='xml'): array {
		$input = $this->input(uuid: $uuid);
		if ($format === 'json') {
			return [
				'fileName' => $uuid . '.mdto.json',
				'mimeType' => 'application/json',
				'content' => (string) json_encode(
					$this->builder->buildJson(input: $input),
					(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
				),
			];
		}

		return [
			'fileName' => $uuid . MdtoRecordReader::SIDECAR_SUFFIX,
			'mimeType' => 'application/xml',
			'content' => $this->builder->buildXml(input: $input),
		];

	}//end export()

	/**
	 * Hand a record to the e-depot through OpenRegister.
	 *
	 * @param string $uuid The record UUID.
	 *
	 * @return array<string, mixed> `status`: blocked (with `missing`), manual, transferred or failed (with `transferStatus`).
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-e-depot-handoff-rides-openregisters-transfer-stack-req-ddtmm-006
	 */
	public function transfer(string $uuid): array {
		$entity  = $this->reader->entity(uuid: $uuid);
		$input   = $this->inputFor(entity: $entity);
		$verdict = $this->verdict(input: $input);
		if ($verdict['complete'] === false) {
			return ['status' => 'blocked', 'missing' => $verdict['missing']];
		}

		$transport = $this->transport();
		if ($transport === null) {
			return ['status' => 'manual', 'reason' => 'no-transport'];
		}

		$sidecar = $this->builder->buildXml(input: $input);
		$list    = $this->locator->get(className: self::TRANSFER_LIST_SERVICE)->createTransferList(objects: [$entity]);
		$result  = (array) $this->locator->get(className: self::TRANSFER_SERVICE)->executeTransfer(transferList: (array) $list, transport: $transport);
		$status  = (string) ($result['status'] ?? '');
		if ($status !== self::CONFIRMED) {
			$this->logger->warning(
				message: '[MdtoTransferService] The e-depot did not confirm the transfer',
				context: ['file' => __FILE__, 'line' => __LINE__, 'record' => $uuid, 'transferStatus' => $status]
			);

			return ['status' => 'failed', 'transferStatus' => $status];
		}

		$this->attach(uuid: $uuid, sidecar: $sidecar);

		return ['status' => 'transferred', 'transferList' => (string) ($result['uuid'] ?? '')];

	}//end transfer()

	/**
	 * Attach the sidecar to the record; a failure is logged, the transfer stands.
	 *
	 * @param string $uuid    The record UUID.
	 * @param string $sidecar The sidecar XML.
	 *
	 * @return void
	 */
	private function attach(string $uuid, string $sidecar): void {
		try {
			$this->locator->get(className: self::FILE_SERVICE)->addFile(
				objectEntity: $uuid,
				fileName: $uuid . MdtoRecordReader::SIDECAR_SUFFIX,
				content: $sidecar
			);
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[MdtoTransferService] The sidecar could not be attached to the transferred record',
				context: ['file' => __FILE__, 'line' => __LINE__, 'record' => $uuid, 'error' => $e->getMessage()]
			);
		}

	}//end attach()

	/**
	 * The transport OpenRegister is configured for, or null when none is usable.
	 *
	 * @return object|null The TransportInterface.
	 */
	private function transport(): ?object {
		$type = $this->appConfig->getValueString('openregister', 'edepot_transport', 'rest_api');
		if (isset(self::TRANSPORTS[$type]) === false) {
			return null;
		}

		[$class, $required] = self::TRANSPORTS[$type];
		try {
			$config = (array) $this->locator->get(className: self::TRANSFER_SERVICE)->getTransportConfig();
			if (trim((string) ($config[$required] ?? '')) === '') {
				return null;
			}

			return $this->locator->get(className: $class);
		} catch (Throwable $e) {
			$this->logger->info(
				message: '[MdtoTransferService] No usable e-depot transport',
				context: ['file' => __FILE__, 'line' => __LINE__, 'transport' => $type, 'error' => $e->getMessage()]
			);

			return null;
		}

	}//end transport()

	/**
	 * The gate verdict for a sidecar input.
	 *
	 * @param MdtoSidecarInput $input The input.
	 *
	 * @return array{complete: bool, missing: array<int, string>}
	 */
	private function verdict(MdtoSidecarInput $input): array {
		return $this->mapping->validateCompleteness(
			record: $input->record,
			supplement: $input->supplement,
			organisationId: $input->organisationId,
			files: $input->files
		);

	}//end verdict()

	/**
	 * The sidecar input of a record by UUID.
	 *
	 * @param string $uuid The record UUID.
	 *
	 * @return MdtoSidecarInput
	 */
	private function input(string $uuid): MdtoSidecarInput {
		return $this->inputFor(entity: $this->reader->entity(uuid: $uuid));

	}//end input()

	/**
	 * The sidecar input of a record.
	 *
	 * @param object $entity The ObjectEntity.
	 *
	 * @return MdtoSidecarInput
	 *
	 * @throws RuntimeException When OpenRegister cannot be read.
	 */
	private function inputFor(object $entity): MdtoSidecarInput {
		$record     = $this->reader->record(entity: $entity);
		$supplement = $this->reader->supplement(uuid: (string) $record['uuid']);
		$members    = [];
		if (($supplement['objectType'] ?? '') === 'dossier') {
			$members = $this->reader->members(record: $record);
		}

		return new MdtoSidecarInput(
			record: $record,
			supplement: $supplement,
			organisationId: $this->reader->organisationId(),
			organisationName: $this->reader->organisationName(),
			files: $this->reader->files(entity: $entity),
			members: $members
		);

	}//end inputFor()

	/**
	 * `dossier` or `document`, from the supplement.
	 *
	 * @param MdtoSidecarInput $input The input.
	 *
	 * @return string
	 */
	private static function recordType(MdtoSidecarInput $input): string {
		if (($input->supplement['objectType'] ?? '') === 'dossier') {
			return 'dossier';
		}

		return 'document';

	}//end recordType()
}//end class
