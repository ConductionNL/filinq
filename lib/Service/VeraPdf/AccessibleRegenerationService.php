<?php

/**
 * Filinq AccessibleRegenerationService
 *
 * Makes a PDF that Filinq generated from a template accessible: it renders the
 * same template version over the same data again through the tagged output
 * path, checks the new copy against PDF/UA-1, and stores it as a new version of
 * the file only when the copy is compliant. A copy that fails is discarded and
 * the original and its version stay as they were. A PDF Filinq did not
 * generate is refused: Filinq does not retag imported pages (decision D5).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\VeraPdf
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-document-filinq-generated-can-be-regenerated-as-pdfua-an-imported-one-cannot-req-ddpum-008
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\VeraPdf;

use OCA\Filinq\Exception\AccessibleRegenerationRefused;
use OCA\Filinq\Exception\VeraPdfException;
use OCA\Filinq\Kernel\Contracts\DocumentServiceInterface;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\ReadsStoredObjectRows;
use OCA\Filinq\Service\SlugSearch;
use OCP\Files\File;
use RuntimeException;
use Throwable;

/**
 * Regenerate, validate, and replace only on a pass.
 */
class AccessibleRegenerationService {
	use ReadsStoredObjectRows;

	/**
	 * The refusal reason for a PDF without generation provenance.
	 */
	public const NOT_GENERATED = 'not-generated-by-filinq';

	/**
	 * Where the generation audit entries live.
	 */
	private const REGISTER = 'filinq';

	private const SCHEMA = 'generatedDocument';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver   $objectResolver Reads the generation audit entries.
	 * @param DocumentServiceInterface        $documents      Renders the template again.
	 * @param AccessibilityConformanceService $conformance    The PDF/UA-1 check.
	 * @param PdfUaVerdictTagger              $tagger         Tags the verdict on the file.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly DocumentServiceInterface $documents,
		private readonly AccessibilityConformanceService $conformance,
		private readonly PdfUaVerdictTagger $tagger,
	) {

	}//end __construct()

	/**
	 * The generation a PDF came from: template, version and data, or null
	 * when Filinq did not generate it.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return array{templateId: string, templateVersion: int|null, dataRefs: array<int, mixed>}|null The provenance.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-document-filinq-generated-can-be-regenerated-as-pdfua-an-imported-one-cannot-req-ddpum-008
	 */
	public function provenanceOf(int $fileId): ?array {
		if ($fileId <= 0) {
			return null;
		}

		foreach ($this->generationRows(fileId: $fileId) as $row) {
			$provenance = $this->generatedPdfProvenance(entry: $this->storedFields(row: $row), fileId: $fileId);
			if ($provenance !== null) {
				return $provenance;
			}
		}

		return null;

	}//end provenanceOf()

	/**
	 * The stored generation entries the search returns for a file; none when the register cannot be read.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return array<int|string, mixed> The raw rows.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-document-filinq-generated-can-be-regenerated-as-pdfua-an-imported-one-cannot-req-ddpum-008
	 */
	private function generationRows(int $fileId): array {
		try {
			return (array) (new SlugSearch(objects: $this->objectResolver->resolve()))->search(
				register: self::REGISTER,
				schema: self::SCHEMA,
				filters: ['fileId' => $fileId]
			);
		} catch (Throwable) {
			return [];
		}

	}//end generationRows()

	/**
	 * The provenance in one stored generation entry, when it records a PDF generated for this file from a template.
	 *
	 * The search filters by file; the match is ours.
	 *
	 * @param array<string, mixed> $entry  The stored generation entry.
	 * @param int                  $fileId The file id.
	 *
	 * @return array{templateId: string, templateVersion: int|null, dataRefs: array<int, mixed>}|null The provenance, or null.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-document-filinq-generated-can-be-regenerated-as-pdfua-an-imported-one-cannot-req-ddpum-008
	 */
	private function generatedPdfProvenance(array $entry, int $fileId): ?array {
		if ($this->isGeneratedPdfOf(entry: $entry, fileId: $fileId) === false) {
			return null;
		}

		$version = null;
		if (is_numeric($entry['templateVersion'] ?? null) === true) {
			$version = (int) $entry['templateVersion'];
		}

		return [
			'templateId' => (string) $entry['templateId'],
			'templateVersion' => $version,
			'dataRefs' => array_values((array) ($entry['dataRefs'] ?? [])),
		];

	}//end generatedPdfProvenance()

	/**
	 * Whether a stored generation entry records a PDF generated for this file from a template.
	 *
	 * @param array<string, mixed> $entry  The stored generation entry.
	 * @param int                  $fileId The file id.
	 *
	 * @return bool True for a generated PDF of this file with a template.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-document-filinq-generated-can-be-regenerated-as-pdfua-an-imported-one-cannot-req-ddpum-008
	 */
	private function isGeneratedPdfOf(array $entry, int $fileId): bool {
		return (int) ($entry['fileId'] ?? 0) === $fileId
			&& ($entry['status'] ?? '') === 'generated'
			&& ($entry['format'] ?? '') === 'pdf'
			&& trim((string) ($entry['templateId'] ?? '')) !== '';

	}//end isGeneratedPdfOf()

	/**
	 * Regenerate a generated PDF as PDF/UA and keep the copy only when it passes.
	 *
	 * @param File   $file   The PDF.
	 * @param string $userId Who asked, recorded on the generation.
	 *
	 * @return array{replaced: bool, report: array<string, mixed>} Whether a new version was stored, and the copy's verdict.
	 *
	 * @throws AccessibleRegenerationRefused When Filinq did not generate the PDF.
	 * @throws VeraPdfException When veraPDF gives no verdict on the copy; nothing is changed then.
	 * @throws RuntimeException When the copy cannot be rendered or stored.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-document-filinq-generated-can-be-regenerated-as-pdfua-an-imported-one-cannot-req-ddpum-008
	 */
	public function regenerate(File $file, string $userId): array {
		$fileId = (int) $file->getId();
		$provenance = $this->provenanceOf(fileId: $fileId);
		if ($provenance === null) {
			throw new AccessibleRegenerationRefused(reason: self::NOT_GENERATED);
		}

		$copy = $this->render(provenance: $provenance, userId: $userId);
		$report = $this->conformance->checkBytes(bytes: $copy, fileId: $fileId, trigger: AccessibilityConformanceService::TRIGGER_GENERATION);
		if ($report['compliant'] !== true) {
			// The copy is discarded; the original, its version and its report stay.
			return ['replaced' => false, 'report' => $report];
		}

		try {
			$file->putContent($copy);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The accessible copy could not be stored: ' . $e->getMessage(), code: 0, previous: $e);
		}

		$stored = $this->conformance->store(report: $report);
		$this->tagger->tag(fileId: $fileId, verdict: PdfUaVerdictTagger::CONFORM);

		return ['replaced' => true, 'report' => $stored];

	}//end regenerate()

	/**
	 * Render the generation again through the tagged output path.
	 *
	 * @param array{templateId: string, templateVersion: int|null, dataRefs: array<int, mixed>} $provenance The generation.
	 * @param string                                                                            $userId     Who asked.
	 *
	 * @return string The PDF bytes.
	 *
	 * @throws RuntimeException When rendering fails or gives no PDF.
	 */
	private function render(array $provenance, string $userId): string {
		$options = [
			'format' => 'pdf',
			'userId' => $userId,
			'pdfOptions' => ['accessible' => true],
		];
		if ($provenance['templateVersion'] !== null) {
			$options['templateVersion'] = $provenance['templateVersion'];
		}

		try {
			$result = $this->documents->generateDocument(
				templateId: $provenance['templateId'],
				dataRefs: $provenance['dataRefs'],
				options: $options
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The document could not be generated again: ' . $e->getMessage(), code: 0, previous: $e);
		}

		$content = (string) ($result['content'] ?? '');
		if (str_starts_with($content, '%PDF') === false) {
			throw new RuntimeException(message: 'The document could not be generated again as a PDF.');
		}

		return $content;

	}//end render()
}//end class
