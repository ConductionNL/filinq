<?php

/**
 * Contract of LegalBasesSummaryService
 *
 * The public surface of the LegalBasesSummaryService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use OCP\Files\File;
use RuntimeException;

/**
 * Contract of LegalBasesSummaryService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface LegalBasesSummaryServiceInterface {
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
	public function appendSummaryToPdf(File $anonymisedFile, int $sourceFileId, array $placeholderMap = []): File;

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
	public function renderSummaryBesideFile(File $anonymisedFile, int $sourceFileId, array $placeholderMap = []): File;

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
	public function authorizeAccess(string $dossierId): void;

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
	public function renderDossierSummary(string $dossierUuid): File;
}//end interface
