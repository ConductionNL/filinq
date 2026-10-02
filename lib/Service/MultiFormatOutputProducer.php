<?php

/**
 * Multi-format output
 *
 * One rendered document, several files: the PDF for the citizen and the
 * editable DOCX for the neighbouring municipality come from the same HTML,
 * so they cannot disagree about the data. Each format is converted and filed
 * on its own; one that fails is reported, the others are still made, and one
 * generatedDocument entry lists them all.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateRenderer;
use Exception;
use OCP\IURLGenerator;
use Throwable;

/**
 * Renders once, then makes, files and records every requested format.
 *
 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.4
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class MultiFormatOutputProducer {
	/**
	 * The formats generation can make.
	 *
	 * @var string[]
	 */
	public const FORMATS = ['pdf', 'odf', 'docx', 'html'];

	/**
	 * File extension per output format.
	 *
	 * @var array<string, string>
	 */
	private const EXTENSIONS = [
		'pdf' => '.pdf',
		'odf' => '.odt',
		'docx' => '.docx',
		'html' => '.html',
	];

	/**
	 * Constructor.
	 *
	 * @param DocumentService                    $documents      Renders the template (its preview path: the same render, no audit entry).
	 * @param TemplateService                    $templates      Reads the template.
	 * @param DocumentRenderPipeline             $renderPipeline Converts the rendered HTML per format.
	 * @param DocumentStorageService             $storage        Files each output in the user's Files.
	 * @param GeneratedDocumentLogger            $auditLog       Writes the one generatedDocument entry.
	 * @param PlainLanguageRenditionService|null $plainRendition Tells whether the template has a plain-language counterpart.
	 * @param IURLGenerator|null                 $urls           Makes the download URLs absolute.
	 * @param OfficeTemplateRenderer|null        $officeRenderer Fills an office template once per format.
	 */
	public function __construct(
		private readonly DocumentService $documents,
		private readonly TemplateService $templates,
		private readonly DocumentRenderPipeline $renderPipeline,
		private readonly DocumentStorageService $storage,
		private readonly GeneratedDocumentLogger $auditLog,
		private readonly ?PlainLanguageRenditionService $plainRendition = null,
		private readonly ?IURLGenerator $urls = null,
		private readonly ?OfficeTemplateRenderer $officeRenderer = null,
	) {

	}//end __construct()

	/**
	 * Generate one document in several formats.
	 *
	 * @param string $templateId The template.
	 * @param array  $dataRefs   Data references: [{register, schema, id}, ...].
	 * @param array  $options    `formats` (required), `userId` (required), `filename`,
	 *                           `output.targetPath`, and what a single generation takes
	 *                           for the render (huisstijlId, adHocData, listRefs, pdfOptions, caseId).
	 *
	 * @return array{outputs: array<int, array<string, mixed>>, metadata: array, warnings: string[]}
	 *               One output per format: format, status (generated|failed), fileId,
	 *               fileName, downloadUrl, size, and error when it failed.
	 *
	 * @throws Exception 400 for a malformed request or a template with a plain-language
	 *                   counterpart; a failing render aborts the whole job.
	 *
	 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.4
	 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.6
	 */
	public function generate(string $templateId, array $dataRefs, array $options): array {
		$formats = $this->requestedFormats(options: $options);
		$userId = (string) ($options['userId'] ?? '');
		if ($userId === '') {
			throw new Exception(message: 'options.userId is required to store generated documents in Files', code: 400);
		}

		if (isset($options['wizardContext']) === true) {
			// A wizard run is checked and recorded by DocumentService::generateDocument(); this path would skip both.
			throw new Exception(message: 'options.wizardContext cannot be combined with options.formats yet; use options.format', code: 400);
		}

		if (isset($options['templateVersion']) === true) {
			// The formats share one head render; a pin it ignored would claim a version it did not render.
			throw new Exception(message: 'options.templateVersion cannot be combined with options.formats yet; use options.format', code: 400);
		}

		$template = $this->templates->getTemplate(id: $templateId);
		if ($this->plainRendition?->counterpartOf(template: $template) !== null) {
			// A formal letter and its plain counterpart are filed together or not at all.
			throw new Exception(message: 'options.formats cannot be used for a template with a plain-language counterpart yet; use options.format', code: 400);
		}

		$render = $this->converterFor(template: $template, templateId: $templateId, dataRefs: $dataRefs, options: $options);
		$basename = pathinfo((string) ($options['filename'] ?? 'document'), PATHINFO_FILENAME);
		if ($basename === '') {
			$basename = 'document';
		}

		$produced = $this->produce(
			convert: $render['convert'],
			formats: $formats,
			userId: $userId,
			targetPath: $this->documents->buildOutputTargetPath(
				templateId: $templateId,
				explicitTargetPath: ($options['output']['targetPath'] ?? null),
				template: $template
			),
			basename: $basename
		);
		$warnings = array_values(array_unique(array_merge($render['warnings'], $produced['warnings'])));

		return [
			'outputs' => array_map(fn (array $output): array => $this->manifestEntry(output: $output), $produced['outputs']),
			'metadata' => $this->record(
				template: $template + ['id' => $templateId],
				dataRefs: $dataRefs,
				formats: $formats,
				outputs: $produced['outputs'],
				warnings: $warnings,
				options: $options
			),
			'warnings' => $warnings,
		];

	}//end generate()

	/**
	 * The formats a request asks for, validated and deduplicated.
	 *
	 * @param array $options The generation options.
	 *
	 * @return string[] The formats, in the order asked.
	 *
	 * @throws Exception 400 when `formats` is combined with `format`, empty, not a list or names an unknown format.
	 *
	 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.4
	 */
	public function requestedFormats(array $options): array {
		if (array_key_exists('format', $options) === true) {
			throw new Exception(message: 'Use options.format or options.formats, not both', code: 400);
		}

		$formats = ($options['formats'] ?? null);
		if (is_array($formats) === false || $formats === [] || array_is_list($formats) === false) {
			throw new Exception(message: 'options.formats must be a non-empty list of formats', code: 400);
		}

		foreach ($formats as $format) {
			if (in_array($format, self::FORMATS, true) === false) {
				throw new Exception(
					message: 'Unsupported format ' . json_encode($format) . ' in options.formats. Valid formats: ' . implode(', ', self::FORMATS),
					code: 400
				);
			}
		}

		return array_values(array_unique($formats));

	}//end requestedFormats()

	/**
	 * How the one render becomes each format: a Twig template is rendered
	 * once to HTML and that HTML converted per format; an office template's
	 * data is resolved once and its filled DOCX converted per format
	 * (MultiFormatOutputProducer starts from the filled DOCX, REQ-DDMFO-007).
	 *
	 * @param array  $template   The template.
	 * @param string $templateId Its id.
	 * @param array  $dataRefs   The data references.
	 * @param array  $options    The generation options.
	 *
	 * @return array{convert: callable, warnings: string[]} convert(format) returns {content, warnings}.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-7
	 */
	private function converterFor(array $template, string $templateId, array $dataRefs, array $options): array {
		if ($this->officeRenderer?->isOffice(template: $template) === true) {
			$resolved = $this->documents->resolveTemplateData(dataRefs: $dataRefs, options: $options);

			return [
				'convert' => fn (string $format): array => $this->officeRenderer->render(
					template: $template,
					data: $resolved['data'],
					format: $format,
					options: $options
				),
				'warnings' => $resolved['warnings'],
			];
		}

		$render = $this->documents->generatePreview(templateId: $templateId, dataRefs: $dataRefs, options: $options);
		$pdfOptions = $this->renderPipeline->buildPdfOptions(
			template: $template,
			huisstijl: $this->renderPipeline->loadHuisstijl(huisstijlId: ($options['huisstijlId'] ?? null)),
			options: $options
		);

		return [
			'convert' => fn (string $format): array => [
				'content' => $this->renderPipeline->produceOutput(htmlContent: $render['html'], format: $format, pdfOptions: $pdfOptions),
				'warnings' => $this->renderPipeline->getLastOutputWarnings(),
			],
			'warnings' => $render['warnings'],
		];

	}//end converterFor()

	/**
	 * Produce every format from the one render and file each result.
	 *
	 * @param callable $convert    format => {content, warnings}.
	 * @param string[] $formats    The formats.
	 * @param string   $userId     Whose Files the outputs go in.
	 * @param string   $targetPath The folder, relative to the user's Files.
	 * @param string   $basename   The file name without extension.
	 *
	 * @return array{outputs: array<int, array<string, mixed>>, warnings: string[]}
	 */
	private function produce(
		callable $convert,
		array $formats,
		string $userId,
		string $targetPath,
		string $basename,
	): array {
		$outputs = [];
		$warnings = [];
		foreach ($formats as $format) {
			$output = ['format' => $format, 'status' => 'generated', 'fileId' => null, 'fileName' => null, 'path' => null, 'size' => null];
			try {
				$converted = $convert($format);
				$content = $converted['content'];
				$warnings = array_merge($warnings, $converted['warnings']);
				$stored = $this->storage->store(
					userId: $userId,
					targetPath: $targetPath,
					filename: $basename . self::EXTENSIONS[$format],
					content: $content
				);
				$output['fileId'] = $stored['fileId'];
				$output['fileName'] = $stored['name'];
				$output['path'] = $stored['path'];
				$output['size'] = $stored['size'];
			} catch (Throwable $e) {
				$output['status'] = 'failed';
				$output['error'] = $e->getMessage();
			}

			$outputs[] = $output;
		}//end foreach

		return ['outputs' => $outputs, 'warnings' => $warnings];

	}//end produce()

	/**
	 * One manifest entry: the file's WebDAV address to download it, so the
	 * same access control as opening it in Files applies.
	 *
	 * @param array $output The output as produce() made it.
	 *
	 * @return array{format: string, status: string, fileId: int|null, fileName: string|null,
	 *               downloadUrl: string|null, size: int|null, error?: string}
	 */
	private function manifestEntry(array $output): array {
		$entry = [
			'format' => $output['format'],
			'status' => $output['status'],
			'fileId' => $output['fileId'],
			'fileName' => $output['fileName'],
			'downloadUrl' => null,
			'size' => $output['size'],
		];
		if (isset($output['error']) === true) {
			$entry['error'] = $output['error'];
		}

		if (preg_match('#^/([^/]+)/files/(.+)$#', (string) $output['path'], $match) === 1) {
			$davPath = '/remote.php/dav/files/' . rawurlencode($match[1]) . '/' . implode('/', array_map('rawurlencode', explode('/', $match[2])));
			$entry['downloadUrl'] = ($this->urls?->getAbsoluteURL($davPath) ?? $davPath);
		}

		return $entry;

	}//end manifestEntry()

	/**
	 * Write the one generatedDocument entry of the job.
	 *
	 * `format` is the first format asked for, `fileId`/`filePath` the first
	 * file made; `outputs` carries every format, failed ones included.
	 *
	 * @param array    $template The template, with its id.
	 * @param array    $dataRefs The data references.
	 * @param string[] $formats  The formats.
	 * @param array    $outputs  The outputs.
	 * @param string[] $warnings The warnings.
	 * @param array    $options  The options (userId, caseId).
	 *
	 * @return array The entry.
	 */
	private function record(array $template, array $dataRefs, array $formats, array $outputs, array $warnings, array $options): array {
		$made = array_values(array_filter($outputs, static fn (array $output): bool => $output['status'] === 'generated'));
		$lines = array_map(
			static fn (array $output): array => array_filter(
				['format' => $output['format'], 'fileId' => $output['fileId'], 'status' => $output['status'], 'error' => ($output['error'] ?? null)],
				static fn ($value): bool => $value !== null
			),
			$outputs
		);

		return $this->auditLog->log(
			template: ['id' => $template['id'], 'version' => ($template['version'] ?? null), 'name' => (string) ($template['name'] ?? '')],
			dataRefs: $dataRefs,
			format: $formats[0],
			outcome: [
				'status' => 'generated',
				'warnings' => $warnings,
				'caseId' => ($options['caseId'] ?? null),
				'errorMessage' => null,
				'fileId' => ($made[0]['fileId'] ?? null),
				'filePath' => ($made[0]['path'] ?? null),
			],
			userId: (string) $options['userId'],
			extra: ['outputs' => $lines]
		);

	}//end record()
}//end class
