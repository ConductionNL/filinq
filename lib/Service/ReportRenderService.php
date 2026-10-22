<?php

/**
 * Report Render Service
 *
 * Implements the external, slug-addressed render contract
 * (`POST /api/v1/documents/render` and `.../render/batch`) that other
 * Conduction apps call to turn ad-hoc data into a stored, school-styled
 * PDF, without needing to know filinq's internal template UUIDs,
 * registers, or schemas. Composes the existing `TemplateSlugResolver` (slug
 * resolution), `DocumentRenderPipeline` (huisstijl + Twig render),
 * `Pdfa3ConversionService` (print-quality output), and
 * `DocumentStorageService` (Files storage) — no new rendering engine.
 *
 * learniq's `ReportCardPdfDelegationService` already POSTs this exact
 * contract (`templateSlug`, ad-hoc data, expects `{documentRef}` back);
 * this service is the filinq-side implementation of what it calls a
 * "proposed, not-yet-verified" endpoint.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Throwable;
use ZipArchive;

/**
 * Resolves a template by slug, renders ad-hoc data through the existing
 * huisstijl/PDF pipeline, and stores the result (single document or a
 * ZIP of a batch).
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md
 */
class ReportRenderService {

	/**
	 * The namespace external render requests resolve templates under.
	 *
	 * @var string
	 */
	public const NAMESPACE = 'learniq';

	/**
	 * App-config key for the fallback Files-storage owner used when a
	 * request omits `userId`.
	 *
	 * @var string
	 */
	private const CFG_STORAGE_USER = 'report_render_storage_user';

	/**
	 * Base folder (within the storage user's Files) that rendered reports
	 * and batch ZIPs are stored under.
	 *
	 * @var string
	 */
	private const STORAGE_FOLDER = 'DocuDesk/Rendered Reports';

	/**
	 * Constructor for ReportRenderService.
	 *
	 * @param TemplateSlugResolver $slugResolver Slug resolution.
	 * @param DocumentRenderPipeline $renderPipeline Huisstijl + Twig rendering and screen-quality output.
	 * @param Pdfa3ConversionService $pdfa3Service Print-quality (PDF/A-3) conversion.
	 * @param DocumentStorageService $storageService Files storage for the rendered output.
	 * @param IAppConfig $appConfig App-config reader for the storage-user fallback.
	 * @param LoggerInterface $logger Logger for per-item batch failures.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly TemplateSlugResolver $slugResolver,
		private readonly DocumentRenderPipeline $renderPipeline,
		private readonly Pdfa3ConversionService $pdfa3Service,
		private readonly DocumentStorageService $storageService,
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Render one document from ad-hoc data against a slug-resolved template.
	 *
	 * @param string $templateSlug The template's stable slug.
	 * @param string|null $tenantId Optional tenant (e.g. school) to prefer.
	 * @param array<string,mixed> $data Ad-hoc Twig data context.
	 * @param array<string,mixed> $options {huisstijlId?, outputQuality?
	 *                                     (screen|print), userId?}.
	 *
	 * @return array{documentRef: string, templateSlug: string, renderedAt: string}
	 *
	 * @throws Exception Code 404 if the slug cannot be resolved, 400 for a
	 *                   missing storage user, or whatever the render/store
	 *                   layers raise.
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#requirement-single-document-render-by-template-slug-req-rra-01
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#requirement-print-quality-output-option-req-rra-02
	 */
	public function render(string $templateSlug, ?string $tenantId, array $data, array $options = []): array {
		$userId = $this->resolveStorageUser(options: $options);
		$template = $this->slugResolver->resolve(
			namespace: self::NAMESPACE,
			slug: $templateSlug,
			tenantId: $tenantId
		);

		$pdfBytes = $this->renderOne(template: $template, data: $data, options: $options);

		$filename = $this->buildFilename(templateSlug: $templateSlug, extension: 'pdf');
		$stored = $this->storageService->store(
			userId: $userId,
			targetPath: self::STORAGE_FOLDER,
			filename: $filename,
			content: $pdfBytes
		);

		return [
			'documentRef' => (string)$stored['fileId'],
			'templateSlug' => $templateSlug,
			'renderedAt' => date('c'),
		];
	}//end render()

	/**
	 * Render a list of ad-hoc payloads against one shared template slug and
	 * package the results into a single ZIP archive.
	 *
	 * @param string $templateSlug The template's stable slug.
	 * @param string|null $tenantId Optional tenant (e.g. school) to prefer.
	 * @param array<int,array{data?: array<string,mixed>}> $items One entry per document to render.
	 * @param array<string,mixed> $options {huisstijlId?, outputQuality?, userId?}.
	 *
	 * @return array{documentRef: string, count: int, failures: array<int,array{index:int,error:string}>}
	 *
	 * @throws Exception Code 404 if the slug cannot be resolved, 400 for
	 *                   empty items or a missing storage user.
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#requirement-batch-render-and-zip-req-rra-03
	 */
	public function renderBatch(string $templateSlug, ?string $tenantId, array $items, array $options = []): array {
		if (empty($items) === true) {
			throw new Exception(message: 'items is required and must be a non-empty array', code: 400);
		}

		$userId = $this->resolveStorageUser(options: $options);
		$template = $this->slugResolver->resolve(
			namespace: self::NAMESPACE,
			slug: $templateSlug,
			tenantId: $tenantId
		);

		$tempZipPath = sys_get_temp_dir() . '/' . uniqid('filinq_report_batch_', true) . '.zip';
		$zip = new ZipArchive();
		if ($zip->open($tempZipPath, ZipArchive::CREATE) !== true) {
			throw new Exception(message: 'Could not create batch ZIP archive', code: 500);
		}

		$failures = [];
		$rendered = 0;

		try {
			foreach ($items as $index => $item) {
				$itemData = (array)($item['data'] ?? []);
				try {
					$pdfBytes = $this->renderOne(template: $template, data: $itemData, options: $options);
					$entryName = $this->buildFilename(
						templateSlug: $templateSlug,
						extension: 'pdf',
						suffix: (string)($index + 1)
					);
					$zip->addFromString($entryName, $pdfBytes);
					$rendered++;
				} catch (Throwable $e) {
					$this->logger->warning(
						message: '[ReportRenderService] Batch item {index} failed to render: {msg}',
						context: ['index' => $index, 'msg' => $e->getMessage()]
					);
					$failures[] = ['index' => $index, 'error' => $e->getMessage()];
				}//end try
			}//end foreach
		} finally {
			$zip->close();
		}

		$zipContent = file_get_contents($tempZipPath);
		unlink($tempZipPath);

		if ($zipContent === false) {
			throw new Exception(message: 'Could not read the assembled batch ZIP archive', code: 500);
		}

		$filename = $this->buildFilename(templateSlug: $templateSlug, extension: 'zip');
		$stored = $this->storageService->store(
			userId: $userId,
			targetPath: self::STORAGE_FOLDER,
			filename: $filename,
			content: $zipContent
		);

		return [
			'documentRef' => (string)$stored['fileId'],
			'count' => $rendered,
			'failures' => $failures,
		];
	}//end renderBatch()

	/**
	 * Render one template + data pair to PDF bytes, honouring `outputQuality`.
	 *
	 * @param array<string,mixed> $template The resolved template object.
	 * @param array<string,mixed> $data Twig data context.
	 * @param array<string,mixed> $options {huisstijlId?, outputQuality?}.
	 *
	 * @return string PDF binary content (PDF/A-3 when outputQuality is "print").
	 *
	 * @throws Exception If rendering fails.
	 */
	private function renderOne(array $template, array $data, array $options): string {
		$huisstijl = $this->renderPipeline->loadHuisstijl(huisstijlId: ($options['huisstijlId'] ?? null));
		$renderResult = $this->renderPipeline->renderWithHuisstijl(
			templateContent: (string)($template['content'] ?? ''),
			data: $data,
			huisstijl: $huisstijl
		);

		$outputQuality = (string)($options['outputQuality'] ?? 'screen');

		if ($outputQuality === 'print') {
			$converted = $this->pdfa3Service->convertHtml(
				html: $renderResult['html'],
				metadata: ['title' => (string)($template['name'] ?? 'Report')],
				attachments: [],
				options: [
					'format' => $template['format'] ?? 'A4',
					'orientation' => $template['orientation'] ?? 'P',
				]
			);

			return $converted['content'];
		}

		$pdfOptions = $this->renderPipeline->buildPdfOptions(
			template: $template,
			huisstijl: $huisstijl,
			options: $options
		);

		return $this->renderPipeline->produceOutput(
			htmlContent: $renderResult['html'],
			format: 'pdf',
			pdfOptions: $pdfOptions
		);
	}//end renderOne()

	/**
	 * Resolve which Nextcloud user's Files area stores the rendered output.
	 *
	 * @param array<string,mixed> $options {userId?}.
	 *
	 * @return string The resolved user id.
	 *
	 * @throws Exception Code 400 when neither the request nor app config names one.
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#scenario-no-storage-user-available-is-rejected
	 */
	private function resolveStorageUser(array $options): string {
		$userId = (string)($options['userId'] ?? '');
		if ($userId !== '') {
			return $userId;
		}

		$default = $this->appConfig->getValueString(
			app: 'filinq',
			key: self::CFG_STORAGE_USER,
			default: ''
		);

		if ($default !== '') {
			return $default;
		}

		throw new Exception(
			message: 'userId is required (no filinq.report_render_storage_user default is configured)',
			code: 400
		);
	}//end resolveStorageUser()

	/**
	 * Build a collision-resistant filename for a rendered document.
	 *
	 * @param string $templateSlug The template slug (used as the basename).
	 * @param string $extension File extension without the leading dot.
	 * @param string|null $suffix Optional extra suffix (e.g. a batch item index).
	 *
	 * @return string The filename.
	 */
	private function buildFilename(string $templateSlug, string $extension, ?string $suffix = null): string {
		$safeSlug = preg_replace('/[^A-Za-z0-9_-]+/', '-', $templateSlug) ?? 'report';
		$parts = [$safeSlug];
		if ($suffix !== null) {
			$parts[] = $suffix;
		}

		$parts[] = uniqid();

		return implode('-', $parts) . '.' . $extension;
	}//end buildFilename()
}//end class
