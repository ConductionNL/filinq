<?php

/**
 * Dossier Summary Data Service
 *
 * The read side of the grondslagen report: everything the renderer needs to
 * *know* — the dossier's context and folder, the placeholder ranking, the
 * anonymised entity rows, and the resolved grondslag labels — behind one seam.
 *
 * Extracted from {@see LegalBasesSummaryService} together with the four
 * collaborators it composes ({@see DossierObjectRepository},
 * {@see BaseLabelResolver}, {@see DossierPlaceholderRanker},
 * {@see DossierEntityCollector}), leaving the summary service to do only what
 * its name says: render and write the report.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCP\App\IAppManager;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Supplies every piece of dossier data the grondslagen report renders.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class DossierSummaryDataService {

	/**
	 * OpenRegister object access.
	 *
	 * @var DossierObjectRepository
	 */
	private readonly DossierObjectRepository $repository;

	/**
	 * Grondslag label resolution.
	 *
	 * @var BaseLabelResolver
	 */
	private readonly BaseLabelResolver $labelResolver;

	/**
	 * Dossier placeholder ranking and sort keys.
	 *
	 * @var DossierPlaceholderRanker
	 */
	private readonly DossierPlaceholderRanker $ranker;

	/**
	 * Anonymised entity collection and shaping.
	 *
	 * @var DossierEntityCollector
	 */
	private readonly DossierEntityCollector $collector;

	/**
	 * Constructor.
	 *
	 * The four collaborators are injected; the null defaults wire an
	 * equivalent chain from the raw dependencies so Nextcloud's autowiring and
	 * tests can both construct this service without naming the chain.
	 *
	 * @param IRootFolder $rootFolder Nextcloud file API entry point.
	 * @param IUserSession $userSession Session-user lookup.
	 * @param IAppManager $appManager App-availability check for OpenRegister.
	 * @param ContainerInterface $container DI container for OpenRegister-side services.
	 * @param LoggerInterface $logger Structured logger.
	 * @param DossierObjectRepository|null $repository OpenRegister object access.
	 * @param BaseLabelResolver|null $labelResolver Grondslag label resolution.
	 * @param DossierPlaceholderRanker|null $ranker Dossier placeholder ranking.
	 * @param DossierEntityCollector|null $collector Anonymised entity collection.
	 *
	 * @return void
	 */
	public function __construct(
		IRootFolder $rootFolder,
		IUserSession $userSession,
		IAppManager $appManager,
		ContainerInterface $container,
		LoggerInterface $logger,
		?DossierObjectRepository $repository = null,
		?BaseLabelResolver $labelResolver = null,
		?DossierPlaceholderRanker $ranker = null,
		?DossierEntityCollector $collector = null,
	) {
		$this->repository = ($repository ?? new DossierObjectRepository(
			$appManager,
			$container,
			$rootFolder,
			$userSession,
			$logger
		));

		$this->labelResolver = ($labelResolver ?? new BaseLabelResolver($this->repository, $logger));
		$this->ranker = ($ranker ?? new DossierPlaceholderRanker($this->repository, $logger));
		$this->collector = ($collector ?? new DossierEntityCollector(
			$this->repository,
			$this->labelResolver,
			$this->ranker,
			$logger
		));

	}//end __construct()

	/**
	 * Load a dossier's context together with its resolved folder node.
	 *
	 * @param string $dossierUuid The OR object UUID.
	 *
	 * @return array{context: array<string, mixed>, folder: Folder} Dossier context and folder.
	 *
	 * @throws RuntimeException When the dossier or its folder cannot be resolved.
	 */
	public function loadDossier(string $dossierUuid): array {
		$context = $this->repository->loadDossierContext(dossierUuid: $dossierUuid);

		return [
			'context' => $context,
			'folder' => $this->repository->resolveDossierFolder(folderRef: ($context['folderRef'] ?? null)),
		];

	}//end loadDossier()

	/**
	 * Assert that the dossier EXISTS and is resolvable to the acting user.
	 *
	 * ⚠️ This is an EXISTENCE check, not an ownership check. The previous
	 * docblock claimed the opposite — "OR's standard RBAC governs visibility:
	 * a dossier the caller may not read resolves to null and we deny by
	 * throwing" — and that claim is false as the app is configured.
	 *
	 * The call below genuinely omits `_rbac: false`, so OpenRegister's RBAC
	 * cascade is consulted. But the cascade resolves to "configured nowhere",
	 * which OpenRegister treats as OPEN: the `dossier` schema in
	 * `lib/Settings/docudesk_register.json` declares `"authorization": null`,
	 * and no register declares the key at all. `find()` therefore returns the
	 * object for any authenticated caller in the same organisation, so the
	 * `null` branch below can only ever fire for a dossier that does not
	 * exist — never for one the caller merely has no business reading.
	 *
	 * What IS still enforced: organisation scoping (multitenancy is not
	 * bypassed here), and the existence check itself.
	 *
	 * Do not read a green result from this method as "the caller owns this
	 * dossier". Closing that gap needs an agreed ownership model for dossiers
	 * — a dossier is a shared work object and it is NOT obvious that only its
	 * creator may regenerate its summary — so it is deliberately not invented
	 * here. Tracked in ConductionNL/docudesk#441.
	 *
	 * @param string $dossierId The OR dossier object UUID.
	 *
	 * @return void
	 *
	 * @throws RuntimeException 403 when the dossier cannot be resolved at all.
	 */
	public function assertDossierReadable(string $dossierId): void {
		$objectService = $this->repository->objectService();
		if ($objectService === null) {
			throw new RuntimeException('Access denied: OpenRegister ObjectService unavailable.', 403);
		}

		$dossier = $objectService->find(
			id: $dossierId,
			register: 'dossier',
			schema: 'dossier'
		);

		if ($dossier === null) {
			throw new RuntimeException('Access denied or dossier not found: ' . $dossierId, 403);
		}

	}//end assertDossierReadable()

	/**
	 * Recompute the dossier's scope-local placeholder ranking.
	 *
	 * @param Folder $folder The dossier folder.
	 *
	 * @return array{ranks: array<array-key, int>, types: array<string, string>} Ranks and entity types.
	 */
	public function placeholderRanking(Folder $folder): array {
		return $this->ranker->rank(folder: $folder);
	}//end placeholderRanking()

	/**
	 * Sort key for a `[<TYPE>: <number>]` placeholder.
	 *
	 * @param string $placeholder The placeholder string.
	 *
	 * @return array{0: string, 1: int} The [type, number] sort key.
	 */
	public function placeholderSortKey(string $placeholder): array {
		return $this->ranker->sortKey(placeholder: $placeholder);
	}//end placeholderSortKey()

	/**
	 * Walk every file under the dossier folder and collect its anonymised entities.
	 *
	 * @param Folder $folder The dossier folder.
	 * @param array<string, string> $placeholderMap Dossier scope-local placeholder map.
	 *
	 * @return array<int, array{fileId: int, filename: string, entities: array<int, array<string, mixed>>}> Per-file rows.
	 */
	public function walkDossierFiles(Folder $folder, array $placeholderMap = []): array {
		return $this->collector->walkDossierFiles(folder: $folder, placeholderMap: $placeholderMap);
	}//end walkDossierFiles()

	/**
	 * Load the shaped anonymised entity rows of one file.
	 *
	 * @param int $fileId The Nextcloud file ID.
	 * @param array<string, string> $placeholderMap Optional scope-local placeholder map.
	 *
	 * @return array<int, array<string, mixed>> Shaped entity rows.
	 */
	public function loadAnonymisedEntitiesForFile(int $fileId, array $placeholderMap = []): array {
		return $this->collector->loadAnonymisedEntitiesForFile(fileId: $fileId, placeholderMap: $placeholderMap);
	}//end loadAnonymisedEntitiesForFile()

	/**
	 * Resolve a list of `base` references to human-readable labels.
	 *
	 * @param array<int, string> $baseRefs Slugs or UUIDs of base records.
	 *
	 * @return array<string, array{name: string, description: string}> The label map.
	 */
	public function resolveBaseLabels(array $baseRefs): array {
		return $this->labelResolver->resolve(baseRefs: $baseRefs);
	}//end resolveBaseLabels()

	/**
	 * Group resolved base labels by display name for the report legend.
	 *
	 * @param array<string, array{name: string, description: string}> $detail Resolved labels.
	 *
	 * @return array<int, array{name: string, description: string}> Distinct bases, sorted by name.
	 */
	public function groupBasesByName(array $detail): array {
		return $this->labelResolver->groupByName(detail: $detail);
	}//end groupBasesByName()

	/**
	 * Update the dossier object's `configuration.grondslagen` freshness metadata.
	 *
	 * @param string $dossierUuid The OR dossier object UUID.
	 * @param int $summaryFileId The newly-written summary file's NC node id.
	 *
	 * @return void
	 */
	public function updateDossierConfiguration(string $dossierUuid, int $summaryFileId): void {
		$this->repository->updateDossierConfiguration(
			dossierUuid: $dossierUuid,
			summaryFileId: $summaryFileId
		);

	}//end updateDossierConfiguration()
}//end class
