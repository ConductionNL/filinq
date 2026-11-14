<?php

/**
 * Filinq repair step: re-point stored Woo grounds to dossiq's codes
 *
 * Every stored reference to one of filinq's 19 old Woo `base` slugs (a
 * dossier's `bases`, a publication consent or prohibition rule's `bases`, and
 * the entity-type mapping in `filinq.grondslagen.entity_type_bases`) is
 * rewritten to the dossiq refusal ground code the legacy map gives it. A slug
 * the map marks `unmapped` is never guessed: the reference stays and the
 * report names the object and the slug. Nothing is deleted, and a second run
 * changes nothing.
 *
 * @category Repair
 * @package  OCA\Filinq\Repair
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
 */

declare(strict_types=1);

namespace OCA\Filinq\Repair;

use OCA\Filinq\Service\DossierObjectReader;
use OCA\Filinq\Service\DossierObjectRepository;
use OCA\Filinq\Service\Grounds\WooGroundsLegacyMap;
use OCA\Filinq\Service\LegalBasisProposalService;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Rewrites one-to-one Woo slugs to codes and reports what it left.
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
 */
class RepointWooGrounds implements IRepairStep {

	/**
	 * The register the objects live in.
	 */
	private const REGISTER = 'filinq';

	/**
	 * The schemas whose objects carry a `bases` list of grounds.
	 *
	 * @var list<string>
	 */
	public const SCHEMAS = ['dossier', 'publicationConsent', 'publicationProhibition'];

	/**
	 * Constructor.
	 *
	 * @param DossierObjectRepository $repository OpenRegister access.
	 * @param DossierObjectReader     $reader     Object-shape reading.
	 * @param WooGroundsLegacyMap     $legacy     The old slug to code map.
	 * @param IAppConfig              $appConfig  Holds the entity-type mapping.
	 * @param LoggerInterface         $logger     Names an object that could not be written.
	 */
	public function __construct(
		private readonly DossierObjectRepository $repository,
		private readonly DossierObjectReader $reader,
		private readonly WooGroundsLegacyMap $legacy,
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * The step's name in the upgrade log.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	public function getName(): string {
		return 'Re-point stored Woo grounds to the dossiq refusal ground codes';
	}//end getName()

	/**
	 * Re-point every stored reference, then report what was rewritten and what was left.
	 *
	 * @param IOutput $output The upgrade output.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	public function run(IOutput $output): void {
		try {
			$this->legacy->all();
		} catch (Throwable $e) {
			$output->warning('RepointWooGrounds: the legacy map cannot be read, nothing was changed: ' . $e->getMessage());
			return;
		}

		$report = ['rewritten' => 0, 'left' => []];
		$objectService = $this->repository->objectService();
		if ($objectService === null) {
			$output->info('RepointWooGrounds: OpenRegister is not available, no objects were re-pointed.');
		}

		foreach (self::SCHEMAS as $schema) {
			if ($objectService !== null) {
				$report = $this->repointSchema(objectService: $objectService, schema: $schema, report: $report);
			}
		}

		$report = $this->repointMapping(report: $report);

		$output->info('RepointWooGrounds: re-pointed ' . $report['rewritten'] . ' references, left ' . count($report['left']) . '.');
		foreach ($report['left'] as $left) {
			$output->warning('RepointWooGrounds: ' . $left . ' has no one-to-one dossiq code and was left as it is.');
		}
	}//end run()

	/**
	 * A list of grounds with every one-to-one old slug replaced by its code.
	 *
	 * @param array<mixed> $bases The stored grounds.
	 *
	 * @return array{bases: list<mixed>, left: list<string>} The grounds, and the old slugs left as they were.
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	public function repoint(array $bases): array {
		$out = ['bases' => [], 'left' => []];
		foreach ($bases as $base) {
			if (is_string($base) === false || $this->legacy->isLegacySlug(slug: $base) === false) {
				$out['bases'][] = $base;
				continue;
			}

			$code = $this->legacy->codeFor(slug: $base);
			$out['bases'][] = ($code ?? $base);
			if ($code === null) {
				$out['left'][] = $base;
			}
		}

		return $out;
	}//end repoint()

	/**
	 * Re-point the `bases` of every object of one schema.
	 *
	 * @param object                                         $objectService OpenRegister's object service.
	 * @param string                                         $schema        The schema slug.
	 * @param array{rewritten: int, left: list<string>}      $report        The report so far.
	 *
	 * @return array{rewritten: int, left: list<string>} The report.
	 */
	private function repointSchema(object $objectService, string $schema, array $report): array {
		try {
			$objects = $this->reader->findAllOf(objectService: $objectService, schema: $schema);
		} catch (Throwable $e) {
			$this->logger->info('Filinq: no ' . $schema . ' objects to re-point', ['reason' => $e->getMessage()]);
			return $report;
		}

		foreach ($objects as $object) {
			$payload = $object;
			if (is_object($object) === true) {
				$payload = $this->reader->payloadOf(object: $object);
			}

			if (is_array($payload) === false || is_array($payload['bases'] ?? null) === false) {
				continue;
			}

			$uuid = (string)($payload['@self']['uuid'] ?? $payload['@self']['id'] ?? $payload['id'] ?? '');
			$result = $this->repoint(bases: $payload['bases']);
			foreach ($result['left'] as $slug) {
				$report['left'][] = $schema . ' ' . $uuid . ' (' . $slug . ')';
			}

			if ($result['bases'] !== $payload['bases']
				&& $this->save(objectService: $objectService, schema: $schema, payload: ['bases' => $result['bases']] + $payload) === true
			) {
				$report['rewritten']++;
			}
		}

		return $report;
	}//end repointSchema()

	/**
	 * Write one re-pointed object back.
	 *
	 * @param object               $objectService OpenRegister's object service.
	 * @param string               $schema        The schema slug.
	 * @param array<string, mixed> $payload       The object with its re-pointed `bases`.
	 *
	 * @return bool True when the object was written.
	 */
	private function save(object $objectService, string $schema, array $payload): bool {
		$uuid = (string)($payload['@self']['uuid'] ?? $payload['@self']['id'] ?? $payload['id'] ?? '');
		if ($uuid === '') {
			return false;
		}

		unset($payload['@self']);
		try {
			$objectService->saveObject(
				object: $payload,
				register: self::REGISTER,
				schema: $schema,
				uuid: $uuid,
				_rbac: false,
				_multitenancy: false
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				'Filinq: a stored Woo ground could not be re-pointed',
				['uuid' => $uuid, 'schema' => $schema, 'reason' => $e->getMessage()]
			);
			return false;
		}

		return true;
	}//end save()

	/**
	 * Re-point the entity-type mapping in app config.
	 *
	 * @param array{rewritten: int, left: list<string>} $report The report so far.
	 *
	 * @return array{rewritten: int, left: list<string>} The report.
	 */
	private function repointMapping(array $report): array {
		$raw = $this->appConfig->getValueString('filinq', LegalBasisProposalService::CONFIG_KEY, '');
		$mapping = json_decode($raw, true);
		if (is_array($mapping) === false) {
			return $report;
		}

		$changed = false;
		foreach ($mapping as $type => $bases) {
			if (is_array($bases) === false) {
				continue;
			}

			$result = $this->repoint(bases: $bases);
			foreach ($result['left'] as $slug) {
				$report['left'][] = LegalBasisProposalService::CONFIG_KEY . ' ' . $type . ' (' . $slug . ')';
			}

			if ($result['bases'] !== $bases) {
				$mapping[$type] = $result['bases'];
				$changed = true;
				$report['rewritten']++;
			}
		}

		if ($changed === true) {
			$this->appConfig->setValueString('filinq', LegalBasisProposalService::CONFIG_KEY, (string)json_encode($mapping));
		}

		return $report;
	}//end repointMapping()
}//end class
