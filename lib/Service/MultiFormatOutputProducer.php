<?php

/**
 * Multi-format output
 *
 * One rendered document, several files: the PDF for the citizen and the
 * editable DOCX for the neighbouring municipality come from the same HTML,
 * so they cannot disagree about the data. Each format is converted and filed
 * on its own; one that fails is reported, the others are still made.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/multi-format-output/tasks.md#task-2.4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use Throwable;

/**
 * Produces and files every requested format from one rendered HTML.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class MultiFormatOutputProducer {
	/**
	 * File extension per output format.
	 *
	 * @var array<string, string>
	 */
	public const EXTENSIONS = [
		'pdf' => '.pdf',
		'odf' => '.odt',
		'docx' => '.docx',
		'html' => '.html',
	];

	/**
	 * Constructor.
	 *
	 * @param DocumentRenderPipeline $renderPipeline Converts the rendered HTML per format.
	 * @param DocumentStorageService $storage        Files each output in the user's Files.
	 */
	public function __construct(
		private readonly DocumentRenderPipeline $renderPipeline,
		private readonly DocumentStorageService $storage,
	) {

	}//end __construct()

	/**
	 * The formats a request asks for, or null for a single-format request.
	 *
	 * @param array    $options The generation options.
	 * @param string[] $valid   The formats generation can make.
	 *
	 * @return string[]|null The formats, deduplicated, in the order asked; null when `formats` is absent.
	 *
	 * @throws Exception 400 when `formats` is combined with `format`, empty, not a list or names an unknown format.
	 *
	 * @spec openspec/changes/multi-format-output/tasks.md#task-2.4
	 */
	public static function requestedFormats(array $options, array $valid): ?array {
		if (array_key_exists('formats', $options) === false) {
			return null;
		}

		if (array_key_exists('format', $options) === true) {
			throw new Exception(message: 'Use options.format or options.formats, not both', code: 400);
		}

		$formats = $options['formats'];
		if (is_array($formats) === false || $formats === [] || array_is_list($formats) === false) {
			throw new Exception(message: 'options.formats must be a non-empty list of formats', code: 400);
		}

		foreach ($formats as $format) {
			if (in_array($format, $valid, true) === false) {
				$given = (string) json_encode($format);
				throw new Exception(
					message: "Unsupported format {$given} in options.formats. Valid formats: " . implode(', ', $valid),
					code: 400
				);
			}
		}

		return array_values(array_unique($formats));

	}//end requestedFormats()

	/**
	 * Convert the rendered HTML to every format and file each result.
	 *
	 * @param string   $html       The rendered HTML (the one render).
	 * @param string[] $formats    The formats, as {@see requestedFormats()} returned them.
	 * @param array    $pdfOptions The PDF options for the pdf output.
	 * @param string   $userId     Whose Files the outputs go in.
	 * @param string   $targetPath The folder, relative to the user's Files.
	 * @param string   $basename   The file name without extension.
	 *
	 * @return array{outputs: array<int, array<string, mixed>>, warnings: string[]}
	 *               One output per format: format, status (generated|failed), fileId,
	 *               fileName, path, size, and error when it failed.
	 *
	 * @spec openspec/changes/multi-format-output/tasks.md#task-2.4
	 */
	public function produce(
		string $html,
		array $formats,
		array $pdfOptions,
		string $userId,
		string $targetPath,
		string $basename,
	): array {
		$outputs = [];
		$warnings = [];
		foreach ($formats as $format) {
			$output = ['format' => $format, 'status' => 'generated', 'fileId' => null, 'fileName' => null, 'path' => null, 'size' => null];
			try {
				$content = $this->renderPipeline->produceOutput(htmlContent: $html, format: $format, pdfOptions: $pdfOptions);
				$warnings = array_merge($warnings, $this->renderPipeline->getLastOutputWarnings());
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

		return ['outputs' => $outputs, 'warnings' => array_values(array_unique($warnings))];

	}//end produce()
}//end class
