<?php

/**
 * Filinq Paper Original Filing
 *
 * Where the scan of a paper original goes (REQ-PAD-003, design D3): a
 * segment whose separator names an original is filed in the folder of the
 * project's target record, not in the intake inbox, and the original records
 * the scan. Which originals a person checks is DigitisationProjectService's
 * draw.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCP\Files\Folder;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Finds where an original's scan is filed and records the scan on it.
 *
 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md
 */
class PaperOriginalFiling {

	/**
	 * The register the project and its originals live in.
	 */
	public const REGISTER = 'filinq';

	/**
	 * Constructor.
	 *
	 * @param ObjectServiceInterface     $objectService OpenRegister's object contract.
	 * @param DossierFileService         $files         The target record's folder.
	 * @param DigitisationProjectService $projects      The sampling draw.
	 * @param LoggerInterface            $logger        An original that cannot be placed.
	 */
	public function __construct(
		private readonly ObjectServiceInterface $objectService,
		private readonly DossierFileService $files,
		private readonly DigitisationProjectService $projects,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * The original, its project and the folder its scan is filed in, or null
	 * when any of them cannot be found.
	 *
	 * @param string $originalId The paperOriginal a separator named.
	 *
	 * @return array{original: array<string, mixed>, project: array<string, mixed>, folder: Folder}|null
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-scan-of-an-original-is-filed-on-the-projects-record-req-pad-003
	 */
	public function placeFor(string $originalId): ?array {
		try {
			$original = $this->payload(entity: $this->find(id: $originalId, register: self::REGISTER, schema: 'paperOriginal'), id: $originalId);
			$projectId = trim((string)($original['project'] ?? ''));
			$project = $this->payload(entity: $this->find(id: $projectId, register: self::REGISTER, schema: 'digitisationProject'), id: $projectId);
			$target = (array)($project['target'] ?? []);
			$entity = $this->find(id: (string)($target['id'] ?? ''), register: (string)($target['register'] ?? ''), schema: (string)($target['schema'] ?? ''));
			if ($original === null || $project === null || $entity === null) {
				return null;
			}

			$folder = $this->files->homeFolder(object: $entity, payload: (array)$entity->getObject());
		} catch (Throwable $e) {
			$this->logger->warning('PaperOriginalFiling: the original could not be placed', ['originalId' => $originalId, 'exception' => $e->getMessage()]);
			return null;
		}

		if ($folder === null) {
			return null;
		}

		return ['original' => $original, 'project' => $project, 'folder' => $folder];
	}//end placeFor()

	/**
	 * The scan's file name: the shelf mark and the description.
	 *
	 * @param array<string, mixed> $original The paperOriginal.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-scan-of-an-original-is-filed-on-the-projects-record-req-pad-003
	 */
	public function fileName(array $original): string {
		$parts = array_filter(
			[trim((string)($original['shelfMark'] ?? '')), trim((string)($original['description'] ?? ''))],
			static fn (string $part): bool => $part !== ''
		);
		$name = (string)preg_replace('/[\\\\\/:*?"<>|\x00-\x1f]+/', '-', implode(' - ', $parts));
		$name = trim(mb_substr($name, 0, 180));
		if ($name === '') {
			$name = 'scan-' . (string)($original['id'] ?? 'original');
		}

		return $name . '.pdf';
	}//end fileName()

	/**
	 * Record the scan on the original: the file, the batch, the moment and
	 * what the draw says (in the checking queue as `scanned`, or `checked`).
	 *
	 * @param array{original: array<string, mixed>, project: array<string, mixed>, folder: Folder} $place The place placeFor() found.
	 * @param array{fileId: int, pages: int, batchId: string} $scan The filed scan.
	 *
	 * @return array<string, mixed> The original as written.
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-scan-of-an-original-is-filed-on-the-projects-record-req-pad-003
	 */
	public function markScanned(array $place, array $scan): array {
		$now = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
		$original = $place['original'];
		$uuid = (string)($original['id'] ?? '');
		unset($original['@self']);
		$original = array_merge(
			$original,
			$this->projects->afterScan(project: $place['project'], separatorReadable: true, pageCount: $scan['pages'], now: $now),
			['document' => $scan['fileId'], 'scannedAt' => $now, 'scanBatch' => $scan['batchId']]
		);

		$this->objectService->saveObject(
			object: $original,
			options: new ObjectSaveOptions(
				target: new ObjectFindTarget(register: self::REGISTER, schema: 'paperOriginal'),
				uuid: $uuid
			),
			_rbac: false,
			_multitenancy: false
		);

		return $original;
	}//end markScanned()

	/**
	 * One object, or null when the id is empty or it does not exist.
	 *
	 * @param string $id       The id.
	 * @param string $register The register.
	 * @param string $schema   The schema.
	 *
	 * @return object|null
	 */
	private function find(string $id, string $register, string $schema): ?object {
		if (trim($id) === '' || $register === '' || $schema === '') {
			return null;
		}

		try {
			return $this->objectService->find(
				id: trim($id),
				options: new ObjectFindOptions(register: $register, schema: $schema),
				_rbac: false,
				_multitenancy: false
			);
		} catch (Throwable $e) {
			return null;
		}
	}//end find()

	/**
	 * An entity's payload with its id, or null.
	 *
	 * @param object|null $entity The entity.
	 * @param string      $id     The id it was found by.
	 *
	 * @return array<string, mixed>|null
	 */
	private function payload(?object $entity, string $id): ?array {
		if ($entity === null || method_exists($entity, 'getObject') === false) {
			return null;
		}

		$payload = (array)$entity->getObject();
		$payload['id'] = (string)($payload['id'] ?? trim($id));

		return $payload;
	}//end payload()
}//end class
