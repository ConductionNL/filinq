<?php

/**
 * Grondslagen PDF Writer
 *
 * The write side of the grondslagen report: loads the bundled Twig templates,
 * renders them to PDF/A-3b through {@see PdfService}, merges a summary page
 * into an already-anonymised PDF with FPDI, and persists the result to
 * Nextcloud Files.
 *
 * Extracted from {@see LegalBasesSummaryService} so that the summary service
 * decides WHAT to report while this class owns HOW it reaches disk.
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
use RuntimeException;
use setasign\Fpdi\Fpdi;

/**
 * Renders and persists the grondslagen summary PDFs.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class GrondslagenPdfWriter {

	/**
	 * Relative path (from the app root) where the Twig templates live.
	 */
	private const TEMPLATE_DIR = '/Resources/templates/grondslagen/';

	/**
	 * Template file for the per-document summary page.
	 */
	private const TEMPLATE_PER_DOC = 'summary_per_doc.twig';

	/**
	 * Template file for the per-dossier summary PDF.
	 */
	private const TEMPLATE_PER_DOSSIER = 'summary_per_dossier.twig';

	/**
	 * File name of the per-dossier summary inside the dossier folder.
	 */
	private const DOSSIER_SUMMARY_NAME = 'grondslagen.pdf';

	/**
	 * Constructor.
	 *
	 * @param PdfService $pdfService Twig + mPDF renderer.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly PdfService $pdfService,
	) {

	}//end __construct()

	/**
	 * Render the per-document summary template into PDF bytes.
	 *
	 * @param array<string, mixed> $data The Twig context.
	 * @param int $sourceFileId The pre-anonymisation source file id (error context).
	 *
	 * @return string The rendered PDF (PDF/A-3b) as raw bytes.
	 *
	 * @throws RuntimeException When template or PDF rendering fails.
	 */
	public function renderPerDocumentPdf(array $data, int $sourceFileId): string {
		try {
			return $this->pdfService->renderPdf(
				templateContent: $this->loadTemplate(name: self::TEMPLATE_PER_DOC),
				data: $data,
				options: ['pdfa' => true, 'title' => 'Anonimisatie-samenvatting']
			);
		} catch (Exception $e) {
			throw new RuntimeException(
				'Grondslagen summary: per-doc render failed for fileId ' . $sourceFileId . ': ' . $e->getMessage(),
				previous: $e
			);
		}

	}//end renderPerDocumentPdf()

	/**
	 * Render the per-dossier summary template into PDF bytes.
	 *
	 * @param array<string, mixed> $data The Twig context.
	 * @param string $dossierUuid The dossier UUID (error context).
	 *
	 * @return string The rendered PDF (PDF/A-3b) as raw bytes.
	 *
	 * @throws RuntimeException When template or PDF rendering fails.
	 */
	public function renderDossierPdf(array $data, string $dossierUuid): string {
		try {
			return $this->pdfService->renderPdf(
				templateContent: $this->loadTemplate(name: self::TEMPLATE_PER_DOSSIER),
				data: $data,
				options: ['pdfa' => true, 'title' => 'Grondslagen-rapportage']
			);
		} catch (Exception $e) {
			throw new RuntimeException(
				'Grondslagen summary: per-dossier render failed for ' . $dossierUuid . ': ' . $e->getMessage(),
				previous: $e
			);
		}

	}//end renderDossierPdf()

	/**
	 * Append the summary PDF to an anonymised PDF file, in place.
	 *
	 * The append is atomic — on any PDF-merge or write failure the anonymised
	 * file is left untouched and the caller receives the thrown exception.
	 *
	 * @param File $anonymisedFile The anonymised PDF file.
	 * @param string $summaryBytes The rendered summary PDF bytes.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When FPDI merging or the file write fails.
	 */
	public function appendToPdf(File $anonymisedFile, string $summaryBytes): void {
		$combinedBytes = $this->mergeSummaryIntoPdf(
			originalPdfBytes: (string)$anonymisedFile->getContent(),
			summaryPdfBytes: $summaryBytes
		);

		try {
			$anonymisedFile->putContent($combinedBytes);
		} catch (Exception $e) {
			throw new RuntimeException(
				'Grondslagen summary: failed to write combined PDF to ' . $anonymisedFile->getPath() . ': ' . $e->getMessage(),
				previous: $e
			);
		}

	}//end appendToPdf()

	/**
	 * Write the summary PDF beside the anonymised file.
	 *
	 * Refreshes the existing file when one is already present, so repeated
	 * runs do not litter the folder.
	 *
	 * @param File $anonymisedFile The anonymised file (any format).
	 * @param string $summaryFileName The summary file's name.
	 * @param string $summaryBytes The rendered summary PDF bytes.
	 *
	 * @return array{file: File, refreshed: bool} The written file and whether it already existed.
	 *
	 * @throws RuntimeException When the write fails.
	 */
	public function writeBesideFile(File $anonymisedFile, string $summaryFileName, string $summaryBytes): array {
		$parent = $anonymisedFile->getParent();

		try {
			if ($parent->nodeExists($summaryFileName) === true) {
				$existing = $parent->get($summaryFileName);
				if ($existing instanceof File) {
					$existing->putContent($summaryBytes);
					return ['file' => $existing, 'refreshed' => true];
				}
			}

			$newFile = $parent->newFile(path: $summaryFileName, content: $summaryBytes);
		} catch (Exception $e) {
			throw new RuntimeException(
				'Grondslagen summary write failed: ' . $summaryFileName . ' — ' . $e->getMessage(),
				previous: $e
			);
		}

		return ['file' => $newFile, 'refreshed' => false];
	}//end writeBesideFile()

	/**
	 * Save the rendered per-dossier summary PDF.
	 *
	 * Destination convention: `<dossier-folder>/grondslagen.pdf`. Wave 2
	 * (`anonymisation-output-folder-layout`) will introduce a
	 * `<dossier-folder>/anonymised/` subfolder; this method will follow that
	 * convention once the helper from Wave 2 lands. For v1, we use the flat
	 * path inside the dossier folder.
	 *
	 * @param Folder $folder The dossier folder.
	 * @param string $pdfBytes The freshly-rendered PDF bytes.
	 *
	 * @return File The newly-written / refreshed summary file.
	 *
	 * @throws RuntimeException On write failure.
	 */
	public function saveDossierSummary(Folder $folder, string $pdfBytes): File {
		$name = self::DOSSIER_SUMMARY_NAME;

		try {
			if ($folder->nodeExists($name) === true) {
				$existing = $folder->get($name);
				if ($existing instanceof File) {
					$existing->putContent($pdfBytes);
					return $existing;
				}
			}

			$newFile = $folder->newFile(path: $name, content: $pdfBytes);
		} catch (Exception $e) {
			throw new RuntimeException(
				'Grondslagen summary: failed to write ' . $name . ' to dossier folder: ' . $e->getMessage(),
				previous: $e
			);
		}

		return $newFile;
	}//end saveDossierSummary()

	/**
	 * Merge an anonymised PDF + the freshly-rendered summary PDF into one PDF.
	 *
	 * Uses FPDI to import every page of both inputs and emit them as a single
	 * combined PDF. The result is **not strictly PDF/A** (FPDI doesn't enforce
	 * that on import — the upstream PDF's compliance isn't guaranteed); the
	 * per-dossier render path uses pure mPDF and IS PDF/A-3b. This trade-off
	 * is documented in design.md.
	 *
	 * @param string $originalPdfBytes Anonymised PDF bytes.
	 * @param string $summaryPdfBytes Summary PDF bytes.
	 *
	 * @return string Combined PDF bytes.
	 *
	 * @throws RuntimeException When FPDI import or output fails.
	 *
	 * @psalm-suppress UndefinedMethod FPDI extends FPDF; Output() is inherited from FPDF
	 *                                 and Psalm lacks stubs for it.
	 */
	private function mergeSummaryIntoPdf(string $originalPdfBytes, string $summaryPdfBytes): string {
		$originalTemp = tempnam(sys_get_temp_dir(), 'grondslagen-orig-');
		$summaryTemp = tempnam(sys_get_temp_dir(), 'grondslagen-summary-');

		if ($originalTemp === false || $summaryTemp === false) {
			throw new RuntimeException('Grondslagen summary: could not allocate temp files for FPDI merge');
		}

		try {
			file_put_contents($originalTemp, $originalPdfBytes);
			file_put_contents($summaryTemp, $summaryPdfBytes);

			$pdf = new Fpdi();
			$pdf->setSourceFile($originalTemp);
			$this->importAllPages(pdf: $pdf, pageCount: $pdf->setSourceFile($originalTemp));
			$this->importAllPages(pdf: $pdf, pageCount: $pdf->setSourceFile($summaryTemp));

			// FPDI inherits Output() from FPDF. Calling 'S' returns the PDF bytes.
			// @phpstan-ignore-next-line method.notFound (FPDF stubs are not loaded for static analysis).
			return (string)$pdf->Output('S');
		} catch (Exception $e) {
			throw new RuntimeException(
				'Grondslagen summary: FPDI merge failed: ' . $e->getMessage(),
				previous: $e
			);
		} finally {
			if (file_exists($originalTemp) === true) {
				unlink($originalTemp);
			}

			if (file_exists($summaryTemp) === true) {
				unlink($summaryTemp);
			}
		}//end try

	}//end mergeSummaryIntoPdf()

	/**
	 * Import every page of the currently-selected FPDI source file.
	 *
	 * @param Fpdi $pdf The FPDI document under construction.
	 * @param int $pageCount Number of pages in the selected source file.
	 *
	 * @return void
	 */
	private function importAllPages(Fpdi $pdf, int $pageCount): void {
		for ($i = 1; $i <= $pageCount; $i++) {
			$tplId = $pdf->importPage($i);
			$size = $pdf->getTemplateSize($tplId);
			$pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
			$pdf->useTemplate($tplId);
		}

	}//end importAllPages()

	/**
	 * Load a Twig template's source from disk.
	 *
	 * The templates live under `lib/Resources/templates/grondslagen/`. This
	 * helper reads the file as a string so it can be passed to
	 * `PdfService::renderPdf($templateContent, ...)`. Throws if the file is
	 * missing — every release MUST ship both templates.
	 *
	 * @param string $name The template file name (e.g. `summary_per_doc.twig`).
	 *
	 * @return string The template's UTF-8 source.
	 *
	 * @throws RuntimeException When the template file is missing or unreadable.
	 */
	private function loadTemplate(string $name): string {
		$path = __DIR__ . '/..' . self::TEMPLATE_DIR . $name;
		$resolved = realpath($path);
		if ($resolved === false || is_readable($resolved) === false) {
			throw new RuntimeException(
				sprintf('Grondslagen summary template not found or unreadable: %s', $path)
			);
		}

		$contents = file_get_contents($resolved);
		if ($contents === false) {
			throw new RuntimeException(
				sprintf('Grondslagen summary template read failed: %s', $resolved)
			);
		}

		return $contents;
	}//end loadTemplate()
}//end class
