<?php

/**
 * Dossier Placeholder Ranker
 *
 * Reproduces OpenRegister's deterministic dossier-scoped placeholder
 * numbering for a folder's files, so a per-dossier report regenerated without
 * a live anonymise run shows the SAME scope-local number the documents carry
 * (e.g. `[DATUM: 6]`) rather than the global entity id.
 *
 * Extracted from {@see LegalBasesSummaryService}. It is also the single seam
 * through which Filinq reaches OpenRegister's ranking helper: that helper is
 * a static API on an OPTIONAL app, so — like every other OR reference in this
 * codebase — it is named by string and probed with `class_exists()` before
 * use. Keeping that in one small class means the rest of the pipeline never
 * touches OR's static surface.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCP\Files\File;
use OCP\Files\Folder;
use Psr\Log\LoggerInterface;

/**
 * Ranks a dossier's entity ids by first appearance, mirroring OpenRegister.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class DossierPlaceholderRanker {

	/**
	 * Fully-qualified name of OpenRegister's placeholder-id translator.
	 *
	 * Referenced as a string, not as a `::class` constant: OpenRegister is an
	 * optional dependency, its translator has a private constructor (so it
	 * cannot be injected), and a hard reference would autoload a class that
	 * may not exist on this instance.
	 *
	 * @var string
	 */
	private const PLACEHOLDER_TRANSLATOR_CLASS = '\OCA\OpenRegister\Service\File\PlaceholderIdTranslator';

	/**
	 * Folder names holding redacted OUTPUT, excluded from the source-file walk.
	 *
	 * @var array<int, string>
	 */
	private const OUTPUT_FOLDER_NAMES = [
		'anonymised',
		'anonymized',
		'redacted',
	];

	/**
	 * Constructor.
	 *
	 * @param DossierObjectRepository $repository OpenRegister object access.
	 * @param LoggerInterface $logger Structured logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DossierObjectRepository $repository,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Rank the dossier's entity ids by first appearance.
	 *
	 * Ranks distinct entity ids by first appearance under the total order
	 * (file_id, position_start, entity_id), reusing OpenRegister's own
	 * `rankByFirstAppearance` so the ranking can never drift from the
	 * anonymise path. Returns empty sets when OpenRegister is absent or too
	 * old; callers then fall back to the global-id behaviour.
	 *
	 * @param Folder $folder The dossier folder.
	 *
	 * @return array{ranks: array<array-key, int>, types: array<string, string>} Ranks per entity
	 *                                                                           id, and each id's
	 *                                                                           entity TYPE.
	 *
	 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md#requirement-the-per-dossier-summary-must-aggregate-per-document-and-per-grondslag
	 */
	public function rank(Folder $folder): array {
		$empty = ['ranks' => [], 'types' => []];
		$mapper = $this->repository->entityRelationMapper();
		if ($this->supportsRanking(mapper: $mapper) === false) {
			return $empty;
		}

		$fileIds = $this->collectFileIds(folder: $folder);
		if ($fileIds === []) {
			return $empty;
		}

		try {
			$rows = $mapper->findEntityIdsByValueForFiles(fileIds: $fileIds);
		} catch (Exception $e) {
			$this->logger->warning(
				'LegalBasesSummaryService: dossier placeholder recompute failed; falling back to global ids',
				['error' => $e->getMessage()]
			);
			return $empty;
		}

		// Deterministic dossier ranking — identical to the anonymise path.
		$translator = self::PLACEHOLDER_TRANSLATOR_CLASS;
		$ranks = $translator::rankByFirstAppearance(rows: $rows);
		if ($ranks === []) {
			return $empty;
		}

		return [
			'ranks' => $ranks,
			'types' => $this->entityTypes(mapper: $mapper, fileIds: $fileIds),
		];

	}//end rank()

	/**
	 * Sort key for a `[<TYPE>: <number>]` placeholder: [type, number] so a
	 * spaceship compare orders by type alphabetically then by number
	 * NUMERICALLY ascending (1,2,…,10,11 — not the lexical 1,10,11,2). A
	 * placeholder that doesn't match the shape sorts last, by its raw string.
	 *
	 * @param string $placeholder The placeholder string.
	 *
	 * @return array{0: string, 1: int} The [type, number] sort key.
	 */
	public function sortKey(string $placeholder): array {
		if (preg_match('/^\[(.+):\s*(\d+)\]\s*$/u', $placeholder, $matches) === 1) {
			return [$matches[1], (int)$matches[2]];
		}

		return [$placeholder, PHP_INT_MAX];
	}//end sortKey()

	/**
	 * Collect the descendant file ids of a dossier folder (recursive), skipping
	 * the redacted-output subfolders — mirrors the entity walk so the
	 * recompute ranks over the same source-file set the rows come from.
	 *
	 * @param Folder $folder The dossier folder.
	 *
	 * @return array<int, int> Distinct descendant source file ids.
	 *
	 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md#requirement-the-per-dossier-summary-must-aggregate-per-document-and-per-grondslag
	 */
	public function collectFileIds(Folder $folder): array {
		$ids = [];
		try {
			foreach ($folder->getDirectoryListing() as $node) {
				if ($node instanceof Folder) {
					if (in_array($node->getName(), self::OUTPUT_FOLDER_NAMES, true) === true) {
						continue;
					}

					foreach ($this->collectFileIds(folder: $node) as $nestedId) {
						$ids[] = $nestedId;
					}

					continue;
				}

				if (($node instanceof File) === true) {
					$ids[] = (int)$node->getId();
				}
			}
		} catch (Exception $e) {
			$this->logger->warning(
				'LegalBasesSummaryService: dossier file enumeration failed',
				['error' => $e->getMessage()]
			);
		}//end try

		return array_values(array_unique($ids));
	}//end collectFileIds()

	/**
	 * Whether OpenRegister exposes everything the recompute needs.
	 *
	 * @param mixed $mapper The EntityRelationMapper, or null.
	 *
	 * @return bool True when the ranking can be reproduced.
	 */
	private function supportsRanking(mixed $mapper): bool {
		return $mapper !== null
			&& method_exists($mapper, 'findEntityIdsByValueForFiles') === true
			&& method_exists($mapper, 'findEntityIdsByValueForFile') === true
			&& class_exists(self::PLACEHOLDER_TRANSLATOR_CLASS) === true;

	}//end supportsRanking()

	/**
	 * Resolve each entity id's TYPE from the per-file value→{id,type} maps.
	 *
	 * @param mixed $mapper The EntityRelationMapper.
	 * @param array<int, int> $fileIds The dossier's source file ids.
	 *
	 * @return array<string, string> Map of entity id → entity TYPE.
	 */
	private function entityTypes(mixed $mapper, array $fileIds): array {
		$types = [];
		foreach ($fileIds as $fileId) {
			try {
				foreach ($mapper->findEntityIdsByValueForFile($fileId) as $entry) {
					$types[(string)($entry['id'] ?? '')] = (string)($entry['type'] ?? '');
				}
			} catch (Exception $e) {
				continue;
			}
		}

		return $types;
	}//end entityTypes()
}//end class
