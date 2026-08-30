<?php

/**
 * Legal Bases Summary Service
 *
 * Renders per-document and per-dossier grondslagen summary PDFs. Reads
 * EntityRelation.bases (OpenRegister, Wave 1.3 — entity-relation-grondslagen)
 * and resolves the bases against the `base` schema (Filinq, Wave 1.1 —
 * add-dossier-schema) to produce an auditable record of "what was redacted
 * under which Woo Art. 5 grondslag" for the file or dossier.
 *
 * Two rendering surfaces:
 *
 *   - **Per-document append.** When the anonymise endpoint is called with
 *     `appendBasisSummary: true`, this service renders the summary as a single
 *     extra page and appends it to the anonymised PDF using mPDF + FPDI. When
 *     the output isn't PDF (operator opted for `outputFormat: "preserve"`),
 *     the summary is saved as a separate `_grondslagen.pdf` file in the same
 *     folder as the anonymised file.
 *
 *   - **Per-dossier on-demand.** A dedicated endpoint regenerates the
 *     per-dossier summary PDF aggregating every file under the dossier's
 *     folder. The same render also fires automatically when the dossier's
 *     `checkedOn` review timestamp is updated.
 *
 * No new persistence beyond the existing dossier object's `configuration`
 * JSON field, which records the generated file's UUID and timestamp so the
 * dossier UI can badge the report as fresh / stale.
 *
 * This class decides WHAT the report says. Reading the data is
 * {@see DossierSummaryDataService}'s job; getting the PDF onto disk is
 * {@see GrondslagenPdfWriter}'s.
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

use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Renderer for the per-document and per-dossier grondslagen summary PDFs.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class LegalBasesSummaryService {

	/**
	 * Suffix applied to the source file's base name when the per-document
	 * append falls back to a separate-PDF file (operator chose to preserve
	 * the native output format and the anonymised file isn't a PDF).
	 */
	private const SUMMARY_FILE_SUFFIX = '_grondslagen.pdf';

	/**
	 * Entity-type labels localised in the placeholder, mirroring
	 * OpenRegister's `DocumentProcessingHandler::LOCALIZABLE_ENTITY_TYPES`
	 * (the `EntityRecognitionHandler::ENTITY_TYPE_*` values). Only these are
	 * translated so the summary legend reads the same as the labels
	 * OpenRegister wrote into the redacted document; an unknown type falls
	 * back to its raw string. Filinq's `l10n/` carries the same Dutch
	 * translations so the two apps resolve identically for a given language.
	 *
	 * @var array<int, string>
	 */
	private const LOCALIZABLE_ENTITY_TYPES = [
		'PERSON',
		'ORGANIZATION',
		'LOCATION',
		'EMAIL',
		'PHONE',
		'ADDRESS',
		'DATE',
		'IBAN',
		'SSN',
		'IP_ADDRESS',
	];

	/**
	 * Everything the report needs to read.
	 *
	 * @var DossierSummaryDataService
	 */
	private readonly DossierSummaryDataService $data;

	/**
	 * Everything the report needs to render and write.
	 *
	 * @var GrondslagenPdfWriter
	 */
	private readonly GrondslagenPdfWriter $pdfWriter;

	/**
	 * Constructor.
	 *
	 * The two collaborators are injected; the null defaults keep the
	 * historical six/seven-argument signature usable, in which case equivalent
	 * collaborators are wired from the same dependencies.
	 *
	 * @param LoggerInterface $logger Structured logger.
	 * @param PdfService $pdfService Twig + mPDF renderer.
	 * @param IRootFolder $rootFolder Nextcloud file API entry point.
	 * @param IUserSession $userSession Session-user lookup for the "operator" header field.
	 * @param IAppManager $appManager App-availability check for OpenRegister.
	 * @param ContainerInterface $container DI container for OpenRegister-side services
	 *                                      (EntityRelationMapper, ObjectService).
	 * @param IL10N|null $l10n Acting-user localisation, used to translate the
	 *                         placeholder TYPE label (PERSON → PERSOON on a Dutch
	 *                         instance) so the summary legend matches the localized
	 *                         labels OpenRegister wrote into the redacted document.
	 *                         Nullable: when absent the raw English label is used.
	 * @param DossierSummaryDataService|null $data Report data source; wired from the above when null.
	 * @param GrondslagenPdfWriter|null $pdfWriter PDF rendering + persistence; wired when null.
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		PdfService $pdfService,
		IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		IAppManager $appManager,
		ContainerInterface $container,
		private readonly ?IL10N $l10n = null,
		?DossierSummaryDataService $data = null,
		?GrondslagenPdfWriter $pdfWriter = null,
	) {
		$this->data = ($data ?? new DossierSummaryDataService(
			$rootFolder,
			$this->userSession,
			$appManager,
			$container,
			$this->logger
		));

		$this->pdfWriter = ($pdfWriter ?? new GrondslagenPdfWriter($pdfService));

	}//end __construct()

	/**
	 * Append a grondslagen summary page to an already-anonymised PDF file.
	 *
	 * Loads the anonymised file's entity relations from OR, resolves their
	 * `bases` to human-readable `base.name` labels, renders the per-document
	 * template, and appends the resulting PDF page to the anonymised file.
	 *
	 * The append is atomic — on any rendering or PDF-merge failure the
	 * anonymised file is left untouched and the caller receives a warning
	 * via the thrown exception. The caller MUST handle the failure
	 * gracefully (the spec calls for returning HTTP 200 with a `warning`
	 * field rather than failing the entire anonymise call).
	 *
	 * @param File $anonymisedFile The anonymised PDF file (must already be a PDF).
	 * @param int $sourceFileId The Nextcloud file ID of the original (pre-anonymisation)
	 *                          source — used to read the EntityRelation rows that
	 *                          record the redactions performed against it.
	 * @param array<string, string> $placeholderMap Optional global entity id → emitted placeholder
	 *                                              map (e.g. "7" => "[PERSOON: 1]"); when set the summary renders the
	 *                                              SAME placeholder the document carries instead of re-deriving it.
	 *
	 * @return File The same anonymised file, with the summary page appended.
	 *
	 * @throws \RuntimeException When template rendering, PDF merging, or file write fails.
	 *
	 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md#requirement-the-per-document-anonymise-endpoint-must-accept-an-optional-appendbasissummary-field
	 */
	public function appendSummaryToPdf(File $anonymisedFile, int $sourceFileId, array $placeholderMap = []): File {
		$summaryBytes = $this->renderPerDocumentSummary(
			anonymisedFile: $anonymisedFile,
			sourceFileId: $sourceFileId,
			placeholderMap: $placeholderMap
		);

		$this->pdfWriter->appendToPdf(anonymisedFile: $anonymisedFile, summaryBytes: $summaryBytes);

		$this->logger->info(
			'LegalBasesSummaryService: appended summary to anonymised PDF',
			[
				'fileId' => $anonymisedFile->getId(),
				'sourceFileId' => $sourceFileId,
			]
		);

		return $anonymisedFile;
	}//end appendSummaryToPdf()

	/**
	 * Produce a separate grondslagen-summary PDF beside the anonymised file.
	 *
	 * Used when the operator chose `outputFormat: "preserve"` and the
	 * anonymised file is not a PDF — the summary cannot be appended in
	 * place, so we write it as `<anonymised-base>_grondslagen.pdf` in the
	 * same parent folder.
	 *
	 * @param File $anonymisedFile The anonymised file (any format).
	 * @param int $sourceFileId The pre-anonymisation source file ID.
	 * @param array<string, string> $placeholderMap Optional global entity id → emitted placeholder
	 *                                              map so the summary renders the SAME placeholder the document carries.
	 *
	 * @return File The newly-written summary PDF.
	 *
	 * @throws \RuntimeException When rendering or write fails.
	 *
	 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md#requirement-the-per-document-anonymise-endpoint-must-accept-an-optional-appendbasissummary-field
	 */
	public function renderSummaryBesideFile(File $anonymisedFile, int $sourceFileId, array $placeholderMap = []): File {
		$summaryBytes = $this->renderPerDocumentSummary(
			anonymisedFile: $anonymisedFile,
			sourceFileId: $sourceFileId,
			placeholderMap: $placeholderMap
		);

		$baseName = pathinfo($anonymisedFile->getName(), PATHINFO_FILENAME);
		$written = $this->pdfWriter->writeBesideFile(
			anonymisedFile: $anonymisedFile,
			summaryFileName: $baseName . self::SUMMARY_FILE_SUFFIX,
			summaryBytes: $summaryBytes
		);

		$message = 'LegalBasesSummaryService: wrote beside-file summary';
		if ($written['refreshed'] === true) {
			$message = 'LegalBasesSummaryService: refreshed beside-file summary';
		}

		$this->logger->info(
			$message,
			['fileId' => $written['file']->getId(), 'path' => $written['file']->getPath()]
		);

		return $written['file'];
	}//end renderSummaryBesideFile()

	/**
	 * Assert the dossier exists before (re)generating its summary.
	 *
	 * ⚠️ The name of this method overstates what it does, and the docblock it
	 * replaces overstated it further ("a successful resolution means the
	 * operator is permitted"). It is an EXISTENCE check. See
	 * `DossierSummaryDataService::assertDossierReadable()` for the measured
	 * reason: OpenRegister's RBAC cascade resolves to "configured nowhere" for
	 * the `dossier` schema, which OpenRegister treats as open, so the refusal
	 * this method relies on cannot fire for an existing dossier.
	 *
	 * What remains true: the HTTP layer (`DossierController`) does call this
	 * before `renderDossierSummary`, and the render itself deliberately runs
	 * as a system operation with RBAC disabled — so this call is the ONLY
	 * pre-render check there is, which is exactly why its real strength
	 * matters. Tracked in ConductionNL/filinq#441.
	 *
	 * @param string $dossierId The OR dossier object UUID.
	 *
	 * @return void
	 *
	 * @throws \RuntimeException 403 when the dossier cannot be resolved at all.
	 */
	public function authorizeAccess(string $dossierId): void {
		$this->data->assertDossierReadable(dossierId: $dossierId);

	}//end authorizeAccess()

	/**
	 * Render the per-dossier summary PDF for one dossier.
	 *
	 * Aggregates anonymisation data across every file under the dossier's
	 * folder. The resulting PDF is written to a deterministic location
	 * (per Wave 2's `anonymisation-output-folder-layout` when shipped:
	 * `<dossier-folder>/anonymised/grondslagen.pdf`; until then:
	 * `<dossier-folder>/grondslagen.pdf`).
	 *
	 * On success the method also updates the dossier object's
	 * `configuration.grondslagen.{fileId, lastGeneratedAt}` so the dossier
	 * UI can badge the summary's freshness.
	 *
	 * @param string $dossierUuid The OR UUID of the dossier object.
	 *
	 * @return File The generated summary PDF.
	 *
	 * @throws \RuntimeException When the dossier can't be loaded, the folder
	 *                           isn't accessible, or rendering fails.
	 *
	 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md#requirement-a-per-dossier-summary-endpoint-must-exist
	 */
	public function renderDossierSummary(string $dossierUuid): File {
		$loaded = $this->data->loadDossier(dossierUuid: $dossierUuid);
		$dossier = $loaded['context'];
		$folder = $loaded['folder'];

		// Recompute the dossier's scope-local placeholder map up-front so every
		// file's rows render the SAME scope-local number the documents carry
		// (e.g. [DATUM: 6]) instead of the global entity_id fallback (the
		// 1600+ ids). Reuses OpenRegister's deterministic dossier ranking.
		$placeholderMap = $this->localisePlaceholderMap(
			ranking: $this->data->placeholderRanking(folder: $folder)
		);

		$perFile = $this->data->walkDossierFiles(folder: $folder, placeholderMap: $placeholderMap);

		// The per-file load already resolves base labels per file.
		// aggregateForDossier just unfolds those rows across files and sorts.
		// No second label-resolution pass needed here.
		$aggregated = $this->aggregateForDossier(perFile: $perFile, labelMap: []);

		// Distinct grondslagen assigned across every file in the dossier, with
		// their Woo Art. 5 toelichting — rendered as a legend under the table.
		$allEntities = [];
		foreach ($perFile as $fileRow) {
			foreach ($fileRow['entities'] as $entity) {
				$allEntities[] = $entity;
			}
		}

		$pdfBytes = $this->pdfWriter->renderDossierPdf(
			data: [
				'dossier' => [
					'name' => (string)($dossier['name'] ?? ''),
					'description' => (string)($dossier['description'] ?? ''),
					'checkedOn' => (string)($dossier['checkedOn'] ?? ''),
				],
				'generatedAt' => date('c'),
				'rows' => $aggregated['rows'],
				'totals' => $aggregated['totals'],
				'bases' => $this->collectAssignedBases(entities: $allEntities),
			],
			dossierUuid: $dossierUuid
		);

		$summaryFile = $this->pdfWriter->saveDossierSummary(folder: $folder, pdfBytes: $pdfBytes);

		$this->data->updateDossierConfiguration(
			dossierUuid: $dossierUuid,
			summaryFileId: $summaryFile->getId()
		);

		$this->logger->info(
			'LegalBasesSummaryService: rendered per-dossier summary',
			[
				'dossierUuid' => $dossierUuid,
				'summaryFileId' => $summaryFile->getId(),
				'fileCount' => count($perFile),
				'totalEntities' => $aggregated['totals']['entityCount'],
			]
		);

		return $summaryFile;
	}//end renderDossierSummary()

	/**
	 * Pair a dossier ranking with localized TYPE labels to form the map.
	 *
	 * The per-dossier report is regenerated without a live anonymise run, so
	 * there is no placeholder map from OpenRegister to reuse — without one,
	 * each entity falls back to its GLOBAL entity_id (the 1600+ numbers). The
	 * ranking is reproduced up-front (reusing OpenRegister's own deterministic
	 * ordering, so it can never drift from the anonymise path); this method
	 * pairs each rank with the LOCALIZED type label so the report shows the
	 * SAME `[<TYPE>: <number>]` the documents carry.
	 *
	 * An empty ranking (OpenRegister absent or too old) yields an empty map;
	 * callers then fall back to the global-id behaviour.
	 *
	 * @param array{ranks: array<array-key, int>, types: array<string, string>} $ranking The dossier ranking.
	 *
	 * @return array<string, string> Map of global entity id → "[<localizedTYPE>: <dossier number>]".
	 */
	private function localisePlaceholderMap(array $ranking): array {
		$map = [];
		foreach ($ranking['ranks'] as $entityId => $rank) {
			$type = ($ranking['types'][(string)$entityId] ?? '');
			$map[(string)$entityId] = '[' . $this->localizeEntityType(entityType: $type) . ': ' . $rank . ']';
		}

		return $map;
	}//end localisePlaceholderMap()

	/**
	 * Build the row set the per-dossier template renders.
	 *
	 * Produces ONE row per distinct entity (`entityType:entityId`). Because the
	 * dossier number is consistent across the dossier's files, the same
	 * person/date appears once — its occurrence `count` is summed, the files it
	 * appears in are collected into a comma-joined `filename` list, and its
	 * grondslagen are unioned. Rows are sorted by TYPE then NUMERIC id ascending
	 * so the type blocks read 1,2,…,10,11.
	 *
	 * Per-file entities arrive pre-aggregated from the entity collector: each
	 * entry already has `placeholder`, `count`, and `basesText` (Dutch labels
	 * joined).
	 *
	 * @param array<int, mixed> $perFile Per-file rows — each entry shaped as
	 *                                   `{fileId, filename, entities[]}`.
	 * @param array<string, string> $labelMap Map of base-ref → human-readable label
	 *                                        (unused here — labels are already
	 *                                        resolved upstream; kept for signature
	 *                                        compat).
	 *
	 * @return array<string, mixed> Shape:
	 *                              `{ rows: array<int, {placeholder, filename,
	 *                              fileCount, count, baseLabels, basesText,
	 *                              entityType, entityId}>,
	 *                              totals: { documentCount, entityCount,
	 *                              distinctEntityCount, distinctBasesCount } }`.
	 */
	private function aggregateForDossier(array $perFile, array $labelMap): array {
		unset($labelMap);

		$grouped = [];
		$totalOccurrences = 0;
		$distinctBasisRefs = [];

		foreach ($perFile as $fileRow) {
			$filename = (string)($fileRow['filename'] ?? '');

			foreach (($fileRow['entities'] ?? []) as $entity) {
				$count = (int)($entity['count'] ?? 0);
				$baseLabels = ($entity['baseLabels'] ?? []);
				if (is_array($baseLabels) === false) {
					$baseLabels = [];
				}

				$totalOccurrences += $count;

				foreach (($entity['bases'] ?? []) as $ref) {
					$distinctBasisRefs[(string)$ref] = true;
				}

				// Dedup to ONE row per distinct entity (entityType:entityId).
				// The dossier number is consistent across files, so the same
				// person/date appears once — aggregating its occurrence count,
				// the files it appears in, and the union of its grondslagen —
				// instead of repeating the same placeholder once per file.
				$entityKey = (string)($entity['entityType'] ?? '') . ':' . (string)($entity['entityId'] ?? '');
				if (isset($grouped[$entityKey]) === false) {
					$grouped[$entityKey] = [
						'placeholder' => (string)($entity['placeholder'] ?? ''),
						'entityType' => (string)($entity['entityType'] ?? ''),
						'entityId' => (int)($entity['entityId'] ?? 0),
						'count' => 0,
						'filenames' => [],
						'baseLabels' => [],
					];
				}

				$grouped[$entityKey]['count'] += $count;
				if ($filename !== '') {
					$grouped[$entityKey]['filenames'][$filename] = true;
				}

				foreach ($baseLabels as $label) {
					$grouped[$entityKey]['baseLabels'][(string)$label] = true;
				}
			}//end foreach
		}//end foreach

		return [
			'rows' => $this->buildDossierRows(grouped: $grouped),
			'totals' => [
				'documentCount' => count($perFile),
				'entityCount' => $totalOccurrences,
				'distinctEntityCount' => count($grouped),
				'distinctBasesCount' => count($distinctBasisRefs),
			],
		];

	}//end aggregateForDossier()

	/**
	 * Turn the grouped dossier entities into the sorted template row set.
	 *
	 * Rows are ordered by TYPE then NUMERIC id ascending (1,2,…,10,11 — not
	 * the lexical 1,10,11,2), then by the joined filename list.
	 *
	 * @param array<string, array<string, mixed>> $grouped Entities grouped by `entityType:entityId`.
	 *
	 * @return array<int, array<string, mixed>> The sorted rows.
	 */
	private function buildDossierRows(array $grouped): array {
		$rows = [];
		foreach ($grouped as $group) {
			$files = array_keys($group['filenames']);
			sort($files);
			$labels = array_keys($group['baseLabels']);

			$rows[] = [
				'placeholder' => $group['placeholder'],
				'count' => $group['count'],
				// Joined distinct filenames — the entity may span several files
				// in the dossier; the twig renders this list in the "Bestanden"
				// column.
				'filename' => implode(', ', $files),
				'fileCount' => count($files),
				'baseLabels' => $labels,
				'basesText' => implode(', ', $labels),
				'entityType' => $group['entityType'],
				'entityId' => $group['entityId'],
			];
		}

		$data = $this->data;
		usort(
			$rows,
			static function (array $a, array $b) use ($data): int {
				// By TYPE then NUMERIC id ascending (1,2,…,10,11), then files.
				$cmp = ($data->placeholderSortKey(placeholder: $a['placeholder']) <=> $data->placeholderSortKey(placeholder: $b['placeholder']));
				if ($cmp !== 0) {
					return $cmp;
				}

				return strcmp($a['filename'], $b['filename']);
			}
		);

		return $rows;
	}//end buildDossierRows()

	/**
	 * Resolve a list of `base` references (slugs or UUIDs) to human-readable labels.
	 *
	 * @param array<int, string> $baseRefs Slugs or UUIDs of base records.
	 *
	 * @return array<string, array{name: string, description: string}> Map from each
	 *                                                                 reference to its
	 *                                                                 display name and
	 *                                                                 Woo Art. 5 toelichting.
	 */
	private function resolveBaseLabels(array $baseRefs): array {
		return $this->data->resolveBaseLabels(baseRefs: $baseRefs);
	}//end resolveBaseLabels()

	/**
	 * Collect the distinct grondslagen assigned across the given entities,
	 * each with its name + description (the Woo Art. 5 toelichting), for the
	 * explanatory legend rendered under the summary table.
	 *
	 * @param array<int, array{bases?: array<int, string>}> $entities Shaped entities (each carrying a `bases` ref list).
	 *
	 * @return array<int, array{name: string, description: string}> Distinct bases, sorted by name.
	 */
	private function collectAssignedBases(array $entities): array {
		$refs = [];
		foreach ($entities as $entity) {
			foreach (($entity['bases'] ?? []) as $ref) {
				$refs[(string)$ref] = true;
			}
		}

		if (count($refs) === 0) {
			return [];
		}

		return $this->data->groupBasesByName(
			detail: $this->resolveBaseLabels(baseRefs: array_keys($refs))
		);

	}//end collectAssignedBases()

	/**
	 * Localise an entity-type label for the summary placeholder so it reads
	 * the same as the label OpenRegister wrote into the redacted document
	 * (anonymisation-placeholder-id-scope). Only the enumerated
	 * `LOCALIZABLE_ENTITY_TYPES` set is translated; an unknown / free-form type
	 * is returned unchanged. When no `IL10N` is injected the raw label is
	 * returned (the `en` / untranslated behaviour).
	 *
	 * @param string $entityType The raw entity type (e.g. 'PERSON').
	 *
	 * @return string The localised label (e.g. 'PERSOON' on nl), or the raw type.
	 */
	private function localizeEntityType(string $entityType): string {
		if ($this->l10n === null
			|| in_array($entityType, self::LOCALIZABLE_ENTITY_TYPES, true) === false
		) {
			return $entityType;
		}

		return $this->l10n->t($entityType);
	}//end localizeEntityType()

	/**
	 * Render the per-document summary template into PDF bytes.
	 *
	 * Shared between {@see appendSummaryToPdf} and
	 * {@see renderSummaryBesideFile} — both produce the same summary
	 * content; only the destination differs.
	 *
	 * @param File $anonymisedFile The anonymised file (for header context).
	 * @param int $sourceFileId The pre-anonymisation source file id.
	 * @param array<string, string> $placeholderMap Optional global entity id → emitted placeholder
	 *                                              map, threaded to the entity collector.
	 *
	 * @return string The rendered PDF (PDF/A-3b) as raw bytes.
	 *
	 * @throws \RuntimeException When template or PDF rendering fails.
	 */
	private function renderPerDocumentSummary(File $anonymisedFile, int $sourceFileId, array $placeholderMap = []): string {
		$entities = $this->data->loadAnonymisedEntitiesForFile(
			fileId: $sourceFileId,
			placeholderMap: $placeholderMap
		);

		$totalOccurrences = 0;
		foreach ($entities as $entity) {
			$totalOccurrences += (int)($entity['count'] ?? 0);
		}

		$operator = 'system';
		$user = $this->userSession->getUser();
		if ($user !== null) {
			$operator = $user->getDisplayName();
		}

		return $this->pdfWriter->renderPerDocumentPdf(
			data: [
				'document' => [
					'filename' => $anonymisedFile->getName(),
					'anonymisedAt' => date('c'),
					'operator' => $operator,
					'tool' => 'OpenAnonymiser via OpenRegister',
				],
				'entities' => $entities,
				'totals' => [
					'entityCount' => $totalOccurrences,
					'distinctEntityCount' => count($entities),
					'distinctBasesCount' => $this->countDistinctBases(entities: $entities),
				],
				// Distinct grondslagen assigned in this document, each with its
				// Woo Art. 5 toelichting — rendered as a legend under the table.
				'bases' => $this->collectAssignedBases(entities: $entities),
			],
			sourceFileId: $sourceFileId
		);

	}//end renderPerDocumentSummary()

	/**
	 * Count distinct base references across a set of shaped entity rows.
	 *
	 * Used by the per-doc template's footer total.
	 *
	 * @param array<int, array<string, mixed>> $entities Shaped entity rows.
	 *
	 * @return int Distinct base count.
	 */
	private function countDistinctBases(array $entities): int {
		$seen = [];
		foreach ($entities as $entity) {
			$bases = ($entity['bases'] ?? []);
			if (is_array($bases) === false) {
				continue;
			}

			foreach ($bases as $ref) {
				$seen[(string)$ref] = true;
			}
		}

		return count($seen);
	}//end countDistinctBases()
}//end class
