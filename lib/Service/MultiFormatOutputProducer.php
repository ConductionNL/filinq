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

use Exception;
use OCA\Filinq\Kernel\Contracts\MultiFormatOutputProducerInterface;
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
class MultiFormatOutputProducer implements MultiFormatOutputProducerInterface {

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
	 */
	public function __construct(
		private readonly DocumentService $documents,
		private readonly TemplateService $templates,
		private readonly DocumentRenderPipeline $renderPipeline,
		private readonly DocumentStorageService $storage,
		private readonly GeneratedDocumentLogger $auditLog,
		private readonly ?PlainLanguageRenditionService $plainRendition = null,
		private readonly ?IURLGenerator $urls = null,
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
		$this->refuseUnsupportedOptions(options: $options);

		$template = $this->templates->getTemplate(id: $templateId);
		if ($this->plainRendition?->counterpartOf(template: $template) !== null) {
			// A formal letter and its plain counterpart are filed together or not at all.
			throw new Exception(message: 'options.formats cannot be used for a template with a plain-language counterpart yet; use options.format', code: 400);
		}

		$render = $this->documents->generatePreview(templateId: $templateId, dataRefs: $dataRefs, options: $options);
		$pdfOptions = $this->renderPipeline->buildPdfOptions(
			template: $template,
			huisstijl: $this->renderPipeline->loadHuisstijl(huisstijlId: ($options['huisstijlId'] ?? null)),
			options: $options
		);
		$basename = pathinfo((string) ($options['filename'] ?? 'document'), PATHINFO_FILENAME);
		if ($basename === '') {
			$basename = 'document';
		}

		$job = new MultiFormatJob(template: $template + ['id' => $templateId], dataRefs: $dataRefs, formats: $formats, options: $options);
		$produced = $this->produce(
			job: $job,
			rendering: [
				'html' => $render['html'],
				'pdfOptions' => $pdfOptions,
				'targetPath' => $this->documents->buildOutputTargetPath(
					templateId: $templateId,
					explicitTargetPath: ($options['output']['targetPath'] ?? null),
					template: $template
				),
				'basename' => $basename,
			]
		);
		$warnings = array_values(array_unique(array_merge($render['warnings'], $produced['warnings'])));

		return [
			'outputs' => array_map(fn (array $output): array => $this->manifestEntry(output: $output), $produced['outputs']),
			'metadata' => $this->record(job: $job, produced: ['outputs' => $produced['outputs'], 'warnings' => $warnings]),
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
	 * Refuse the options a multi-format job cannot honour yet.
	 *
	 * @param array $options The generation options.
	 *
	 * @return void
	 *
	 * @throws Exception 400 without a user, with a wizard context or with a pinned template version.
	 */
	private function refuseUnsupportedOptions(array $options): void {
		if ((string) ($options['userId'] ?? '') === '') {
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

	}//end refuseUnsupportedOptions()

	/**
	 * Convert the rendered HTML to every format and file each result.
	 *
	 * @param MultiFormatJob $job       The formats and whose Files the outputs go in.
	 * @param array{html: string, pdfOptions: array, targetPath: string, basename: string} $rendering The rendered
	 *        HTML (the one render), the PDF options, the folder relative to the user's Files and the file name
	 *        without extension.
	 *
	 * @return array{outputs: array<int, array<string, mixed>>, warnings: string[]}
	 */
	private function produce(MultiFormatJob $job, array $rendering): array {
		$outputs = [];
		$warnings = [];
		foreach ($job->formats as $format) {
			$output = ['format' => $format, 'status' => 'generated', 'fileId' => null, 'fileName' => null, 'path' => null, 'size' => null];
			try {
				$content = $this->renderPipeline->produceOutput(htmlContent: $rendering['html'], format: $format, pdfOptions: $rendering['pdfOptions']);
				$warnings = array_merge($warnings, $this->renderPipeline->getLastOutputWarnings());
				$stored = $this->storage->store(
					userId: $job->userId(),
					targetPath: $rendering['targetPath'],
					filename: $rendering['basename'] . self::EXTENSIONS[$format],
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
	 * @param MultiFormatJob $job The template, data references, formats and options (userId, caseId).
	 * @param array{outputs: array<int, array<string, mixed>>, warnings: string[]} $produced The outputs and the warnings.
	 *
	 * @return array The entry.
	 */
	private function record(MultiFormatJob $job, array $produced): array {
		$template = $job->template;
		$made = array_values(array_filter($produced['outputs'], static fn (array $output): bool => $output['status'] === 'generated'));
		$lines = array_map(
			static fn (array $output): array => array_filter(
				['format' => $output['format'], 'fileId' => $output['fileId'], 'status' => $output['status'], 'error' => ($output['error'] ?? null)],
				static fn ($value): bool => $value !== null
			),
			$produced['outputs']
		);

		return $this->auditLog->log(
			generation: new DocumentGenerationRecord(
				template: ['id' => $template['id'], 'version' => ($template['version'] ?? null), 'name' => (string) ($template['name'] ?? '')],
				dataRefs: $job->dataRefs,
				format: $job->formats[0],
				userId: $job->userId(),
				extra: ['outputs' => $lines]
			),
			outcome: [
				'status' => 'generated',
				'warnings' => $produced['warnings'],
				'caseId' => ($job->options['caseId'] ?? null),
				'errorMessage' => null,
				'fileId' => ($made[0]['fileId'] ?? null),
				'filePath' => ($made[0]['path'] ?? null),
			]
		);

	}//end record()
}//end class
