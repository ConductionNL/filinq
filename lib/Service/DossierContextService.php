<?php

/**
 * Dossier Context Service
 *
 * WHAT A DOSSIER ROW IS MADE OF. The dossier surface shows more than the
 * dossier object holds: the files that make up its membership, the grondslagen
 * its slugs stand for, the anonymisation runs recorded against its folder, and
 * whether the publication pipeline is there at all. Each of those is a read
 * into a different part of the instance, and each can be absent without the
 * dossier being wrong.
 *
 * EVERY LOOKUP HERE DEGRADES RATHER THAN FAILS, with one exception that is the
 * point of the class: a membership reference whose file cannot be resolved
 * comes back marked `missing`, never dropped. A dossier that quietly lists four
 * of its five documents is worse than one that says the fifth is gone.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\Files\Folder;
use OCP\Files\Node;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Assembles the live context a dossier row or detail is shown with.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */
class DossierContextService {

	/**
	 * Memoised `base` vocabulary, slug => name.
	 *
	 * Read once per request: the index resolves grondslagen for every row, and
	 * re-reading the vocabulary per row turns one query into N.
	 *
	 * @var array<string, string>|null
	 */
	private ?array $baseLabels = null;

	/**
	 * Constructor.
	 *
	 * @param DossierObjectRepository $repository OpenRegister access.
	 * @param DossierFileService $files The filesystem half.
	 * @param DossierObjectReader $reader Object-shape reading.
	 * @param LoggerInterface $logger Logger.
	 */
	public function __construct(
		private readonly DossierObjectRepository $repository,
		private readonly DossierFileService $files,
		private readonly DossierObjectReader $reader,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The dossier's effective membership: home folder ∪ documents[].
	 *
	 * @param object $object The dossier object.
	 * @param array<string, mixed> $payload Its payload.
	 *
	 * @return array{documents: array<int, array<string, mixed>>, missing: int} Members and the missing count.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function members(object $object, array $payload): array {
		$documents = $this->folderRows(object: $object, payload: $payload);
		$documents = $this->withReferences(documents: $documents, payload: $payload);

		$missing = array_filter($documents, static fn (array $row): bool => $row['missing'] === true);

		return ['documents' => $documents, 'missing' => count($missing)];

	}//end members()

	/**
	 * The rows of the files in the dossier's home folder, when it has one.
	 *
	 * @param object               $object  The dossier object.
	 * @param array<string, mixed> $payload Its payload.
	 *
	 * @return array<int, array<string, mixed>> The rows.
	 */
	private function folderRows(object $object, array $payload): array {
		$folder = $this->files->homeFolder(object: $object, payload: $payload);
		if ($folder === null) {
			return [];
		}

		$rows = [];
		foreach ($this->files->enumerateFolder(folder: $folder) as $node) {
			$rows[] = $this->documentRow(node: $node, referenced: false);
		}

		return $rows;

	}//end folderRows()

	/**
	 * Add a row per explicit reference not listed yet, in reference order.
	 *
	 * A resolved file is listed once; a reference that cannot be resolved is
	 * listed, marked missing, each time it appears.
	 *
	 * @param array<int, array<string, mixed>> $documents The rows so far.
	 * @param array<string, mixed>             $payload   The dossier payload.
	 *
	 * @return array<int, array<string, mixed>> The rows with the references.
	 */
	private function withReferences(array $documents, array $payload): array {
		$seen = array_fill_keys(array_column($documents, 'id'), true);
		foreach ($this->reader->documentRefs(payload: $payload) as $ref) {
			$fileId = (int)$ref;
			if (isset($seen[$fileId]) === true) {
				continue;
			}

			$row = $this->referenceRow(fileId: $fileId);
			if ($row['missing'] === false) {
				$seen[$fileId] = true;
			}

			$documents[] = $row;
		}

		return $documents;

	}//end withReferences()

	/**
	 * The row of one referenced file.
	 *
	 * @param int $fileId The referenced file id.
	 *
	 * @return array<string, mixed> The row; a missing row when the file cannot be resolved.
	 */
	private function referenceRow(int $fileId): array {
		$node = $this->files->nodeFor(fileId: $fileId);
		if ($node === null) {
			// Visible, never silently dropped: a reference the caller
			// cannot resolve is information, not noise.
			return [
				'id' => $fileId,
				'name' => '',
				'missing' => true,
				'referenced' => true,
			];
		}

		return $this->documentRow(node: $node, referenced: true);

	}//end referenceRow()

	/**
	 * One document row.
	 *
	 * @param Node $node The file node.
	 * @param bool $referenced Whether it is a reference rather than a home-folder file.
	 *
	 * @return array<string, mixed> The row.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function documentRow(Node $node, bool $referenced): array {
		return [
			'id' => $node->getId(),
			'name' => $node->getName(),
			'mimetype' => $node->getMimetype(),
			'size' => $node->getSize(),
			'modified' => $node->getMTime(),
			'missing' => false,
			'referenced' => $referenced,
		];

	}//end documentRow()

	/**
	 * Resolve the dossier's grondslag slugs to their labels.
	 *
	 * An unknown slug is returned with `known: false` rather than dropped —
	 * a grondslag that silently disappears from a Woo dossier is a compliance
	 * problem, not a rendering detail.
	 *
	 * The lookup reads the `base` vocabulary directly. `BasesResolverService`
	 * looks the other way round — it answers "which grondslagen apply to this
	 * batch's folders" and returns slugs — so it cannot serve as the
	 * slug-to-label resolver this needs.
	 *
	 * @param array<string, mixed> $payload The dossier payload.
	 *
	 * @return array<int, array{slug: string, label: string, known: bool}> The resolved bases.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function resolveBases(array $payload): array {
		$slugs = ($payload['bases'] ?? []);
		if (is_array($slugs) === false || count($slugs) === 0) {
			return [];
		}

		$labels = $this->baseLabels();

		$out = [];
		foreach ($slugs as $slug) {
			$slug = (string)$slug;
			$label = ($labels[$slug] ?? '');

			$out[] = [
				'slug' => $slug,
				'label' => $this->reader->firstNonEmpty(value: $label, fallback: $slug),
				'known' => ($label !== ''),
			];
		}

		return $out;

	}//end resolveBases()

	/**
	 * The `base` vocabulary as slug => name.
	 *
	 * @return array<string, string> The labels, empty when OpenRegister is unavailable.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function baseLabels(): array {
		if ($this->baseLabels !== null) {
			return $this->baseLabels;
		}

		$this->baseLabels = [];
		$this->baseLabels = $this->readBaseLabels();

		return $this->baseLabels;

	}//end baseLabels()

	/**
	 * Read the `base` vocabulary; a failing read is logged and keeps what was read before it.
	 *
	 * @return array<string, string> The labels, slug => name.
	 */
	private function readBaseLabels(): array {
		$objectService = $this->repository->objectService();
		if ($objectService === null) {
			return [];
		}

		$labels = [];
		try {
			foreach ($this->reader->findAllOf(objectService: $objectService, schema: 'base') as $base) {
				$labels = $this->withBaseLabel(labels: $labels, base: $base);
			}
		} catch (Throwable $e) {
			$this->logVocabularyFailure(exception: $e);
		}

		return $labels;

	}//end readBaseLabels()

	/**
	 * Log that the `base` vocabulary could not be read.
	 *
	 * @param Throwable $exception The failure.
	 *
	 * @return void
	 */
	private function logVocabularyFailure(Throwable $exception): void {
		$this->logger->warning(
			'DossierContextService: the grondslagen vocabulary could not be read',
			['exception' => $exception->getMessage()]
		);

	}//end logVocabularyFailure()

	/**
	 * Add one vocabulary entry under its slug; an entry without a slug is skipped.
	 *
	 * @param array<string, string> $labels The labels so far.
	 * @param object                $base   The `base` object.
	 *
	 * @return array<string, string> The labels.
	 */
	private function withBaseLabel(array $labels, object $base): array {
		$payload = $this->reader->payloadOf(object: $base);
		$slug = (string)($payload['@self']['slug'] ?? $payload['slug'] ?? '');
		if ($slug === '') {
			return $labels;
		}

		$labels[$slug] = (string)($payload['name'] ?? $slug);
		return $labels;

	}//end withBaseLabel()

	/**
	 * The folder-batch runs recorded against this dossier's folder.
	 *
	 * @param object $object The dossier object.
	 * @param array<string, mixed> $payload Its payload.
	 *
	 * @return array<int, array<string, mixed>> The batch runs, newest first.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function batchRuns(object $object, array $payload): array {
		$objectService = $this->repository->objectService();
		if ($objectService === null) {
			return [];
		}

		$folder = $this->files->homeFolder(object: $object, payload: $payload);
		if ($folder === null) {
			return [];
		}

		$runs = $this->runsInFolder(batches: $this->batchesOrNone(objectService: $objectService), folder: $folder);
		usort($runs, static fn (array $a, array $b): int => strcmp($b['created'], $a['created']));

		return $runs;

	}//end batchRuns()

	/**
	 * Every anonymisation batch, or none when they cannot be read.
	 *
	 * @param object $objectService OpenRegister's object service.
	 *
	 * @return array<int, object> The batches.
	 */
	private function batchesOrNone(object $objectService): array {
		try {
			return $this->reader->findAllOf(objectService: $objectService, schema: 'anonymizationBatch');
		} catch (Throwable $e) {
			return [];
		}

	}//end batchesOrNone()

	/**
	 * The runs of the batches recorded against this folder, in batch order.
	 *
	 * @param array<int, object> $batches The batches.
	 * @param Folder             $folder  The dossier's home folder.
	 *
	 * @return array<int, array<string, mixed>> The runs.
	 */
	private function runsInFolder(array $batches, Folder $folder): array {
		$runs = [];
		foreach ($batches as $batch) {
			$batchPayload = $this->reader->payloadOf(object: $batch);
			if ((int)($batchPayload['folderId'] ?? 0) === $folder->getId()) {
				$runs[] = $this->runRow(batchPayload: $batchPayload);
			}
		}

		return $runs;

	}//end runsInFolder()

	/**
	 * One run as the detail shows it.
	 *
	 * @param array<string, mixed> $batchPayload The batch payload.
	 *
	 * @return array<string, mixed> The run.
	 */
	private function runRow(array $batchPayload): array {
		return [
			'id' => (string)($batchPayload['batchId'] ?? ''),
			'status' => (string)($batchPayload['status'] ?? ''),
			'fileCount' => (int)($batchPayload['fileCount'] ?? 0),
			'created' => (string)($batchPayload['created'] ?? ''),
		];

	}//end runRow()

	/**
	 * The publication section, presence-gated on the Woo pipeline.
	 *
	 * @return array{installed: bool, state: string} The publication state.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function publication(): array {
		// The woo-publicatie-pipeline has not shipped. Reporting `installed:
		// false` lets the detail explain the capability is absent instead of
		// offering a publish action that would fail.
		return ['installed' => false, 'state' => ''];

	}//end publication()

}//end class
