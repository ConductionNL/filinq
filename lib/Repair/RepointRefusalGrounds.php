<?php

/**
 * Filinq repair step: re-point stored Woo grounds to dossiq's codes
 *
 * Every stored reference to one of filinq's 19 old Woo `base` slugs (a
 * dossier's `bases`, a publication consent or prohibition rule's `bases`, and
 * the entity-type mapping in `filinq.grondslagen.entity_type_bases`, and the
 * `bases` of every recorded entity relation) is
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

use OCA\Filinq\Kernel\Contracts\DossierObjectReaderInterface;
use OCA\Filinq\Kernel\Contracts\LegalBasisProposalServiceInterface;
use OCA\Filinq\Kernel\Contracts\RefusalGroundsLegacyMapInterface;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Rewrites one-to-one Woo slugs to codes and reports what it left.
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
 */
class RepointRefusalGrounds implements IRepairStep {

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
	 * @param DossierObjectReaderInterface     $reader     Object-shape reading.
	 * @param RefusalGroundsLegacyMapInterface $legacy     The old slug to code map.
	 * @param IAppConfig              $appConfig  Holds the entity-type mapping.
	 * @param LoggerInterface         $logger     Names an object that could not be written.
	 * @param ContainerInterface|null $container  Reaches OpenRegister's object service and entity relations.
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	public function __construct(
		private readonly DossierObjectReaderInterface $reader,
		private readonly RefusalGroundsLegacyMapInterface $legacy,
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
		private readonly ?ContainerInterface $container = null,
	) {
	}//end __construct()

	/**
	 * OpenRegister's object service, or null when OpenRegister is not there.
	 *
	 * @return object|null
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	private function objectService(): ?object {
		try {
			return $this->container?->get('OCA\\OpenRegister\\Service\\ObjectService');
		} catch (Throwable $e) {
			return null;
		}
	}//end objectService()

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
			$output->warning('RepointRefusalGrounds: the legacy map cannot be read, nothing was changed: ' . $e->getMessage());
			return;
		}

		$report = $this->repointObjects(report: ['rewritten' => 0, 'left' => []], output: $output);
		$report = $this->repointMapping(report: $report);
		$report = $this->repointRelations(report: $report, output: $output);

		$output->info('RepointRefusalGrounds: re-pointed ' . $report['rewritten'] . ' references, left ' . count($report['left']) . '.');
		foreach ($report['left'] as $left) {
			$output->warning('RepointRefusalGrounds: ' . $left . ' has no one-to-one dossiq code and was left as it is.');
		}
	}//end run()

	/**
	 * Re-point the `bases` of the objects of every schema that carries them.
	 *
	 * @param array{rewritten: int, left: list<string>} $report The report so far.
	 * @param IOutput                                   $output The upgrade output.
	 *
	 * @return array{rewritten: int, left: list<string>} The report.
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	private function repointObjects(array $report, IOutput $output): array {
		$objectService = $this->objectService();
		if ($objectService === null) {
			$output->info('RepointRefusalGrounds: OpenRegister is not available, no objects were re-pointed.');
			return $report;
		}

		foreach (self::SCHEMAS as $schema) {
			$report = $this->repointSchema(objectService: $objectService, schema: $schema, report: $report);
		}

		return $report;
	}//end repointObjects()

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
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	private function repointSchema(object $objectService, string $schema, array $report): array {
		try {
			$objects = $this->reader->findAllOf(objectService: $objectService, schema: $schema);
		} catch (Throwable $e) {
			$this->logger->info('Filinq: no ' . $schema . ' objects to re-point', ['reason' => $e->getMessage()]);
			return $report;
		}

		foreach ($objects as $object) {
			$report = $this->repointObject(objectService: $objectService, schema: $schema, object: $object, report: $report);
		}

		return $report;
	}//end repointSchema()

	/**
	 * Re-point the `bases` of one object and save it when they changed.
	 *
	 * @param object                                    $objectService OpenRegister's object service.
	 * @param string                                    $schema        The schema slug.
	 * @param mixed                                     $object        The stored object or its array.
	 * @param array{rewritten: int, left: list<string>} $report        The report so far.
	 *
	 * @return array{rewritten: int, left: list<string>} The report.
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	private function repointObject(object $objectService, string $schema, mixed $object, array $report): array {
		$payload = $this->payloadWithBases(object: $object);
		if ($payload === null) {
			return $report;
		}

		$uuid   = (string)($payload['@self']['uuid'] ?? $payload['@self']['id'] ?? $payload['id'] ?? '');
		$result = $this->repoint(bases: $payload['bases']);
		foreach ($result['left'] as $slug) {
			$report['left'][] = $schema . ' ' . $uuid . ' (' . $slug . ')';
		}

		if ($result['bases'] !== $payload['bases']
			&& $this->save(objectService: $objectService, schema: $schema, payload: ['bases' => $result['bases']] + $payload) === true
		) {
			$report['rewritten']++;
		}

		return $report;
	}//end repointObject()

	/**
	 * The payload of a stored object when it carries a `bases` list, else null.
	 *
	 * @param mixed $object The stored object or its array.
	 *
	 * @return array<string, mixed>|null
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	private function payloadWithBases(mixed $object): ?array {
		$payload = $object;
		if (is_object($object) === true) {
			$payload = $this->reader->payloadOf(object: $object);
		}

		if (is_array($payload) === false || is_array($payload['bases'] ?? null) === false) {
			return null;
		}

		return $payload;
	}//end payloadWithBases()

	/**
	 * Write one re-pointed object back.
	 *
	 * @param object               $objectService OpenRegister's object service.
	 * @param string               $schema        The schema slug.
	 * @param array<string, mixed> $payload       The object with its re-pointed `bases`.
	 *
	 * @return bool True when the object was written.
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
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
				options: new ObjectSaveOptions(
					target: new ObjectFindTarget(register: self::REGISTER, schema: $schema),
					uuid: $uuid
				),
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
	 * The page size of the entity relation walk.
	 */
	private const RELATION_PAGE = 500;

	/**
	 * Re-point the `bases` of every entity relation that carries them.
	 *
	 * Reads OpenRegister's relations page by page in id order and writes a
	 * changed list through its audited decision-metadata path. An OpenRegister
	 * without the paged read leaves the relations as they are and says so;
	 * their old slugs keep their labels through the legacy map.
	 *
	 * @param array{rewritten: int, left: list<string>} $report The report so far.
	 * @param IOutput                                   $output The upgrade output.
	 *
	 * @return array{rewritten: int, left: list<string>} The report.
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	private function repointRelations(array $report, IOutput $output): array {
		$mapper = $this->relationMapper();
		if ($mapper === null) {
			$output->info('RepointRefusalGrounds: this OpenRegister cannot list entity relations with grounds; their grounds were left as they are.');
			return $report;
		}

		$afterId = 0;
		do {
			$relations = $mapper->findWithBases(afterId: $afterId, limit: self::RELATION_PAGE);
			$lastId = $afterId;
			foreach ($relations as $relation) {
				$lastId = max($lastId, (int)$relation->getId());
				$report = $this->repointRelation(mapper: $mapper, relation: $relation, report: $report);
			}

			$fullPage = (count($relations) === self::RELATION_PAGE);
			$advanced = ($lastId > $afterId);
			$afterId = $lastId;
		} while ($fullPage === true && $advanced === true);

		return $report;
	}//end repointRelations()

	/**
	 * OpenRegister's EntityRelationMapper when it can list relations with grounds, else null.
	 *
	 * @return object|null
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	private function relationMapper(): ?object {
		try {
			$mapper = $this->container?->get('OCA\\OpenRegister\\Db\\EntityRelationMapper');
		} catch (Throwable $e) {
			return null;
		}

		if (is_object($mapper) === false || method_exists($mapper, 'findWithBases') === false) {
			return null;
		}

		return $mapper;
	}//end relationMapper()

	/**
	 * Re-point one entity relation's `bases`.
	 *
	 * @param object                                    $mapper   OpenRegister's EntityRelationMapper.
	 * @param object                                    $relation The relation.
	 * @param array{rewritten: int, left: list<string>} $report   The report so far.
	 *
	 * @return array{rewritten: int, left: list<string>} The report.
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	private function repointRelation(object $mapper, object $relation, array $report): array {
		$bases = $relation->getBases();
		if (is_array($bases) === false) {
			return $report;
		}

		$result = $this->repoint(bases: $bases);
		foreach ($result['left'] as $slug) {
			$report['left'][] = 'entity relation ' . (string)$relation->getId() . ' (' . $slug . ')';
		}

		if ($result['bases'] === $bases) {
			return $report;
		}

		try {
			$mapper->updateDecisionMetadata(relation: $relation, fields: ['bases' => $result['bases']]);
			$report['rewritten']++;
		} catch (Throwable $e) {
			$this->logger->warning(
				'Filinq: the grounds of an entity relation could not be re-pointed',
				['relationId' => $relation->getId(), 'reason' => $e->getMessage()]
			);
		}

		return $report;
	}//end repointRelation()

	/**
	 * Re-point the entity-type mapping in app config.
	 *
	 * @param array{rewritten: int, left: list<string>} $report The report so far.
	 *
	 * @return array{rewritten: int, left: list<string>} The report.
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	private function repointMapping(array $report): array {
		$raw = $this->appConfig->getValueString('filinq', LegalBasisProposalServiceInterface::CONFIG_KEY, '');
		$mapping = json_decode($raw, true);
		if (is_array($mapping) === false) {
			return $report;
		}

		$before = $mapping;
		foreach ($mapping as $type => $bases) {
			[$mapping[$type], $report] = $this->repointMappingType(type: (string)$type, bases: $bases, report: $report);
		}

		if ($mapping !== $before) {
			$this->appConfig->setValueString('filinq', LegalBasisProposalServiceInterface::CONFIG_KEY, (string)json_encode($mapping));
		}

		return $report;
	}//end repointMapping()

	/**
	 * Re-point the grounds of one entity type in the mapping.
	 *
	 * @param string                                    $type   The entity type.
	 * @param mixed                                     $bases  Its grounds as stored.
	 * @param array{rewritten: int, left: list<string>} $report The report so far.
	 *
	 * @return array{0: mixed, 1: array{rewritten: int, left: list<string>}} The grounds and the report.
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	private function repointMappingType(string $type, mixed $bases, array $report): array {
		if (is_array($bases) === false) {
			return [$bases, $report];
		}

		$result = $this->repoint(bases: $bases);
		foreach ($result['left'] as $slug) {
			$report['left'][] = LegalBasisProposalServiceInterface::CONFIG_KEY . ' ' . $type . ' (' . $slug . ')';
		}

		if ($result['bases'] !== $bases) {
			$report['rewritten']++;
		}

		return [$result['bases'], $report];
	}//end repointMappingType()
}//end class
